<?php
/*
 * Name:  XMPP Application
 * Description: add an XMPP Window. Based on webRTC Addon
 * Version: 0.1
 * Author: Stephen Mahood <https://friends.mayfirst.org/profile/marxistvegan>
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function xmpp_install()
{
		Hook::register('app_menu', __FILE__, 'xmpp_app_menu');
}

function xmpp_app_menu(array &$b)
{
		$b['app_menu'][] = '<div class="app-title"><a href="xmpp">' . DI::l10n()->t('XMPP Chat') . '</a></div>';
}

function xmpp_addon_admin(string &$o)
{
		$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/xmpp/');
		$o = Renderer::replaceMacros($t, [
			'$submit' => DI::l10n()->t('Save Settings'),
			'$xmppurl' => [
				'xmppurl',
				DI::l10n()->t('xmpp Base URL'),
				DI::config()->get('xmpp', 'xmppurl'),
				DI::l10n()->t('Page your users will create an XMPP chat. For example, you could use https://conversejs.org/fullscreen.html.'),
			],
		]);
}

function xmpp_addon_admin_post()
{
		DI::config()->set('xmpp', 'xmppurl', trim($_POST['xmppurl'] ?? ''));
}

/**
 * This is a statement rather than an actual function definition. The simple
 * existence of this method is checked to figure out if the addon offers a
 * module.
 */
function xmpp_module()
{
}

function xmpp_content(): string
{
		$o = '';

		/* landing page to create chatrooms */
		$xmppurl = DI::config()->get('xmpp', 'xmppurl');

		/* open the landing page in a new browser window without controls */
		$o = '<script>
					window.open("' . $xmppurl . '", "_blank", "toolbar=no,scrollbars=no,resizable=no,top=120,left=120,width=540,height=880");
				</script>';

		$o .= '<h2>' . DI::l10n()->t('XMPP Chat') . '</h2>';
		$o .= '<p>' . DI::l10n()->t('XMPP is an Chat tool. Connect your account to XMPP and Chat with other People. ') . '</p>';
		if ($xmppurl == '') {
			$o .= '<p>' . DI::l10n()->t('Please contact your Friendica administrator to remind them to configure the xmpp addon.') . '</p>';
		}

		return $o;
}
