<?php
/*
 * Name: ytprox
 * Description: Proxied YouTube thumbnails and metadata for Friendica
 * Version: 0.4 dev
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function ytprox_install()
{
    // Statt prepare_body_final jetzt direkt beim Parsen eingreifen
    Hook::register('parse_link', __FILE__, 'ytprox_parse_link');
    Hook::register('addon_settings', __FILE__, 'ytprox_settings');
    Hook::register('addon_settings_post', __FILE__, 'ytprox_settings_post');
}

/**
 * Addon-Einstellungen anzeigen
 */
function ytprox_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $enabled = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'ytprox', 'enabled', false);

    $t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/ytprox/');
    $html = Renderer::replaceMacros($t, [
        '$enabled' => [
            'enabled',
            DI::l10n()->t('Enable YouTube proxy preview'),
            $enabled,
            DI::l10n()->t('If enabled, YouTube previews (thumbnails and metadata) are fetched via this server.')
        ],
    ]);

    $data = [
        'addon' => 'ytprox',
        'title' => DI::l10n()->t('YouTube Proxy Settings'),
        'html'  => $html,
    ];
}

/**
 * Addon-Einstellungen speichern
 */
function ytprox_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['ytprox-submit'])) {
        return;
    }

    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'ytprox', 'enabled', (bool)$_POST['enabled']);
}

/**
 * Hook: parse_link – wird aufgerufen, wenn Friendica eine Vorschau für einen Link erstellt
 */
function ytprox_parse_link(array &$b)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid || !DI::pConfig()->get($uid, 'ytprox', 'enabled')) {
        return;
    }

    // Nur für YouTube-Links
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]{11})~i', $b['url'], $matches)) {
        $vid = $matches[1];

        $infoUrl  = DI::baseUrl() . "/addon/ytprox/proxy.php?type=info&vid=" . $vid;
        $thumbUrl = DI::baseUrl() . "/addon/ytprox/proxy.php?type=thumb&vid=" . $vid;

        // Hole die Metadaten über den Proxy
        $json = @file_get_contents($infoUrl);
        if ($json) {
            $meta = json_decode($json, true);
            if ($meta) {
                $b['title'] = $meta['title'] ?? 'YouTube Video';
                $b['text']  = $meta['author_name'] ?? '';
                $b['image'] = $thumbUrl;
                $b['url']   = "https://www.youtube.com/watch?v=" . $vid;
            }
        }
    }
}
