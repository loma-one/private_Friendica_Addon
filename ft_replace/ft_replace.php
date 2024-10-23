<?php
/**
 * Name: 12ft Replace
 * Description: Replaces occurrences of specified URLs with the address of alternative servers in all displays of postings on a node.
 * Version: 1.2
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
    Hook::register('addon_settings', __FILE__, 'ft_replace_settings');
    Hook::register('addon_settings_post', __FILE__, 'ft_replace_settings_post');
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
/**
 * Display settings for the user to enable/disable the addon
 */
function ft_replace_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    // Current status (enabled or disabled) for the user
    $enabled = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'ft_replace', 'enabled', false);

    // Get template for settings
    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/ft_replace/');

    // Ensure the HTML content for the settings is defined
    $html = Renderer::replaceMacros($t, [
        '$enabled' => [
            'enabled',
            DI::l10n()->t('Enable 12ft Replace Addon'),
            $enabled,
            DI::l10n()->t('If enabled, specific URLs will be replaced with 12ft.io URLs.')
        ],
        // Include a submit button for the form
        '$submit' => DI::l10n()->t('Save Settings'),
    ]);

    // Return settings
    $data = [
        'addon' => 'ft_replace',
        'title' => DI::l10n()->t('12ft Replace Settings'),
        'html'  => $html,
    ];
}

/**
 * Handle post data from user settings
 */
function ft_replace_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['ft_replace-submit'])) {
        return;
    }

    // Save the checkbox value (true or false)
    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'ft_replace', 'enabled', (bool)$_POST['enabled']);
}

/**
 * Replace proprietary URLs with their specified counterpart
 */
function ft_replace_render(array &$b)
{
    // Check if the addon is enabled for the user
    if (!DI::userSession()->getLocalUserId() || !DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'ft_replace', 'enabled')) {
        return;
    }

    $twelvefeet_sites = DI::config()->get('ft_replace', 'twelvefeet_sites') ?? [] ?: [];
    $replaced = false;

    foreach ($twelvefeet_sites as $twelvefeet_site) {
        if (strpos($b['html'], $twelvefeet_site) !== false) {
            $b['html'] = str_replace($twelvefeet_site, 'https://12ft.io/' . $twelvefeet_site, $b['html']);
            $replaced = true;
        }
    }

    if ($replaced) {
        $b['html'] .= '<hr><p><small>' . DI::l10n()->t('(12ft replace addon enabled for some news sites.)') . '</small></p>';
    }
}
