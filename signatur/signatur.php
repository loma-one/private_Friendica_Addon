<?php
/*
 * Name: signatur
 * Description: Automatically adds a signature to new posts. Admins can define a default signature, and users can configure their own.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * Status: Beta
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function signatur_install()
{
    Hook::register('post_local', __FILE__, 'signatur_add_signature');
    Hook::register('addon_settings', __FILE__, 'signatur_user_settings');
    Hook::register('addon_settings_post', __FILE__, 'signatur_user_settings_post');
    Hook::register('addon_admin', __FILE__, 'signatur_admin_settings');
    Hook::register('addon_admin_post', __FILE__, 'signatur_admin_settings_post');
}

/**
 * Adds a signature to new posts.
 *
 * @param array &$b Post data.
 */
function signatur_add_signature(array &$b)
{
    if (!$b['uid']) {
        return;
    }

    $enabled = DI::pConfig()->get($b['uid'], 'signatur', 'enabled', false);
    if (!$enabled) {
        return;
    }

    $signature = DI::pConfig()->get($b['uid'], 'signatur', 'text') ??
                 DI::config()->get('signatur', 'default_text', "---\nDefault Signature");

    if (!empty($b['body'])) {
        $b['body'] .= "\n\n" . $signature;
    }
}

/**
 * User settings for the app.
 *
 * @param array &$data Settings data for rendering.
 */
function signatur_user_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $uid = DI::userSession()->getLocalUserId();
    $enabled = DI::pConfig()->get($uid, 'signatur', 'enabled', false);
    $signature = DI::pConfig()->get($uid, 'signatur', 'text', '');

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/signatur/');
    $html = Renderer::replaceMacros($t, [
        '$description' => DI::l10n()->t('Add a signature to your posts.'),
        '$enabled'     => ['enabled', DI::l10n()->t('Enable Signature'), $enabled],
        '$signature'   => ['text', DI::l10n()->t('Your Signature'), $signature, DI::l10n()->t('Enter your custom signature. (Multiline allowed)')],
        '$submit'      => DI::l10n()->t('Save'),
    ]);

    $data = [
        'addon' => 'signatur',
        'title' => DI::l10n()->t('Signature Settings'),
        'html'  => $html,
    ];
}

/**
 * Saves user settings.
 *
 * @param array &$b Submitted data.
 */
function signatur_user_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['signatur-submit'])) {
        return;
    }

    $uid = DI::userSession()->getLocalUserId();
    $enabled = !empty($_POST['enabled']);
    $signature = trim($_POST['text']);

    DI::pConfig()->set($uid, 'signatur', 'enabled', $enabled);
    DI::pConfig()->set($uid, 'signatur', 'text', $signature);
}

/**
 * Admin settings for the app.
 *
 * @param string &$o Output string for rendering.
 */
function signatur_admin_settings(string &$o)
{
    $default_text = DI::config()->get('signatur', 'default_text', "---\nDefault Admin Signature");

    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/signatur/');
    $o = Renderer::replaceMacros($t, [
        '$description' => DI::l10n()->t('Set the default signature for users who have not defined their own.'),
        '$default_text' => ['default_text', DI::l10n()->t('Default Signature'), $default_text, DI::l10n()->t('This will be used if users do not set their own signature.')],
        '$submit'      => DI::l10n()->t('Save'),
    ]);
}

/**
 * Saves admin settings.
 */
function signatur_admin_settings_post()
{
    if (empty($_POST['signatur-admin-submit'])) {
        return;
    }

    $default_text = trim($_POST['default_text']);
    DI::config()->set('signatur', 'default_text', $default_text);
}
