<?php
/*
 * Name:  Matrix Application
 * Description: add an Matrix Window. Based on webRTC Addon
 * Version: 0.1
 * Author: Stephen Mahood <https://friends.mayfirst.org/profile/marxistvegan>
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function matrix_install()
{
		Hook::register('app_menu', __FILE__, 'matrix_app_menu');
}

function matrix_app_menu(array &$b)
{
		$b['app_menu'][] = '<div class="app-title"><a href="matrix">' . DI::l10n()->t('Matrix Chat') . '</a></div>';
}

function matrix_addon_admin(string &$o)
{
		$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/matrix/');
		$o = Renderer::replaceMacros($t, [
			'$submit' => DI::l10n()->t('Save Settings'),
			'$matrixurl' => [
				'matrixurl',
				DI::l10n()->t('Matrix Base URL'),
				DI::config()->get('matrix', 'matrixurl'),
				DI::l10n()->t('Page your users will create an matrix chat. For example, you could use https://app.cinny.in.'),
			],
		]);
}

function matrix_addon_admin_post()
{
		DI::config()->set('matrix', 'matrixurl', trim($_POST['matrixurl'] ?? ''));
}

/**
 * This is a statement rather than an actual function definition. The simple
 * existence of this method is checked to figure out if the addon offers a
 * module.
 */
function matrix_module()
{
}

function matrix_content(): string
{
		$o = '';

		/* landing page to create chatrooms */
		$matrixurl = DI::config()->get('matrix', 'matrixurl');

		/* open the landing page in a new browser window without controls */
		$o = '<script>
					window.open("' . $matrixurl . '", "_blank", "toolbar=no,scrollbars=no,resizable=no,top=120,left=120,width=540,height=880");
				</script>';

		$o .= '<h2>' . DI::l10n()->t('Matrix Chat') . '</h2>';
		$o .= '<p>' . DI::l10n()->t('Matrix is an Chat tool. Connect your account to matrix and Chat with other People. ') . '</p>';
		if ($matrixurl == '') {
			$o .= '<p>' . DI::l10n()->t('Please contact your Friendica administrator to remind them to configure the Matrix addon.') . '</p>';
		}

		return $o;
}
