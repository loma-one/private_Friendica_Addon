<?php
/**
 * Name: smry Replace
 * Description: Replaces URLs with SMRY.ai links, using AI to summarize articles and bypass paywalls via archive.org, googlebot, and archive.is.
 * Version: 1.2
 * Author: Dr. Tobias Quathamer <https://social.anoxinon.de/@toddy>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * Maintainer: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function smry_replace_install()
{
    Hook::register('prepare_body_final', 'addon/smry_replace/smry_replace.php', 'smry_replace_render');
    Hook::register('addon_settings', __FILE__, 'smry_replace_settings');
    Hook::register('addon_settings_post', __FILE__, 'smry_replace_settings_post');
}

/**
 * Handle sent data from admin settings
 */
function smry_replace_addon_admin_post()
{
    $smry_sites = explode(PHP_EOL, $_POST['smry_sites']);
    // Normalize URLs by using lower case, removing a trailing slash and whitespace
    $smry_sites = array_map(fn($value): string => rtrim(trim(strtolower($value)), '/'), $smry_sites);
    // Do not store empty lines or duplicates
    $smry_sites = array_filter($smry_sites, fn($value): bool => !empty($value));
    $smry_sites = array_unique($smry_sites);
    // Ensure a protocol and default to HTTPS
    $smry_sites = array_map(
        fn($value): string => substr($value, 0, 4) !== 'http' ? 'https://' . $value : $value,
        $smry_sites
    );
    asort($smry_sites);
    DI::config()->set('smry_replace', 'smry_sites', $smry_sites);
}

/**
 * Hook into admin settings to enable choosing a different server
 * for twitter, youtube, and news sites.
 */
function smry_replace_addon_admin(string &$o)
{
    $smry_sites = implode(PHP_EOL, DI::config()->get('smry_replace', 'smry_sites') ?? [] ?: []);
    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/smry_replace/');
    $o = Renderer::replaceMacros($t, [
        '$smry_sites' => [
            'smry_sites',
            DI::l10n()->t('Sites which are accessed through smry.ai'),
            $smry_sites,
            DI::l10n()->t('Specify the URLs with protocol, one per line.'),
            null,
            'rows="6"'
        ],
        '$submit' => DI::l10n()->t('Save settings'),
    ]);
}

/**
 * Display settings for the user to enable/disable the addon
 */
function smry_replace_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }
    // Current status (enabled or disabled) for the user
    $enabled = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'smry_replace', 'enabled', false);
    // Get template for settings
    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/smry_replace/');
    // Ensure the HTML content for the settings is defined
    $html = Renderer::replaceMacros($t, [
        '$enabled' => [
            'enabled',
            DI::l10n()->t('Enable smry Replace Addon'),
            $enabled,
            DI::l10n()->t('If enabled, specific URLs will be replaced with smry.ai URLs. Uses AI to summarise articles and bypass paywalls via archive.org, googlebot and archive.is.')
        ],
        // Include a submit button for the form
        '$submit' => DI::l10n()->t('Save Settings'),
    ]);
    // Return settings
    $data = [
        'addon' => 'smry_replace',
        'title' => DI::l10n()->t('smry Replace Settings'),
        'html'  => $html,
    ];
}

/**
 * Handle post data from user settings
 */
function smry_replace_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['smry_replace-submit'])) {
        return;
    }
    // Save the checkbox value (true or false)
    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'smry_replace', 'enabled', (bool)$_POST['enabled']);
}

/**
 * Replace proprietary URLs with their specified counterpart
 */
function smry_replace_render(array &$b)
{
    // Check if the addon is enabled for the user
    if (!DI::userSession()->getLocalUserId() || !DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'smry_replace', 'enabled')) {
        return;
    }
    $smry_sites = DI::config()->get('smry_replace', 'smry_sites') ?? [] ?: [];
    $replaced = false;
    foreach ($smry_sites as $smry_site) {
        if (strpos($b['html'], $smry_site) !== false) {
            $b['html'] = str_replace($smry_site, 'https://smry.ai/' . $smry_site, $b['html']);
            $replaced = true;
        }
    }
    if ($replaced) {
        $b['html'] .= '<hr><p><small>' . DI::l10n()->t('(smry replace addon enabled for some news sites.)') . '</small></p>';
    }
}

