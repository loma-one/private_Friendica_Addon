<?php
/*
 * Name: signatur
 * Description: Automatically adds a signature to new posts. Admins can define a default signature, and users can configure their own.
 * Version: 1.5
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

    // Check if the signature feature is enabled for the user
    $enabled = DI::pConfig()->get($b['uid'], 'signatur', 'enabled', false);
    if (!$enabled) {
        return;
    }

    // Check if the post is a comment
    if ($b['parent'] && ($b['parent'] != ($b['uri-id'] ?? null))) {
        // Check if the signature should be added to comments
        $enable_signature_in_comments = DI::pConfig()->get($b['uid'], 'signatur', 'enable_signature_in_comments', true);
        if (!$enable_signature_in_comments) {
            return;
        }
    }

    // Get the user's custom signature or the admin default
    $signature = DI::pConfig()->get($b['uid'], 'signatur', 'text') ??
                 DI::config()->get('signatur', 'default_text', "---\nDefault Signature");

    // Define the marker as [hr] (horizontal rule)
    $signature_marker = "[hr]";

    // Check if the marker is already present in the post body
    if (strpos($b['body'], $signature_marker) !== false) {
        return; // Signature already exists, do not add it again
    }

    // Check if the original content has changed
    if (isset($b['original_body']) && $b['original_body'] != $b['body']) {
        // Append the signature with the [hr] marker
        if (!empty($b['body'])) {
            $b['body'] = insert_signature_before_images($b['body'], $signature_marker, $signature);
        }
    } else {
        // If original_body is not set, assume it's a new post and add the signature
        if (!empty($b['body'])) {
            $b['body'] = insert_signature_before_images($b['body'], $signature_marker, $signature);
        }
    }
}

/**
 * Inserts the signature before images in the post body.
 *
 * @param string $body The post body.
 * @param string $signature_marker The signature marker.
 * @param string $signature The signature text.
 * @return string The modified post body.
 */
function insert_signature_before_images($body, $signature_marker, $signature)
{
    // Split the body into parts based on the image marker
    $parts = preg_split('/(\[url=.*?\])/', $body, -1, PREG_SPLIT_DELIM_CAPTURE);

    // Initialize the new body
    $new_body = '';

    // Flag to check if the signature has been added
    $signature_added = false;

    // Iterate through the parts
    foreach ($parts as $part) {
        // Check if the part is an image marker
        if (strpos($part, '[url=') !== false) {
            // If the signature has not been added yet, add it before the image
            if (!$signature_added) {
                $new_body .= "\n\n{$signature_marker}\n{$signature}\n\n";
                $signature_added = true;
            }
        }
        // Append the part to the new body
        $new_body .= $part;
    }

    // If no images were found, add the signature at the end
    if (!$signature_added) {
        $new_body .= "\n\n{$signature_marker}\n{$signature}";
    }

    return $new_body;
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
    $enable_signature_in_comments = DI::pConfig()->get($uid, 'signatur', 'enable_signature_in_comments', true);

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/signatur/');
    $html = Renderer::replaceMacros($t, [
        '$description' => DI::l10n()->t('Add a signature to your posts. This addon automatically appends a customizable signature to posts in Friendica. Users can enable or disable it, define personal signatures, and optionally include them in comments.'),
        '$enabled'     => ['enabled', DI::l10n()->t('Enable Signature'), $enabled],
        '$signature'   => ['text', DI::l10n()->t('Your Signature'), $signature, DI::l10n()->t('Enter your custom signature. (Multiline allowed)')],
        '$enable_signature_in_comments' => ['enable_signature_in_comments', DI::l10n()->t('Enable Signature in Comments'), $enable_signature_in_comments],
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
    $enable_signature_in_comments = !empty($_POST['enable_signature_in_comments']);

    DI::pConfig()->set($uid, 'signatur', 'enabled', $enabled);
    DI::pConfig()->set($uid, 'signatur', 'text', $signature);
    DI::pConfig()->set($uid, 'signatur', 'enable_signature_in_comments', $enable_signature_in_comments);
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
