<?php
/*
 * Name: instaproxy
 * Description: Replaces links to instagram.com to an Proxigram instance in all displays of postings on a node.
 * Version: 0.2
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 *
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

CONST INSTAPROXY_DEFAULT = 'https://proxigram.lunar.icu';

function instaproxy_install()
{
	Hook::register('prepare_body_final',  __FILE__, 'instaproxy_render');
	Hook::register('addon_settings',      __FILE__, 'instaproxy_settings');
	Hook::register('addon_settings_post', __FILE__, 'instaproxy_settings_post');
}

/* Handle the admin settings post request */
function instaproxy_addon_admin_post()
{
	// Sanitize and validate the input as a valid URL
	$url = filter_var(trim($_POST['instaproxyserver'], " \n\r\t\v\x00/"), FILTER_VALIDATE_URL);
	if ($url !== false) {
		DI::config()->set('instaproxy', 'server', $url);
	}
}

/* Admin settings page */
function instaproxy_addon_admin(string &$o)
{
	$instaproxyserver = DI::config()->get('instaproxy', 'server', INSTAPROXY_DEFAULT);
	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/instaproxy/');
	$o = Renderer::replaceMacros($t, [
		'$settingdescription' => DI::l10n()->t('Which proxigram server shall be used for the replacements in the post bodies? Use the URL with servername and protocol. See %s for a list of available public proxigram servers.', 'https://codeberg.org/ThePenguinDev/proxigram/wiki/Instances#user-content-public'),
		'$instaproxyserver'   => ['instaproxyserver', DI::l10n()->t('Instaproxy server'), $instaproxyserver, DI::l10n()->t('See %s for a list of available proxigram servers.', '<a href="https://codeberg.org/ThePenguinDev/proxigram/wiki/Instances#user-content-public/">https://codeberg.org/ThePenguinDev/proxigram/wiki/Instances#user-content-public/</a>')],
		'$submit'             => DI::l10n()->t('Save Settings'),
	]);
}

/* User settings page */
function instaproxy_settings(array &$data)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$enabled = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'instaproxy', 'enabled');
	$server  = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'instaproxy', 'server', DI::config()->get('instaproxy', 'server', INSTAPROXY_DEFAULT));

	$t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/instaproxy/');
	$html = Renderer::replaceMacros($t, [
		'$enabled' => ['enabled', DI::l10n()->t('Replace Instagram links with links to a Proxigram server'), $enabled, DI::l10n()->t('If enabled, Instagram links are replaced with the links to the specified Proxigram server.')],
		'$server'  => ['server', DI::l10n()->t('Proxigram server'), $server, DI::l10n()->t('See %s for a list of available Proxigram servers.', '<a href="https://codeberg.org/ThePenguinDev/proxigram/wiki/Instances#user-content-public/">https://codeberg.org/ThePenguinDev/proxigram/wiki/Instances#user-content-public/</a>')],
	]);

	$data = [
		'addon' => 'instaproxy',
		'title' => DI::l10n()->t('Instaproxy Settings'),
		'html'  => $html,
	];
}

/* Handle user settings post */
function instaproxy_settings_post(array &$b)
{
	if (!DI::userSession()->getLocalUserId() || empty($_POST['instaproxy-submit'])) {
		return;
	}

	// Save user preferences
	DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'instaproxy', 'enabled', (bool)$_POST['enabled']);

	$server = trim($_POST['server'], " \n\r\t\v\x00/");
	// Validate the custom server URL provided by the user
	if (!empty($server) && filter_var($server, FILTER_VALIDATE_URL) && $server != DI::config()->get('instaproxy', 'server', INSTAPROXY_DEFAULT)) {
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'instaproxy', 'server', $server);
	} else {
		DI::pConfig()->delete(DI::userSession()->getLocalUserId(), 'instaproxy', 'server');
	}
}

/* Replace Instagram links with the chosen Proxigram instance */
function instaproxy_render(array &$b)
{
	if (!DI::userSession()->getLocalUserId() || !DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'instaproxy', 'enabled')) {
		return;
	}

	$original = $b['html'];
	$server   = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'instaproxy', 'server', DI::config()->get('instaproxy', 'server', INSTAPROXY_DEFAULT));

	// Replace Instagram URLs with the selected Proxigram server
	$b['html'] = preg_replace("~https?://(?:www\.)?instagram\.com/p/([a-zA-Z0-9_-]+)~ism", $server . '/p/$1', $b['html']);
	$b['html'] = preg_replace("~https?://ig\.me/([a-zA-Z0-9_-]+)~ism", $server . '/p/$1', $b['html']);

	if ($original != $b['html']) {
		$b['html'] .= '<hr><p><small>' . DI::l10n()->t('(Instaproxy addon enabled: Instagram links via %s)', $server) . '</small></p>';
	}
}
