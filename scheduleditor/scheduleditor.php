<?php

/**
 * Name: Scheduled Editor
 * Description: Bearbeiten geplanter (noch nicht veröffentlichter) Beiträge - Text, Titel, Zusammenfassung und Veröffentlichungszeitpunkt
 * Version: 0.1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;

/**
 * Addon-Installation: registriert den Hook fuer den Menue-Eintrag
 */
function scheduleditor_install()
{
	Hook::register('nav_info', __FILE__, 'scheduleditor_nav_info');
}

function scheduleditor_uninstall()
{
	Hook::unregister('nav_info', __FILE__, 'scheduleditor_nav_info');
}

/**
 * Fuegt einen Eintrag ins Nutzermenue hinzu
 */
function scheduleditor_nav_info(array &$nav_info)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	if (!isset($nav_info['nav']['usermenu']) || !is_array($nav_info['nav']['usermenu'])) {
		return;
	}

	$nav_info['nav']['usermenu'][] = [
		'scheduleditor',
		DI::l10n()->t('Edit scheduled posts'),
		'',
		DI::l10n()->t('Edit or remove your scheduled posts'),
		scheduleditor_menu_icon(),
	];
}

/**
 * Icon-Klasse abhaengig vom verfuegbaren Icon-Set
 */
function scheduleditor_menu_icon(): string
{
	return is_dir(DI::basePath() . '/view/asset/remixicon')
		? 'ri-calendar-check-line'
		: 'fa-calendar';
}

/**
 * Erforderlich, damit das Addon als Modul unter /scheduleditor erreichbar ist
 */
function scheduleditor_module()
{
}

/**
 * GET-Handler
 */
function scheduleditor_content(): string
{
	if (!DI::userSession()->getLocalUserId()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect();
	}

	$argv = DI::args()->getArgv();

	if (($argv[1] ?? '') === 'edit' && !empty($argv[2]) && is_numeric($argv[2])) {
		return scheduleditor_edit_form((int)$argv[2]);
	}

	if (($argv[1] ?? '') === 'new') {
		return scheduleditor_edit_form(0);
	}

	return scheduleditor_list();
}

/**
 * POST-Handler (speichern / loeschen)
 */
