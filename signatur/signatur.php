<?php
/**
 * Name: signatur
 * Description: Automatically adds a signature to new posts. Admins can define a default signature, and users can configure their own. Can be toggled via the editor.
 * Version: 1.9
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * Status: Beta
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

// Use Zero Width Space (ZWSP) as the signature marker
const SIGNATURE_MARKER = "\u{200B}";

function signatur_install()
{
    Hook::register('post_local', __FILE__, 'signatur_add_signature');
    Hook::register('jot_networks', __FILE__, 'signatur_jot_nets');
    Hook::register('addon_settings', __FILE__, 'signatur_user_settings');
    Hook::register('addon_settings_post', __FILE__, 'signatur_user_settings_post');
}

function signatur_uninstall()
{
    Hook::unregister('post_local', __FILE__, 'signatur_add_signature');
    Hook::unregister('jot_networks', __FILE__, 'signatur_jot_nets');
    Hook::unregister('addon_settings', __FILE__, 'signatur_user_settings');
    Hook::unregister('addon_settings_post', __FILE__, 'signatur_user_settings_post');
}

/**
 * Adds the checkbox to the editor (Jot permissions/connectors)
 *
 * @param array &$jotnets_fields Editor fields.
 */
function signatur_jot_nets(array &$jotnets_fields)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    // Präzise Abfrage: Nur anzeigen, wenn das Addon in den Einstellungen aktiv gesetzt wurde
    $enabled = DI::pConfig()->get($uid, 'signatur', 'enabled');

    if ($enabled) {
        $jotnets_fields[] = [
            'type' => 'checkbox',
            'field' => [
                'signatur_enable',
                DI::l10n()->t('Signatur anfügen'),
                DI::pConfig()->get($uid, 'signatur', 'by_default', false)
            ]
        ];
    }
}

/**
 * Adds a signature to new posts.
 *
 * @param array &$b Post data.
 */
function signatur_add_signature(array &$b)
{
    if (empty($b['uid'])) {
        return;
    }

    $enabled = DI::pConfig()->get($b['uid'], 'signatur', 'enabled', false);
    if (!$enabled) {
        return;
    }

    // Check if enabled via editor checkbox
    $sig_enable = (!empty($_REQUEST['signatur_enable']) ? intval($_REQUEST['signatur_enable']) : 0);

    // API posts or fallback to "by_default" setting
    if ($b['api_source'] && DI::pConfig()->get($b['uid'], 'signatur', 'by_default')) {
        $sig_enable = 1;
    }

    // Falls es kein Kommentar ist UND die Editor-Checkbox nicht aktiv ist -> abbrechen
    $isComment = !empty($b['parent']) && ($b['parent'] != ($b['uri-id'] ?? null));
    if (!$isComment && !$sig_enable) {
        return;
    }

    // Comments have their own independent setting rule
    $enableSignatureInComments = DI::pConfig()->get($b['uid'], 'signatur', 'enable_signature_in_comments', true);
    if ($isComment && !$enableSignatureInComments) {
        return;
    }

    $signature = DI::pConfig()->get($b['uid'], 'signatur', 'text', '');
    if (empty($signature) || strpos($b['body'], SIGNATURE_MARKER) !== false) {
        return;
    }

    if (!empty($b['body'])) {
        $b['body'] = insert_signature_before_images($b['body'], SIGNATURE_MARKER, $signature);
    }
}

/**
 * Inserts the signature based on the content structure.
 *
 * @param string $body The post body.
 * @param string $signature_marker The signature marker.
 * @param string $signature The signature text.
 * @return string The modified post body.
 */
function insert_signature_before_images($body, $signature_marker, $signature)
{
    $lines = explode("\n", $body);
    $image_count = 0;
    $text_end_index = -1;

    foreach ($lines as $index => $line) {
        if (strpos($line, '[url=') !== false) {
            $image_count++;
            if ($image_count === 1 && $text_end_index === -1) {
                $text_end_index = $index;
            }
        }
    }

    if ($image_count <= 1) {
        return $body . "\n\n{$signature_marker}\n{$signature}";
    }

    if ($image_count >= 2 && $text_end_index !== -1) {
        $lines_before_first_image = array_slice($lines, 0, $text_end_index);
        $lines_after_first_image = array_slice($lines, $text_end_index);

        return implode("\n", $lines_before_first_image) .
            "\n\n{$signature_marker}\n{$signature}\n\n" .
            implode("\n", $lines_after_first_image);
    }

    return $body . "\n\n{$signature_marker}\n{$signature}";
}

/**
 * User settings for the app.
 *
 * @param array &$data Settings data for rendering.
 */
function signatur_user_settings(array &$data)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    $enabled = DI::pConfig()->get($uid, 'signatur', 'enabled', false);
    $by_default = DI::pConfig()->get($uid, 'signatur', 'by_default', false);
    $signature = DI::pConfig()->get($uid, 'signatur', 'text', '');
    $enable_signature_in_comments = DI::pConfig()->get($uid, 'signatur', 'enable_signature_in_comments', true);

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/signatur/');
    $html = Renderer::replaceMacros($t, [
        '$description' => DI::l10n()->t('BETA Version - Add a signature to your posts. This addon automatically appends a customizable signature to posts in Friendica. Users can enable or disable it, define personal signatures, and optionally include them in comments.'),
        '$enabled' => ['enabled', DI::l10n()->t('Enable Signature'), $enabled],
        '$by_default' => ['by_default', DI::l10n()->t('Signatur im Editor standardmäßig aktivieren'), $by_default],
        '$signature' => ['text', DI::l10n()->t('Your Signature'), $signature, DI::l10n()->t('Enter your custom signature. (Multiline allowed)')],
        '$enable_signature_in_comments' => ['enable_signature_in_comments', DI::l10n()->t('Enable Signature in Comments'), $enable_signature_in_comments],
        '$submit' => DI::l10n()->t('Save'),
    ]);

    $data = [
        'addon' => 'signatur',
        'title' => DI::l10n()->t('Signature Settings'),
        'html' => $html,
    ];
}

/**
 * Saves user settings.
 *
 * @param array &$b Submitted data.
 */
function signatur_user_settings_post(array &$b)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid || empty($_POST['signatur-submit'])) {
        return;
    }

    DI::pConfig()->set($uid, 'signatur', 'enabled', !empty($_POST['enabled']));
    DI::pConfig()->set($uid, 'signatur', 'by_default', !empty($_POST['by_default']));
    DI::pConfig()->set($uid, 'signatur', 'text', trim($_POST['text']));
    DI::pConfig()->set($uid, 'signatur', 'enable_signature_in_comments', !empty($_POST['enable_signature_in_comments']));
}
