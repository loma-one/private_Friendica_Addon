<?php
/**
 * Name: Quick Hashtag
 * Description: Inserts personal favorite hashtags into the post editor via a toolbar icon.
 * Version: 1.2
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

const QUICKTAG_DEFAULT_TAGS = '#Fediverse #Friendica #OpenSource';

function quicktag_install()
{
	Hook::register('addon_settings', __FILE__, 'quicktag_addon_settings');
	Hook::register('addon_settings_post', __FILE__, 'quicktag_addon_settings_post');
	Hook::register('jot_tool', __FILE__, 'quicktag_jot_tool');
}

function quicktag_uninstall()
{
	Hook::unregister('addon_settings', __FILE__, 'quicktag_addon_settings');
	Hook::unregister('addon_settings_post', __FILE__, 'quicktag_addon_settings_post');
	Hook::unregister('jot_tool', __FILE__, 'quicktag_jot_tool');
}

function quicktag_addon_settings(array &$data)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	$tags = DI::pConfig()->get($uid, 'quicktag', 'tags', QUICKTAG_DEFAULT_TAGS);

	$t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/quicktag/');
	$html = Renderer::replaceMacros($t, [
		'$description' => DI::l10n()->t('Enter your favorite hashtags, one per line or space-separated. They will be offered as chips in the post editor.'),
		'$tags'        => ['quicktag-tags', DI::l10n()->t('Favorite hashtags'), $tags],
	]);

	$data = [
		'addon' => 'quicktag',
		'title' => DI::l10n()->t('Quick Hashtags'),
		'html'  => $html,
	];
}

function quicktag_addon_settings_post(array &$b)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	if (isset($_POST['quicktag-tags'])) {
		DI::pConfig()->set($uid, 'quicktag', 'tags', trim($_POST['quicktag-tags']));
	}
}

function quicktag_jot_tool(string &$html)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	$tags = quicktag_split_tags(DI::pConfig()->get($uid, 'quicktag', 'tags', QUICKTAG_DEFAULT_TAGS));
	if (empty($tags)) {
		return;
	}

	$t = Renderer::getMarkupTemplate('jot_tool.tpl', 'addon/quicktag/');
	$html .= Renderer::replaceMacros($t, [
		'$title' => DI::l10n()->t('Quick Hashtags'),
		'$tags'  => $tags,
	]);

	DI::page()->registerFooterScript('addon/quicktag/quicktag.js');
	DI::page()->registerStylesheet('addon/quicktag/quicktag.css');
}

function quicktag_split_tags(string $rawTags): array
{
	$tags = [];

	$parts = preg_split('/\s+/', trim($rawTags));
	foreach (is_array($parts) ? $parts : [] as $tag) {
		$tag = trim($tag);
		if ($tag === '') {
			continue;
		}

		$tags[] = ($tag[0] === '#') ? $tag : '#' . $tag;
	}

	return array_values(array_unique($tags));
}