function scheduleditor_post()
{
	if (!DI::userSession()->getLocalUserId()) {
		throw new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
	}

	BaseModule::checkFormSecurityTokenForbiddenOnError('scheduleditor');

	$uid    = DI::userSession()->getLocalUserId();
	$action = trim($_REQUEST['action'] ?? '');
	$id     = (int)($_REQUEST['id'] ?? 0);

	if ($action === 'create') {
		scheduleditor_create_post($uid);
	}

	if (empty($id) || !DBA::exists('delayed-post', ['id' => $id, 'uid' => $uid])) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Entry not found.'));
		DI::baseUrl()->redirect('scheduleditor');
	}

	if ($action === 'delete') {
		Post\Delayed::deleteById($id);
		DI::sysmsg()->addInfo(DI::l10n()->t('Scheduled post removed.'));
		DI::baseUrl()->redirect('scheduleditor');
	}

	if ($action !== 'save') {
		DI::baseUrl()->redirect('scheduleditor');
	}

	$parameters = Post\Delayed::getParametersForid($id);
	if (empty($parameters['item'])) {
		DI::sysmsg()->addNotice(DI::l10n()->t('This post cannot be edited. It may already have been published.'));
		DI::baseUrl()->redirect('profile/' . DI::userSession()->getLocalUserNickname() . '/schedule');
	}

	$item = $parameters['item'];

	$item['title']           = trim($_REQUEST['scheduleditor_title'] ?? '');
	$item['content-warning'] = trim($_REQUEST['scheduleditor_summary'] ?? '');
	$item['body']            = trim($_REQUEST['scheduleditor_body'] ?? '');

	if ($item['body'] === '') {
		DI::sysmsg()->addNotice(DI::l10n()->t('You cannot schedule an empty post.'));
		DI::baseUrl()->redirect('scheduleditor/edit/' . $id);
	}

	$rawTime = trim($_REQUEST['scheduleditor_scheduled_at'] ?? '');
	if (!empty($rawTime)) {
		try {
			$delayed = DateTimeFormat::convert($rawTime, 'UTC', scheduleditor_user_timezone($uid));
		} catch (\Throwable $e) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Invalid date.'));
			DI::baseUrl()->redirect('scheduleditor/edit/' . $id);
		}

		if ($delayed <= DateTimeFormat::utcNow()) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Scheduled time must be in the future.'));
			DI::baseUrl()->redirect('scheduleditor/edit/' . $id);
		}
	} else {
		$delayed = '';
	}

	$uri         = (string)($parameters['uri'] ?? ($item['uri'] ?? ''));
	$notify      = (int)($parameters['notify'] ?? 0);
	$taglist     = (array)($parameters['taglist'] ?? []);
	$attachments = (array)($parameters['attachments'] ?? []);
	$mode        = (int)($parameters['unprepared'] ?? Post\Delayed::PREPARED);

	Post\Delayed::deleteById($id);

	$newId = Post\Delayed::add($uri, $item, $notify, $mode, $delayed, $taglist, $attachments);

	if (!empty($newId)) {
		DI::sysmsg()->addInfo(DI::l10n()->t('Scheduled post updated.'));
	} else {
		$fallbackId = Post\Delayed::add($uri, $item, $notify, $mode, '', $taglist, $attachments);
		DI::sysmsg()->addNotice($fallbackId
			? DI::l10n()->t('The post could not be rescheduled to the desired time and was queued for the next possible time instead.')
			: DI::l10n()->t('Error while rescheduling the post.'));
	}

	DI::baseUrl()->redirect('scheduleditor');
}

/**
 * Legt einen NEUEN geplanten Beitrag an
 */
function scheduleditor_create_post(int $uid)
{
	$body    = trim($_REQUEST['scheduleditor_body'] ?? '');
	$title   = trim($_REQUEST['scheduleditor_title'] ?? '');
	$summary = trim($_REQUEST['scheduleditor_summary'] ?? '');
	$rawTime = trim($_REQUEST['scheduleditor_scheduled_at'] ?? '');

	if ($body === '') {
		DI::sysmsg()->addNotice(DI::l10n()->t('You cannot schedule an empty post.'));
		DI::baseUrl()->redirect('scheduleditor/new');
	}

	if (empty($rawTime)) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Please provide a publish date.'));
		DI::baseUrl()->redirect('scheduleditor/new');
	}

	try {
		$delayed = DateTimeFormat::convert($rawTime, 'UTC', scheduleditor_user_timezone($uid));
	} catch (\Throwable $e) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Invalid date.'));
		DI::baseUrl()->redirect('scheduleditor/new');
	}

	if ($delayed <= DateTimeFormat::utcNow()) {
		DI::sysmsg()->addNotice(DI::l10n()->t('Scheduled time must be in the future.'));
		DI::baseUrl()->redirect('scheduleditor/new');
	}

	$owner = User::getOwnerDataById($uid);

	$item = [
		'uid'              => $uid,
		'network'          => Protocol::DFRN,
		'verb'             => Activity::POST,
		'contact-id'       => $owner['id'],
		'author-id'        => Contact::getPublicIdByUserId($uid),
		'owner-id'         => Contact::getPublicIdByUserId($uid),
		'title'            => $title,
		'content-warning'  => $summary,
		'body'             => $body,
		'app'              => 'Scheduleditor',
		'sensitive'        => false,
		'allow_cid'        => '',
		'allow_gid'        => '',
		'deny_cid'         => '',
		'deny_gid'         => '',
		'private'          => Item::PUBLIC,
	];

	$item = DI::contentItem()->expandTags($item, false);

	$item['guid'] = System::createUUID();
	$item['uri']  = 'urn:uuid:' . $item['guid'];

	$newId = Post\Delayed::add($item['uri'], $item, Worker::PRIORITY_HIGH, Post\Delayed::PREPARED_NO_HOOK, $delayed);

	if (!empty($newId)) {
		DI::sysmsg()->addInfo(DI::l10n()->t('New post scheduled.'));
	} else {
		DI::sysmsg()->addNotice(DI::l10n()->t('Error while scheduling the post.'));
	}

	DI::baseUrl()->redirect('scheduleditor');
}

