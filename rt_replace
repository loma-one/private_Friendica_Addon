<?php
/**
 * Name: Replace URL
 * Description: Replaces occurrences of specified URLs with the address of alternative servers in all displays of postings on a node.
 * Version: 1.1
 * Author: Dr. Tobias Quathamer <https://social.anoxinon.de/@toddy>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * Maintainer: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function rt_replace_install()
{
	Hook::register('prepare_body_final', 'addon/rt_replace/rt_replace.php', 'rt_replace_render');
}

/**
 * Handle sent data from admin settings
 */
function rt_replace_addon_admin_post()
{
	DI::config()->set('rt_replace', 'nitter_server', rtrim(trim($_POST['nitter_server']), '/'));
	DI::config()->set('rt_replace', 'instagram_server', rtrim(trim($_POST['instagram_server']), '/'));
	// Convert twelvefeet_sites into an array before setting the new value
	$twelvefeet_sites = explode(PHP_EOL, $_POST['twelvefeet_sites']);
	// Normalize URLs by using lower case, removing a trailing slash and whitespace
	$twelvefeet_sites = array_map(fn($value): string => rtrim(trim(strtolower($value)), '/'), $twelvefeet_sites);
	// Do not store empty lines or duplicates
	$twelvefeet_sites = array_filter($twelvefeet_sites, fn($value): bool => !empty($value));
	$twelvefeet_sites = array_unique($twelvefeet_sites);
	// Ensure a protocol and default to HTTPS
	$twelvefeet_sites = array_map(
		fn($value): string => substr($value, 0, 4) !== 'http' ? 'https://' . $value : $value,
		$twelvefeet_sites
	);
	asort($twelvefeet_sites);
	DI::config()->set('rt_replace', 'twelvefeet_sites', $twelvefeet_sites);
}

/**
 * Hook into admin settings to enable choosing a different server
 * for twitter, instagram, and news sites.
 */
function rt_replace_addon_admin(string &$o)
{
	$nitter_server    = DI::config()->get('rt_replace', 'nitter_server');
	$instagram_server = DI::config()->get('rt_replace', 'instagram_server');
	$twelvefeet_sites = implode(PHP_EOL, DI::config()->get('rt_replace', 'twelvefeet_sites') ?? [] ?: []);

	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/rt_replace/');
	$o = Renderer::replaceMacros($t, [
		'$nitter_server' => [
			'nitter_server',
			DI::l10n()->t('Nitter server'),
			$nitter_server,
			DI::l10n()->t('Specify the URL with protocol. The default is https://nitter.net.'),
			null,
			'placeholder="https://nitter.net"',
		],
		'$instagram_server' => [
			'instagram_server',
			DI::l10n()->t('Instagram server'),
			$instagram_server,
			DI::l10n()->t('Specify the URL with protocol. The default is https://proxigram.lunar.icu.'),
			null,
			'placeholder="https://proxigram.lunar.icu"',
		],
		'$twelvefeet_sites' => [
			'twelvefeet_sites',
			DI::l10n()->t('Sites which are accessed through 12ft.io'),
			$twelvefeet_sites,
			DI::l10n()->t('Specify the URLs with protocol, one per line.'),
			null,
			'rows="6"'
		],
		'$submit' => DI::l10n()->t('Save settings'),
	]);
}

/**
 * Replace proprietary URLs with their specified counterpart
 */
function rt_replace_render(array &$b)
{
	$replaced = false;

	$nitter_server = DI::config()->get('rt_replace', 'nitter_server');
	if (empty($nitter_server)) {
		$nitter_server = 'https://nitter.net';
	}

	$instagram_server = DI::config()->get('rt_replace', 'instagram_server');
	if (empty($instagram_server)) {
		$instagram_server = 'https://proxigram.lunar.icu';
	}

	// Handle some of twitter and instagram
	$replacements = [
		'https://mobile.twitter.com' => $nitter_server,
		'https://twitter.com'        => $nitter_server,
		'https://mobile.x.com'       => $nitter_server,
		'https://x.com'              => $nitter_server,
		'https://www.instagram.com'  => $instagram_server,
		'https://ig.me'              => $instagram_server,

	];
	foreach ($replacements as $server => $replacement) {
		if (strpos($b['html'], $server) !== false) {
			$b['html'] = str_replace($server, $replacement, $b['html']);
			$replaced  = true;
		}
	}

	$twelvefeet_sites = DI::config()->get('rt_replace', 'twelvefeet_sites') ?? [] ?: [];
	foreach ($twelvefeet_sites as $twelvefeet_site) {
		if (strpos($b['html'], $twelvefeet_site) !== false) {
			$b['html'] = str_replace($twelvefeet_site, 'https://12ft.io/' . $twelvefeet_site, $b['html']);
			$replaced  = true;
		}
	}


	if ($replaced) {
		$b['html'] .= '<hr><p><small>' . DI::l10n()->t('(URL replace addon enabled for X, Instagram and some news sites.)') . '</small></p>';
	}
}
