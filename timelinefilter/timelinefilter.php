<?php

/**
 * Name: Timeline Filter
 * Description: Filters hashtags, words and accounts in personal timelines
 * Version: 1.7.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\Security\Login;

function timelinefilter_install(): void
{
	Hook::register('page_end', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_page_end', 999);
	Hook::register('addon_settings', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_addon_settings');
	Hook::register('addon_settings_post', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_addon_settings_post');
	Hook::register('nav_info', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_nav_info');
}

/**
 * Marks the addon as a module, enables the /timelinefilter page.
 */
function timelinefilter_module()
{
}

/**
 * Addon settings entry, links to the own config page.
 */
function timelinefilter_addon_settings(array &$data): void
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$data = [
		'addon' => 'timelinefilter',
		'title' => DI::l10n()->t('Timeline Filter'),
		'href'  => 'timelinefilter',
	];
}

/**
 * User menu entry, points to the config page.
 */
function timelinefilter_nav_info(array &$nav_info): void
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	if (!isset($nav_info['nav']['usermenu']) || !is_array($nav_info['nav']['usermenu'])) {
		return;
	}

	$nav_info['nav']['usermenu'][] = [
		'timelinefilter',
		DI::l10n()->t('Timeline Filter'),
		'',
		DI::l10n()->t('Manage your timeline filter rules'),
		timelinefilter_menu_icon(),
	];
}

function timelinefilter_menu_icon(): string
{
	if (is_dir(DI::basePath() . '/view/asset/remixicon')) {
		return 'ri-filter-2-line';
	}

	return 'fa-filter';
}

/**
 * Config page (GET /timelinefilter).
 */
function timelinefilter_content(): string
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return Login::form('timelinefilter');
	}

	$enabled = !DI::pConfig()->get($uid, 'timelinefilter', 'disable', 1);
	$rules   = timelinefilter_get_rules($uid);

	if (empty($rules)) {
		$rules[] = [
			'keyword'        => '',
			'type'           => 'hashtag',
			'duration'       => 'always',
			'expires'        => 0,
			'days_left'      => null,
			'days_left_text' => '',
		];
	} else {
		$now = time();
		foreach ($rules as &$rule) {
			if (!empty($rule['expires']) && $rule['expires'] > $now) {
				$days                 = (int) ceil(($rule['expires'] - $now) / 86400);
				$rule['days_left']      = $days;
				$rule['days_left_text'] = sprintf(DI::l10n()->tt('%d day remaining', '%d days remaining', $days), $days);
			} else {
				$rule['days_left']      = null;
				$rule['days_left_text'] = '';
			}
		}
		unset($rule);
	}

	$template = Renderer::getMarkupTemplate('settings.tpl', 'addon/timelinefilter/');
	return Renderer::replaceMacros($template, [
		'$title'       => DI::l10n()->t('Timeline Filter'),
		'$back'        => DI::l10n()->t('Back to Addon Settings'),
		'$add_rule'    => DI::l10n()->t('Add a rule'),
		'$info'        => DI::l10n()->t('Safe Filter: Define personal rules with optional expiration dates to hide posts.'),
		'$enabled'     => ['timelinefilter-enable', DI::l10n()->t('Enable Filter'), $enabled],
		'$words_label' => DI::l10n()->t('Filter Rules'),
		'$words_help'  => DI::l10n()->t('Add keywords/accounts, select type and specify how long the filter should remain active.'),
		'$rules'       => $rules,
		'$submit'      => DI::l10n()->t('Save Settings'),
		'$opt_always'  => DI::l10n()->t('Always'),
		'$opt_1d'      => DI::l10n()->t('1 Day'),
		'$opt_1w'      => DI::l10n()->t('1 Week'),
		'$opt_1m'      => DI::l10n()->t('1 Month'),
	]);
}

/**
 * Form submit on the own page (POST /timelinefilter), redirects back.
 */
function timelinefilter_post(): void
{
	$unused = [];
	timelinefilter_addon_settings_post($unused);

	DI::baseUrl()->redirect('timelinefilter');
}