/**
 * Liste aller geplanten Beitraege des Nutzers
 */
function scheduleditor_list(): string
{
	$uid = DI::userSession()->getLocalUserId();
	$tz  = scheduleditor_user_timezone($uid);

	$entries = [];
	$rows    = DBA::select('delayed-post', ['id', 'uid', 'uri', 'delayed', 'wid'], ['uid' => $uid], ['order' => ['delayed' => true]]);
	while ($row = DBA::fetch($rows)) {
		$parameters = Post\Delayed::getParametersForid($row['id']);
		if (empty($parameters['item'])) {
			continue;
		}

		$item = $parameters['item'];

		$body = Item::setHashtags($item['body'] ?? '');

		$entries[] = [
			'id'           => $row['id'],
			'scheduled_at' => DateTimeFormat::convert($row['delayed'], $tz, 'UTC', 'D, d M Y H:i'),
			'title'        => $item['title'] ?? '',
			'preview'      => mb_substr(BBCode::toPlaintext($body, false), 0, 140),
			'private'      => !empty($item['private']),
			'edit_link'    => 'scheduleditor/edit/' . $row['id'],
		];
	}
	DBA::close($rows);

	$tpl = Renderer::getMarkupTemplate('scheduleditor.tpl', 'addon/scheduleditor/');

	return Renderer::replaceMacros($tpl, [
		'$form_security_token' => BaseModule::getFormSecurityToken('scheduleditor'),
		'$title'               => DI::l10n()->t('Edit scheduled posts'),
		'$nickname'            => DI::userSession()->getLocalUserNickname(),
		'$scheduled_at'        => DI::l10n()->t('Scheduled'),
		'$content'             => DI::l10n()->t('Content'),
		'$edit'                => DI::l10n()->t('Edit'),
		'$delete'              => DI::l10n()->t('Remove post'),
		'$private'             => DI::l10n()->t('Private Message'),
		'$empty'               => DI::l10n()->t('No scheduled posts.'),
		'$new_post'            => DI::l10n()->t('New scheduled post'),
		'$core_schedule'       => DI::l10n()->t('Open the core scheduled posts overview'),
		'$entries'             => $entries,
		'$show_form'           => false,
	]);
}

/**
 * Bearbeitungsformular fuer einen geplanten Beitrag
 */
