<?php
/**
 * Name: 12ft Replace
 * Description: Replaces occurrences of specified URLs with the address of alternative servers in all displays of postings on a node.
 * Version: 1.1
 * Author: Dr. Tobias Quathamer <https://social.anoxinon.de/@toddy>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * Maintainer: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function ft_replace_install()
{
	Hook::register('prepare_body_final', 'addon/ft_replace/ft_replace.php', 'ft_replace_render');
}

/**
 * Handle sent data from admin settings
 */
function ft_replace_addon_admin_post()
{
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
	DI::config()->set('ft_replace', 'twelvefeet_sites', $twelvefeet_sites);
}

/**
 * Hook into admin settings to enable choosing a different server
 * for twitter, youtube, and news sites.
 */
function ft_replace_addon_admin(string &$o)
{
	$twelvefeet_sites = implode(PHP_EOL, DI::config()->get('ft_replace', 'twelvefeet_sites') ?? [] ?: []);

	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/ft_replace/');
	$o = Renderer::replaceMacros($t, [
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
function ft_replace_render(array &$b)
{
	$twelvefeet_sites = DI::config()->get('ft_replace', 'twelvefeet_sites') ?? [] ?: [];
	foreach ($twelvefeet_sites as $twelvefeet_site) {
		if (strpos($b['html'], $twelvefeet_site) !== false) {
			$b['html'] = str_replace($twelvefeet_site, 'https://12ft.io/' . $twelvefeet_site, $b['html']);
			$replaced  = true;
		}
	}


	if ($replaced) {
		$b['html'] .= '<hr><p><small>' . DI::l10n()->t('(12ft replace addon enabled for some news sites.)') . '</small></p>';
	}
}
