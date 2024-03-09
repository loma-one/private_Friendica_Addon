<?php
/*
 * Name: Nodulus Application
 * Description: add an Nodulus game. Based on webRTC Addon
 * Version: 0.2
 * Author: Stephen Mahood <https://friends.mayfirst.org/profile/marxistvegan>
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function nodulus_install()
{
	Hook::register('app_menu', __FILE__, 'nodulus_app_menu');
}

function nodulus_app_menu(array &$b)
{
	$b['app_menu'][] = '<div class="app-title"><a href="nodulus">' . DI::l10n()->t('Game: Nodulus') . '</a></div>';
}

function nodulus_addon_admin(string &$o)
{
	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/nodulus/');
	$o = Renderer::replaceMacros($t, [
		'$submit' => DI::l10n()->t('Save Settings'),
		'$nodulusurl' => [
			'nodulusurl',
			DI::l10n()->t('nodulus Base URL'),
			DI::config()->get('nodulus', 'nodulusurl'),
			DI::l10n()->t('Page your users will create an Nodulus game. For example, you could use https://hyperparticle.itch.io/nodulus.'),
		],
	]);
}

function nodulus_addon_admin_post()
{
	DI::config()->set('nodulus', 'nodulusurl', trim($_POST['nodulusurl'] ?? ''));
}

/**
 * This is a statement rather than an actual function definition. The simple
 * existence of this method is checked to figure out if the addon offers a
 * module.
 */
function nodulus_module()
{
}

function nodulus_content(): string
{
	$o = '';

	/* landing page to create chatrooms */
	$nodulusurl = DI::config()->get('nodulus', 'nodulusurl');

	/* open the landing page in a new browser window without controls */
	$o = '<script>
				window.open("' . $nodulusurl . '", "_blank", "toolbar=no,scrollbars=no,resizable=no,top=100,left=100,width=1040,height=680");
			</script>';

	$o .= '<h2>' . DI::l10n()->t('Nodulus Game') . '</h2>';
	$o .= '<p>' . DI::l10n()->t('Nodulus is a puzzle game with a clever twist. Based on the mathematical theory behind plank puzzles, consists of a grid of cubes and rods which can be rotated with a swipe.') . '</p>';
	if ($nodulusurl == '') {
		$o .= '<p>' . DI::l10n()->t('Please contact your Friendica administrator to remind them to configure the nodulus addon.') . '</p>';
	}

	return $o;
}