function scheduleditor_edit_form(int $id): string
{
	$uid = DI::userSession()->getLocalUserId();
	$tz  = scheduleditor_user_timezone($uid);

	$isNew = ($id === 0);

	if (!$isNew) {
		if (!DBA::exists('delayed-post', ['id' => $id, 'uid' => $uid])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Entry not found.'));
			DI::baseUrl()->redirect('scheduleditor');
		}

		$parameters = Post\Delayed::getParametersForid($id);
		if (empty($parameters['item'])) {
			DI::sysmsg()->addNotice(DI::l10n()->t('This post cannot be edited. It may already have been published.'));
			DI::baseUrl()->redirect('scheduleditor');
		}

		$item = $parameters['item'];

		// Rueckwandlung der Hashtag- und Tag-Darstellung fuer das Formular
		$body = Item::setHashtags($item['body'] ?? '');
		$body = scheduleditor_undo_tagging($body);

		$delayedLocal = DateTimeFormat::convert($parameters['parameters']['delayed'] ?? '', $tz, 'UTC', 'Y-m-d\TH:i');
	} else {
		$item        = [];
		$body        = '';
		$delayedLocal = '';
	}

	// Volle Editor-Funktionsfaehigkeit nur mit frio
	if (DI::appHelper()->getCurrentTheme() !== 'frio') {
		DI::sysmsg()->addNotice(DI::l10n()->t('The full editor functionality (formatting, images) is only available with the frio theme.'));
	} else {
		DI::page()->registerFooterScript(Theme::getPathForFile('js/ajaxupload.js'));
		DI::page()->registerFooterScript(Theme::getPathForFile('js/linkPreview.js'));
		DI::page()->registerFooterScript(Theme::getPathForFile('js/compose.js'));
	}

	$tpl = Renderer::getMarkupTemplate('scheduleditor.tpl', 'addon/scheduleditor/');

	return Renderer::replaceMacros($tpl, [
		'$form_security_token' => BaseModule::getFormSecurityToken('scheduleditor'),
		'$rand_num'            => Crypto::randomDigits(12),
		'$title'               => $isNew
			? DI::l10n()->t('New scheduled post')
			: DI::l10n()->t('Edit scheduled post'),
		'$nickname'            => DI::userSession()->getLocalUserNickname(),
		'$entries'             => [],
		'$show_form'           => true,
		'$new_post'            => DI::l10n()->t('New scheduled post'),
		'$form_action'         => $isNew ? 'create' : 'save',
		'$post_id'             => $id,
		'$field_title'         => DI::l10n()->t('Title'),
		'$field_summary'       => DI::l10n()->t('Summary (abstract)'),
		'$field_body'          => DI::l10n()->t('Body'),
		'$field_scheduled_at'  => DI::l10n()->t('Publish date'),
		'$value_title'         => $item['title'] ?? '',
		'$value_summary'       => $item['content-warning'] ?? '',
		'$value_body'          => $body,
		'$value_scheduled_at'  => $delayedLocal,
		'$private_notice'     => empty($item['private'])
			? ''
			: DI::l10n()->t('This post has privacy settings. They are kept unchanged.'),
		'$save'                => DI::l10n()->t('Save'),
		'$cancel'              => DI::l10n()->t('Cancel'),
		// Toolbar-Labels
		'$l10n_img'            => DI::l10n()->t('Insert image'),
		'$l10n_attach'         => DI::l10n()->t('Insert Link or Media'),
		'$l10n_emojis'         => DI::l10n()->t('Add emojis'),
		'$l10n_url'            => DI::l10n()->t('Link'),
		'$l10n_uline'          => DI::l10n()->t('Underline'),
		'$l10n_italic'         => DI::l10n()->t('Italic'),
		'$l10n_bold'           => DI::l10n()->t('Bold'),
		'$l10n_quote'          => DI::l10n()->t('Quote'),
		'$l10n_cw'             => DI::l10n()->t('Content warning'),
		'$l10n_code'           => DI::l10n()->t('Code'),
		'$l10n_prompt'         => DI::l10n()->t('Please enter a link URL or media URL:'),
		'$l10n_preview'        => DI::l10n()->t('Preview'),
		'$l10n_close_preview'  => DI::l10n()->t('Close preview'),
	]);
}

/**
 * Zeitzone des Nutzers aus dem User-Datensatz lesen (Fallback UTC)
 */
function scheduleditor_user_timezone(int $uid): string
{
	$user = User::getById($uid, ['timezone']);
	return (empty($user['timezone'])) ? 'UTC' : $user['timezone'];
}

/**
 * Entfernt BBCode-Tag-Links aus dem Body, damit das Formular Klartext-Tags zeigt
 */
function scheduleditor_undo_tagging(string $body): string
{
	if (preg_match_all('/([!#@])\[url=(.*?)\](.*?)\[\/url\]/ism', $body, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			if (in_array($match[1], ['!', '@'])) {
				$contact  = Contact::getByURL($match[2], false, ['addr']);
				$match[3] = empty($contact['addr']) ? $match[2] : $contact['addr'];
			}
			$body = str_replace($match[0], $match[1] . $match[3], $body);
		}
	}

	return $body;
}
