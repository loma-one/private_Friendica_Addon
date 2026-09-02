<?php
/**
 * Name: RadioPlayer
 * Description: (POC SPA Modus) Adds a web radio player above the timeline.
 * Version: 1.1
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function radioplayer_install()
{
    Hook::register('page_content_top', 'addon/radioplayer/radioplayer.php', 'radioplayer_page_header');
    Hook::register('addon_settings', 'addon/radioplayer/radioplayer.php', 'radioplayer_settings');
    Hook::register('addon_settings_post', 'addon/radioplayer/radioplayer.php', 'radioplayer_settings_post');
}

function radioplayer_uninstall()
{
    Hook::unregister('page_content_top', 'addon/radioplayer/radioplayer.php', 'radioplayer_page_header');
    Hook::unregister('addon_settings', 'addon/radioplayer/radioplayer.php', 'radioplayer_settings');
    Hook::unregister('addon_settings_post', 'addon/radioplayer/radioplayer.php', 'radioplayer_settings_post');
}

function radioplayer_settings(array &$data)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    $enabled = (bool) DI::pConfig()->get($uid, 'radioplayer', 'enable', false);

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/radioplayer');

    $html = Renderer::replaceMacros($t, [
        '$title'  => DI::l10n()->t('RadioPlayer Settings'),

        '$enable' => [
            'radioplayer_enable',
            DI::l10n()->t('Enable RadioPlayer'),
            $enabled,
            DI::l10n()->t('Enable a radio stream Player for Friendica. Important: SPA mode must be enabled for the player to work correctly.')
        ],
    ]);

    $data = [
        'addon' => 'radioplayer',
        'title' => DI::l10n()->t('RadioPlayer'),
        'html'  => $html,
    ];
}

function radioplayer_settings_post(array &$post)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    if (isset($_POST['radioplayer-submit'])) {
        $enable = !empty($_POST['radioplayer_enable']) ? 1 : 0;
        DI::pConfig()->set($uid, 'radioplayer', 'enable', $enable);
    }
}

function radioplayer_page_header(&$b)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    $enabled = (bool) DI::pConfig()->get($uid, 'radioplayer', 'enable', false);
    if (!$enabled) {
        return;
    }

    DI::page()->registerStylesheet('addon/radioplayer/radioplayer.css');

    $rawStreams = [
        ['name' => 'DLF Nova', 'url' => 'https://st03.dlf.de/dlf/03/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk', 'url' => 'https://st01.dlf.de/dlf/01/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk Kultur', 'url' => 'https://st02.dlf.de/dlf/02/128/mp3/stream.mp3'],
        ['name' => 'WDR2', 'url' => 'https://wdr-wdr2-rheinland.icecast.wdr.de/wdr/wdr2/rheinland/mp3/128/stream.mp3'],
        ['name' => 'WDR5', 'url' => 'https://wdr-wdr5-live.icecast.wdr.de/wdr/wdr5/live/mp3/128/stream.mp3'],
        ['name' => 'ORF Radio FM4', 'url' => 'https://orf-live.ors-shoutcast.at/fm4-q2a'],
        ['name' => '1LIVE', 'url' => 'https://wdr-1live-live.icecast.wdr.de/wdr/1live/live/mp3/128/stream.mp3'],
        ['name' => 'OE1', 'url' => 'https://orf-live.ors-shoutcast.at/oe1-q2a'],
        ['name' => 'SRF3', 'url' => 'https://stream.srg-ssr.ch/m/srf3/mp3_128'],
        ['name' => 'N-JOY', 'url' => 'https://icecast.ndr.de/ndr/njoy/live/mp3/128/stream.mp3'],
        ['name' => 'Radio FFN', 'url' => 'http://player.ffn.de/radioffn.mp3'],
        ['name' => 'Antenne Bayern', 'url' => 'https://stream.antenne.de/antenne/stream/mp3'],
        ['name' => 'France Inter', 'url' => 'https://icecast.radiofrance.fr/franceinter-midfi.mp3'],
        ['name' => 'France Culture', 'url' => 'https://icecast.radiofrance.fr/franceculture-midfi.mp3'],
        ['name' => 'FIP (France)', 'url' => 'https://icecast.radiofrance.fr/fip-midfi.mp3'],
        ['name' => 'KEXP 90.3 FM (Seattle, USA)', 'url' => 'https://kexp-mp3-128.streamguys1.com/kexp128.mp3'],
        ['name' => 'NPR Live Stream (USA)', 'url' => 'https://npr-ice.streamguys1.com/live.mp3'],
        ['name' => 'RAI Radio 3 (Italy)', 'url' => 'https://icestreaming.rai.it/3.mp3'],
        ['name' => '3FM (Netherlands)', 'url' => 'https://icecast.omroep.nl/3fm-bb-mp3'],
        ['name' => 'Radio Swiss Classic', 'url' => 'https://stream.srg-ssr.ch/m/rsj/mp3_128'],
        ['name' => 'BBC Radio 1 (UK)', 'url' => 'http://stream.live.vc.bbcmedia.co.uk/bbc_radio_one'],
        ['name' => 'Classic FM (UK)', 'url' => 'https://stream.mediauk.com/classicfm.mp3'],
        ['name' => 'Sveriges Radio P3 (Sweden)', 'url' => 'https://http-live.sr.se/p3-mp3-128'],
        ['name' => 'Radio Ibiza (Spain)', 'url' => 'https://stream.ibizaglobalradio.com/ibizaglobalradio.mp3'],
        ['name' => 'RTHK Radio 3 (Hong Kong)', 'url' => 'https://rthk.hk/live/radio3.mp3'],
        ['name' => 'Radio Paradise (Main)', 'url' => 'https://stream.radioparadise.com/aac-320'],
        ['name' => 'Radio Paradise (Mellow Mix)', 'url' => 'https://stream.radioparadise.com/mellow-320'],
        ['name' => 'Radio Paradise (Rock Mix)', 'url' => 'https://stream.radioparadise.com/rock-320']
    ];

    $streams = array_map(static function ($st) {
        return [
            'name' => htmlspecialchars($st['name'], ENT_QUOTES, 'UTF-8'),
            'url'  => htmlspecialchars($st['url'], ENT_QUOTES, 'UTF-8'),
        ];
    }, $rawStreams);

    $template = Renderer::getMarkupTemplate('player.tpl', 'addon/radioplayer');
    $b .= Renderer::replaceMacros($template, [
        'streams'        => $streams,
        'lblPlay'        => htmlspecialchars(DI::l10n()->t('Play'), ENT_QUOTES, 'UTF-8'),
        'txtStop'        => htmlspecialchars(DI::l10n()->t('Stop'), ENT_QUOTES, 'UTF-8'),
        'txtSelectError' => htmlspecialchars(DI::l10n()->t('Please select a valid stream.'), ENT_QUOTES, 'UTF-8'),
        'txtLoading'     => htmlspecialchars(DI::l10n()->t('Loading stream...'), ENT_QUOTES, 'UTF-8'),
        'txtNewSender'   => htmlspecialchars(DI::l10n()->t('Loading new station...'), ENT_QUOTES, 'UTF-8'),
        'txtPlayError'   => htmlspecialchars(DI::l10n()->t('Playback blocked by browser.'), ENT_QUOTES, 'UTF-8'),
        'txtLoadError'   => htmlspecialchars(DI::l10n()->t('Error loading the new station.'), ENT_QUOTES, 'UTF-8'),
        'txtConnError'   => htmlspecialchars(DI::l10n()->t('Connection to stream server lost.'), ENT_QUOTES, 'UTF-8'),
    ]);

    $jsUrl = DI::baseUrl() . '/addon/radioplayer/radioplayer.js';
    $b .= '<script src="' . $jsUrl . '"></script>';
    $b .= '<script>if(window.RadioPlayerInit){ window.RadioPlayerInit(); }</script>';
}
