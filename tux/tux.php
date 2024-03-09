<?php
/*
 * Name: Tux Application
 * Description: add an Tux game. Based on webRTC Addon
 * Version: 0.2
 * Author: Stephen Mahood <https://friends.mayfirst.org/profile/marxistvegan>
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function tux_install()
{
	Hook::register('app_menu', __FILE__, 'tux_app_menu');
}

function tux_app_menu(array &$b)
{
	$b['app_menu'][] = '<div class="app-title"><a href="tux">' . DI::l10n()->t('Game: Super Tux') . '</a></div>';
}

function tux_addon_admin(string &$o)
{
	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/tux/');
	$o = Renderer::replaceMacros($t, [
		'$submit' => DI::l10n()->t('Save Settings'),
		'$tuxurl' => [
			'tuxurl',
			DI::l10n()->t('tux Base URL'),
			DI::config()->get('tux', 'tuxurl'),
			DI::l10n()->t('Page your users will create an tux game. For example, you could use https://alzter-s.itch.io/supertux-classic.'),
		],
	]);
}

function tux_addon_admin_post()
{
	DI::config()->set('tux', 'tuxurl', trim($_POST['tuxurl'] ?? ''));
}

/**
 * This is a statement rather than an actual function definition. The simple
 * existence of this method is checked to figure out if the addon offers a
 * module.
 */
function tux_module()
{
}

function tux_content(): string
{
	$o = '';

	/* landing page to create chatrooms */
	$tuxurl = DI::config()->get('tux', 'tuxurl');

	/* open the landing page in a new browser window without controls */
	$o = '<script>
				window.open("' . $tuxurl . '", "_blank", "toolbar=no,scrollbars=no,resizable=no,top=100,left=100,width=1040,height=680");
			</script>';

	$o .= '<h2>' . DI::l10n()->t('Super Tux Game') . '</h2>';
	$o .= '<p>' . DI::l10n()->t('SuperTux Classic is a 2D platformer game where where you play as a penguin in Antarctica!') . '</p>';
	if ($tuxurl == '') {
		$o .= '<p>' . DI::l10n()->t('Please contact your Friendica administrator to remind them to configure the tux addon.') . '</p>';
	}

	return $o;
}
