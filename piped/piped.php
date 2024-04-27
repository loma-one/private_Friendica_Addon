<?php
/*
 * Name: piped
 * Description: Replaces links to youtube.com to an piped instance in all displays of postings on a node.
 * Version: 0.1
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * Author: Michael Vogel <https://pirati.ca/profile/heluecht>
 * Status:
 * Note:
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

CONST PIPED_DEFAULT = 'https://piped.video';

function piped_install()
{
	Hook::register('prepare_body_final',  __FILE__, 'piped_render');
	Hook::register('addon_settings',      __FILE__, 'piped_settings');
	Hook::register('addon_settings_post', __FILE__, 'piped_settings_post');
}

/* Handle the send data from the admin settings
 */
function piped_addon_admin_post()
{
	DI::config()->set('piped', 'server', trim($_POST['pipedserver'], " \n\r\t\v\x00/"));
}

/* Hook into the admin settings to let the admin choose an
 * piped server to use for the replacement.
 */
function piped_addon_admin(string &$o)
{
	$pipedserver = DI::config()->get('piped', 'server', PIPED_DEFAULT);
	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/piped/');
	$o = Renderer::replaceMacros($t, [
		'$settingdescription' => DI::l10n()->t('Which Piped server shall be used for the replacements in the post bodies? Use the URL with servername and protocol. See %s for a list of available public Piped servers.', 'https://github.com/TeamPiped/Piped/wiki/Instances'),
		'$pipedserver'    => ['pipedserver', DI::l10n()->t('Piped server'), $pipedserver, DI::l10n()->t('See %s for a list of available Piped servers.', '<a href="https://github.com/TeamPiped/Piped/wiki/Instances/">https://github.com/TeamPiped/Piped/wiki/Instances/</a>')],
		'$submit'             => DI::l10n()->t('Save Settings'),
	]);
}

function piped_settings(array &$data)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$enabled = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'piped', 'enabled');
	$server  = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'piped', 'server', DI::config()->get('piped', 'server', PIPED_DEFAULT));

	$t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/piped/');
	$html = Renderer::replaceMacros($t, [
		'$enabled' => ['enabled', DI::l10n()->t('Replace Youtube links with links to an Piped server'), $enabled, DI::l10n()->t('If enabled, Youtube links are replaced with the links to the specified Piped server.')],
		'$server'  => ['server', DI::l10n()->t('Piped server'), $server, DI::l10n()->t('See %s for a list of available Piped servers.', '<a href="https://github.com/TeamPiped/Piped/wiki/Instances/">https://github.com/TeamPiped/Piped/wiki/Instances/</a>')],
	]);

	$data = [
		'addon' => 'piped',
		'title' => DI::l10n()->t('Piped Settings'),
		'html'  => $html,
	];
}

function piped_settings_post(array &$b)
{
	if (!DI::userSession()->getLocalUserId() || empty($_POST['piped-submit'])) {
		return;
	}

	DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'piped', 'enabled', (bool)$_POST['enabled']);

	$server = trim($_POST['server'], " \n\r\t\v\x00/");
	if ($server != DI::config()->get('piped', 'server', PIPED_DEFAULT) && !empty($server)) {
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'piped', 'server', $server);
	} else {
		DI::pConfig()->delete(DI::userSession()->getLocalUserId(), 'piped', 'server');
	}
}

/*
 *  replace "youtube.com" with the chosen Piped instance
 */
function piped_render(array &$b)
{
	if (!DI::userSession()->getLocalUserId() || !DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'piped', 'enabled')) {
		return;
	}

	$original = $b['html'];
	$server   = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'piped', 'server', DI::config()->get('piped', 'server', PIPED_DEFAULT));

	$b['html'] = preg_replace("~https?://(?:www\.)?youtube\.com/watch\?v=(.*?)~ism", $server . '/watch?v=$1', $b['html']);
	$b['html'] = preg_replace("~https?://(?:www\.)?youtube\.com/embed/(.*?)~ism", $server . '/embed/$1', $b['html']);
	$b['html'] = preg_replace("~https?://(?:www\.)?youtube\.com/shorts/(.*?)~ism", $server . '/shorts/$1', $b['html']);
	$b['html'] = preg_replace ("/https?:\/\/music.youtube.com\/(.*?)/ism", $server . '/watch?v=$1', $b['html']);
	$b['html'] = preg_replace ("/https?:\/\/m.youtube.com\/(.*?)/ism", $server . '/watch?v=$1', $b['html']);
	$b['html'] = preg_replace("/https?:\/\/youtu.be\/(.*?)/ism", $server . '/watch?v=$1', $b['html']);

	if ($original != $b['html']) {
		$b['html'] .= '<hr><p><small>' . DI::l10n()->t('(Piped addon enabled: YouTube links via %s)', $server) . '</small></p>';
	}
}
