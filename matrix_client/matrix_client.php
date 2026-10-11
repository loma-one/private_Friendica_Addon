<?php
/**
 * Name: Matrix Client
 * Description: Bindet den lokal gehosteten Hydrogen Matrix-Client als Widget ein (Prototyp).
 * Version: 0.3
 * Author: Matthias Ebers
 */

use Friendica\Core\Hook;
use Friendica\DI;

function matrix_client_install()
{
    Hook::register('head', 'addon/matrix_client/matrix_client.php', 'matrix_client_head');
    Hook::register('footer', 'addon/matrix_client/matrix_client.php', 'matrix_client_footer');
    Hook::register('addon_settings', 'addon/matrix_client/matrix_client.php', 'matrix_client_settings');
    Hook::register('addon_settings_post', 'addon/matrix_client/matrix_client.php', 'matrix_client_settings_post');
}

function matrix_client_uninstall()
{
    Hook::unregister('head', 'addon/matrix_client/matrix_client.php', 'matrix_client_head');
    Hook::unregister('footer', 'addon/matrix_client/matrix_client.php', 'matrix_client_footer');
    Hook::unregister('addon_settings', 'addon/matrix_client/matrix_client.php', 'matrix_client_settings');
    Hook::unregister('addon_settings_post', 'addon/matrix_client/matrix_client.php', 'matrix_client_settings_post');
}

function matrix_client_head(&$b)
{
    if (!matrix_client_is_enabled()) {
        return;
    }

    DI::page()->registerStylesheet(__DIR__ . '/css/widget.css');
}

function matrix_client_footer(&$b)
{
    if (!matrix_client_is_enabled()) {
        return;
    }

    DI::page()->registerFooterScript(__DIR__ . '/matrix_client.js');
}

function matrix_client_is_enabled(): bool
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId) {
        return false;
    }

    return (int) DI::pConfig()->get($userId, 'matrix_client', 'enabled') === 1;
}

function matrix_client_settings(array &$data)
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId) {
        return;
    }

    $enabled = (int) DI::pConfig()->get($userId, 'matrix_client', 'enabled');

    $template = \Friendica\Core\Renderer::getMarkupTemplate('settings.tpl', 'addon/matrix_client/');
    $html = \Friendica\Core\Renderer::replaceMacros($template, [
        '$enabled'     => ['matrix_enabled', DI::l10n()->t('Matrix-Client Widget aktivieren'), $enabled, ''],
        '$title'       => DI::l10n()->t('Matrix-Client - Prototyp (Hydrogen)'),
        '$description' => DI::l10n()->t('Aktiviert ein Chat-Fenster am unteren Bildschirmrand. Der Login erfolgt direkt im Client.'),
    ]);

    $data = [
        'addon' => 'matrix_client',
        'title' => DI::l10n()->t('Matrix Client Einstellungen'),
        'html'  => $html,
    ];
}

function matrix_client_settings_post($post)
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId || empty($_POST['matrix_client-submit'])) {
        return;
    }

    DI::pConfig()->set($userId, 'matrix_client', 'enabled', !empty($_POST['matrix_enabled']) ? 1 : 0);
}
