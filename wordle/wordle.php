<?php
/*
 * Name: Wordle Application
 * Description: add an Wordle game. Based on webRTC Addon
 * Version: 0.2
 * Author: Stephen Mahood <https://friends.mayfirst.org/profile/marxistvegan>
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function wordle_install()
{
	Hook::register('app_menu', __FILE__, 'wordle_app_menu');
}

function wordle_app_menu(array &$b)
{
	$b['app_menu'][] = '<div class="app-title"><a href="wordle">' . DI::l10n()->t('Game: Wordle') . '</a></div>';
}

function wordle_addon_admin(string &$o)
{
	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/wordle/');
	$o = Renderer::replaceMacros($t, [
		'$submit' => DI::l10n()->t('Save Settings'),
		'$wordleurl' => [
			'wordleurl',
			DI::l10n()->t('wordle Base URL'),
			DI::config()->get('wordle', 'wordleurl'),
			DI::l10n()->t('Page your users will create an wordle game. For example, you could use https://swilliamsio.itch.io/valyrian-wordle.'),
		],
	]);
}

function wordle_addon_admin_post()
{
	DI::config()->set('wordle', 'wordleurl', trim($_POST['wordleurl'] ?? ''));
}

/**
 * This is a statement rather than an actual function definition. The simple
 * existence of this method is checked to figure out if the addon offers a
 * module.
 */
function wordle_module()
{
}

function wordle_content(): string
{
	$o = '';

	/* landing page to create chatrooms */
	$wordleurl = DI::config()->get('wordle', 'wordleurl');

	/* open the landing page in a new browser window without controls */
	$o = '<script>
				window.open("' . $wordleurl . '", "_blank", "toolbar=no,scrollbars=no,resizable=no,top=100,left=100,width=1040,height=680");
			</script>';

	$o .= '<h2>' . DI::l10n()->t('Wordle Game') . '</h2>';
	$o .= '<p>' . DI::l10n()->t('Valyrian Wordle is a re-implementation of the modern classic Wordle game into the constructed language of High Valyrian') . '</p>';
	if ($wordleurl == '') {
		$o .= '<p>' . DI::l10n()->t('Please contact your Friendica administrator to remind them to configure the wordle addon.') . '</p>';
	}

	return $o;
}
