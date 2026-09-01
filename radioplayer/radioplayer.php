<?php
/**
 * Name: RadioPlayer
 * Description: (POC SPA Modus) Adds a persistent web radio player above the timeline.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function radioplayer_install()
{
    Hook::register('page_content_top', 'addon/radioplayer/radioplayer.php', 'radioplayer_page_header');
}

function radioplayer_uninstall()
{
    Hook::unregister('page_content_top', 'addon/radioplayer/radioplayer.php', 'radioplayer_page_header');
}

function radioplayer_page_header(&$b)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    DI::page()->registerStylesheet('addon/radioplayer/radioplayer.css');

    $rawStreams = [
        ['name' => 'Radio Paradise (Main)', 'url' => 'https://stream.radioparadise.com/aac-320'],
        ['name' => 'WDR2', 'url' => 'https://wdr-wdr2-rheinland.icecast.wdr.de/wdr/wdr2/rheinland/mp3/128/stream.mp3'],
        ['name' => 'DLF Nova', 'url' => 'https://st03.dlf.de/dlf/03/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk', 'url' => 'https://st01.dlf.de/dlf/01/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk Kultur', 'url' => 'https://st02.dlf.de/dlf/02/128/mp3/stream.mp3'],
        ['name' => 'WDR 5', 'url' => 'https://wdr-wdr5-live.icecast.wdr.de/wdr/wdr5/live/mp3/128/stream.mp3'],
        ['name' => 'ORF Radio FM4', 'url' => 'https://orf-live.ors-shoutcast.at/fm4-q2a'],
        ['name' => '1LIVE', 'url' => 'https://wdr-1live-live.icecast.wdr.de/wdr/1live/live/mp3/128/stream.mp3'],
        ['name' => 'OE1', 'url' => 'https://orf-live.ors-shoutcast.at/oe1-q2a'],
        ['name' => 'SRF 3', 'url' => 'https://stream.srg-ssr.ch/m/srf3/mp3_128'],
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