/**
 * Rule form processing, shared by own page and addon_settings_post hook.
 */
function timelinefilter_addon_settings_post(array &$b): void
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid || empty($_POST['timelinefilter-submit'])) {
		return;
	}

	if (!empty($_POST['timelinefilter-enable'])) {
		$disable = 0;
	} else {
		$disable = 1;
	}

	$keywords = [];
	if (isset($_POST['tf-keywords'])) {
		$keywords = $_POST['tf-keywords'];
	}
	$types = [];
	if (isset($_POST['tf-types'])) {
		$types = $_POST['tf-types'];
	}
	$durations = [];
	if (isset($_POST['tf-durations'])) {
		$durations = $_POST['tf-durations'];
	}
	$expires = [];
	if (isset($_POST['tf-expires'])) {
		$expires = $_POST['tf-expires'];
	}

	$rules = [];
	$now   = time();

	foreach ($keywords as $i => $kw) {
		$kw = trim($kw);
		if ($kw === '') {
			continue;
		}

		$duration = 'always';
		if (isset($durations[$i])) {
			$duration = $durations[$i];
		}

		$exp = 0;
		if (isset($expires[$i])) {
			$exp = (int) $expires[$i];
		}

		if ($exp === 0) {
			switch ($duration) {
				case '1d':
					$exp = $now + 86400;
					break;
				case '1w':
					$exp = $now + (7 * 86400);
					break;
				case '1m':
					$exp = $now + (30 * 86400);
					break;
				default:
					$exp = 0;
					break;
			}
		}

		$type = 'hashtag';
		if (isset($types[$i])) {
			$type = $types[$i];
		}

		$rules[] = [
			'keyword'  => $kw,
			'type'     => $type,
			'duration' => $duration,
			'expires'  => $exp,
		];
	}

	DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode($rules));
	DI::pConfig()->set($uid, 'timelinefilter', 'disable', $disable);
}

function timelinefilter_page_end(string &$html): void
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid || empty($html) || DI::pConfig()->get($uid, 'timelinefilter', 'disable', 1)) {
		return;
	}

	$rules = timelinefilter_get_rules($uid);
	if (empty($rules)) {
		return;
	}

	$hashtags = [];
	$words    = [];
	$accounts = [];

	foreach ($rules as $rule) {
		$kw = mb_strtolower(trim($rule['keyword']));
		switch ($rule['type']) {
			case 'hashtag':
				$hashtags[] = ltrim($kw, '#');
				break;
			case 'account':
				$accounts[] = ltrim($kw, '@');
				break;
			default:
				$words[] = $kw;
				break;
		}
	}

	if (empty($hashtags) && empty($words) && empty($accounts)) {
		return;
	}

	$configJson = json_encode([
		'hashtags' => $hashtags,
		'words'    => $words,
		'accounts' => $accounts,
		'selector' => 'article.media, .wall-item, .wall-item-comment, .wall-item-container.comment',
	], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	$jsPath = 'addon/timelinefilter/timelinefilter.js';
	$v      = '1.7.0';
	$jsUrl  = DI::baseUrl() . '/' . $jsPath . '?v=' . $v;

	$html .= '<script>window.timelinefilterConfig = ' . $configJson . ';</script>';
	$html .= '<script src="' . $jsUrl . '"></script>';
}

function timelinefilter_get_rules(int $uid): array
{
	static $cache = [];
	if (array_key_exists($uid, $cache)) {
		return $cache[$uid];
	}

	$json  = DI::pConfig()->get($uid, 'timelinefilter', 'rules', '[]');
	$rules = json_decode($json, true);

	if (!is_array($rules)) {
		$cache[$uid] = [];
		return $cache[$uid];
	}

	$now   = time();
	$clean = array_filter($rules, function ($r) use ($now) {
		return empty($r['expires']) || $r['expires'] <= 0 || $r['expires'] > $now;
	});

	if (count($clean) !== count($rules)) {
		DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode(array_values($clean)));
	}

	$cache[$uid] = array_values($clean);
	return $cache[$uid];
}
