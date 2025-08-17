<?php
/*
 * Name: ytprox
 * Description: Proxied YouTube thumbnails and metadata for Friendica
 * Version: 0.1 dev
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function ytprox_install()
{
    Hook::register('prepare_body_final', __FILE__, 'ytprox_render');
    Hook::register('addon_settings', __FILE__, 'ytprox_settings');
    Hook::register('addon_settings_post', __FILE__, 'ytprox_settings_post');
}

/**
 * Anzeige der User-Einstellungen
 */
function ytprox_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $enabled = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'ytprox', 'enabled', false);

    $t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/ytprox/');
    $html = Renderer::replaceMacros($t, [
        '$enabled' => ['enabled', DI::l10n()->t('Enable YouTube proxy preview'), $enabled, DI::l10n()->t('If enabled, YouTube links are proxied through the server (thumbnails and metadata).')],
    ]);

    $data = [
        'addon' => 'ytprox',
        'title' => DI::l10n()->t('YouTube Proxy Settings'),
        'html'  => $html,
    ];
}

/**
 * Speichern der User-Einstellungen
 */
function ytprox_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['ytprox-submit'])) {
        return;
    }

    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'ytprox', 'enabled', (bool)$_POST['enabled']);
}

/**
 * Ersetzen von YouTube-Links durch Proxy-Preview
 */
function ytprox_render(array &$b)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid || !DI::pConfig()->get($uid, 'ytprox', 'enabled')) {
        return;
    }

    $original = $b['html'];

    $b['html'] = preg_replace_callback(
        "~https?://(?:www\.|m\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]{11})~i",
        function ($matches) {
            $vid = $matches[1];
            $thumbUrl = DI::baseUrl() . "/addon/ytprox/proxy.php?type=thumb&vid=" . $vid;
            $infoUrl  = DI::baseUrl() . "/addon/ytprox/proxy.php?type=info&vid=" . $vid;

            return '<div class="ytprox-card">'
                . '<a href="https://www.youtube.com/watch?v=' . $vid . '" target="_blank" rel="nofollow noopener">'
                . '<img src="' . $thumbUrl . '" alt="YouTube Thumbnail" style="max-width:100%; border-radius:8px;">'
                . '</a>'
                . '<div class="ytprox-meta" data-src="' . $infoUrl . '"></div>'
                . '</div>';
        },
        $b['html']
    );

    if ($original != $b['html']) {
        $b['html'] .= '<hr><p><small class="ytprox-note">(YouTube proxied via ytprox addon)</small></p>';
    }
}
