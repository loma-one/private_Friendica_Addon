<?php
/*
 * Name: podcast Application
 * Description: add a Podcast Website. Based on webRTC Addon
 * Version: 0.1
 * Author: Stephen Mahood <https://friends.mayfirst.org/profile/marxistvegan>
 * Author: Tobias Diekershoff <https://f.diekershoff.de/profile/tobias>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function podcast_install()
{
	Hook::register('app_menu', __FILE__, 'podcast_app_menu');
}

function podcast_app_menu(array &$b)
{
	$b['app_menu'][] = '<div class="app-title"><a href="podcast">' . DI::l10n()->t('podcast Podcast') . '</a></div>';
}

function podcast_addon_admin(string &$o)
{
	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/podcast/');
	$o = Renderer::replaceMacros($t, [
		'$submit'   => DI::l10n()->t('Save Settings'),
		'$podcasturl' => [
			'podcasturl',
			DI::l10n()->t('podcast Base URL'),
			DI::config()->get('podcast','podcasturl'),
			DI::l10n()->t('Page your users will create an Podcast Website. For example you could use https://podcast.fm or https://podcastaddict.com.'),
		],
	]);
}

function podcast_addon_admin_post()
{
	DI::config()->set('podcast', 'podcasturl', trim($_POST['podcasturl'] ?? ''));
}

/**
 * This is a statement rather than an actual function definition. The simple
 * existence of this method is checked to figure out if the addon offers a
 * module.
 */
function podcast_module() {}

function podcast_content(): string
{
	$o = '';

	/* landingpage to create chatrooms */
	$podcasturl = DI::config()->get('podcast', 'podcasturl');

		/* open the landing page in a new browser window without controls */
		$o = '<script>
					window.open("' . $podcasturl . '", "_blank", "toolbar=no,scrollbars=no,resizable=no,top=100,left=100,width=840,height=850");
				</script>';

	/* embedd the landing page in an iframe */
	$o .= '<h2>' . DI::l10n()->t('Podcast') . '</h2>';
	$o .= '<p>' . DI::l10n()->t('podcast is a tool for listening to podcasts. Select the podcast that suits you best.') . '</p>';
	if ($podcasturl == '') {
		$o .= '<p>' . DI::l10n()->t('Please contact your Friendica administrator to remind them to configure the podcast addon.') . '</p>';
	}

	return $o;
}
