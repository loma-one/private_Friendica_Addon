<?php
/**
 * Name: RadioPlayer
 * Version: 1.8.1
 * Description: Radio-Player für Friendica
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

    $enabled     = (bool) DI::pConfig()->get($uid, 'radioplayer', 'enable', false);
    $custom_urls = DI::pConfig()->get($uid, 'radioplayer', 'custom_urls', '');

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/radioplayer');

    $html = Renderer::replaceMacros($t, [
        '$title'   => DI::l10n()->t('RadioPlayer Einstellungen'),
        '$enable'  => [
            'radioplayer_enable',
            DI::l10n()->t('RadioPlayer aktivieren'),
            $enabled,
            DI::l10n()->t('Aktiviert den Radio-Player über der Zeitleiste. Hinweis: Der SPA-Modus muss in Friendica aktiv sein, damit der Player unterbrechungsfrei läuft.')
        ],
        '$streams' => [
            'radioplayer_custom_urls',
            DI::l10n()->t('Eigene Stream-URLs (max. 20)'),
            $custom_urls,
            DI::l10n()->t('Trage hier die Internetadresse (URL) deiner gewünschten Radiosender ein – genau eine URL pro Zeile (maximal 20 Sender). Der Sendername wird beim Speichern automatisch ermittelt und im Player angezeigt.')
        ],
    ]);

    $data = [
        'addon' => 'radioplayer',
        'title' => DI::l10n()->t('RadioPlayer'),
        'html'  => $html,
    ];
}

function radioplayer_fetch_stream_title(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'method'          => 'GET',
            'header'          => "Icy-MetaData: 1\r\nUser-Agent: FriendicaRadioPlayer/1.0\r\n",
            'timeout'         => 2,
            'follow_location' => 1,
        ]
    ]);

    $headers = @get_headers($url, true, $context);

    if (is_array($headers)) {
        foreach ($headers as $key => $value) {
            if (strtolower((string)$key) === 'icy-name') {
                $name = is_array($value) ? end($value) : $value;
                $name = trim((string)$name);
                if (!empty($name)) {
                    return $name;
                }
            }
        }
    }

    $parsed = parse_url($url);
    return $parsed['host'] ?? 'Eigener Stream';
}

function radioplayer_settings_post(array &$post)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    if (isset($post['radioplayer-submit'])) {
        $enable = !empty($post['radioplayer_enable']) ? 1 : 0;
        DI::pConfig()->set($uid, 'radioplayer', 'enable', $enable);

        $raw_input = trim($post['radioplayer_custom_urls'] ?? '');
        $lines = explode("\n", $raw_input);

        $clean_urls   = [];
        $stored_pairs = [];

        foreach ($lines as $line) {
            $url = trim($line);
            if (empty($url)) {
                continue;
            }

            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $parsed = parse_url($url);
                if (isset($parsed['scheme']) && in_array($parsed['scheme'], ['http', 'https'])) {
                    $name = radioplayer_fetch_stream_title($url);
                    $clean_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

                    $clean_urls[]   = $url;
                    $stored_pairs[] = $clean_name . ' | ' . $url;
                }
            }
        }

        if (count($clean_urls) > 20) {
            $clean_urls   = array_slice($clean_urls, 0, 20);
            $stored_pairs = array_slice($stored_pairs, 0, 20);
        }

        DI::pConfig()->set($uid, 'radioplayer', 'custom_urls', implode("\n", $clean_urls));
        DI::pConfig()->set($uid, 'radioplayer', 'custom_pairs', implode("\n", $stored_pairs));
    }
}

function radioplayer_page_header(&$b)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    if (DI::mode()->isAjax()) {
        return;
    }

    $enabled = (bool) DI::pConfig()->get($uid, 'radioplayer', 'enable', false);
    if (!$enabled) {
        return;
    }

    DI::page()->registerStylesheet('addon/radioplayer/radioplayer.css');
    DI::page()->registerFooterScript('addon/radioplayer/radioplayer.js');

    $streams = [
        ['name' => 'DLF Nova', 'url' => 'https://st03.dlf.de/dlf/03/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk', 'url' => 'https://st01.dlf.de/dlf/01/128/mp3/stream.mp3'],
        ['name' => 'Deutschlandfunk Kultur', 'url' => 'https://st02.dlf.de/dlf/02/128/mp3/stream.mp3'],
        ['name' => 'WDR2', 'url' => 'https://wdr-wdr2-rheinland.icecast.wdr.de/wdr/wdr2/rheinland/mp3/128/stream.mp3'],
        ['name' => 'WDR 5', 'url' => 'https://wdr-wdr5-live.icecast.wdr.de/wdr/wdr5/live/mp3/128/stream.mp3'],
        ['name' => 'ORF Radio FM4', 'url' => 'https://orf-live.ors-shoutcast.at/fm4-q2a'],
        ['name' => '1LIVE', 'url' => 'https://wdr-1live-live.icecast.wdr.de/wdr/1live/live/mp3/128/stream.mp3'],
        ['name' => 'OE1', 'url' => 'https://orf-live.ors-shoutcast.at/oe1-q2a'],
        ['name' => 'SRF3', 'url' => 'https://stream.srg-ssr.ch/m/srf3/mp3_128'],
        ['name' => 'N-JOY', 'url' => 'https://icecast.ndr.de/ndr/njoy/live/mp3/128/stream.mp3'],
        ['name' => 'Radio FFN', 'url' => 'https://player.ffn.de/radioffn.mp3'],
        ['name' => 'Antenne Bayern', 'url' => 'https://stream.antenne.de/antenne/stream/mp3']
    ];

    $saved_pairs = DI::pConfig()->get($uid, 'radioplayer', 'custom_pairs', '');
    if (!empty($saved_pairs)) {
        $lines = explode("\n", $saved_pairs);
        $custom_streams = [];

        foreach ($lines as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2) {
                $custom_streams[] = [
                    'name' => trim($parts[0]),
                    'url'  => trim($parts[1])
                ];
            }
        }

        if (!empty($custom_streams)) {
            $streams = array_merge($custom_streams, $streams);
        }
    }

    $template = Renderer::getMarkupTemplate('player.tpl', 'addon/radioplayer');
    $b .= Renderer::replaceMacros($template, [
        'streams'        => $streams,
        'lblPlay'        => DI::l10n()->t('Play'),
        'txtStop'        => DI::l10n()->t('Stop'),
        'txtSelectError' => DI::l10n()->t('Please select a valid stream.'),
        'txtLoading'     => DI::l10n()->t('Loading stream...'),
        'txtNewSender'   => DI::l10n()->t('Loading new station...'),
        'txtPlayError'   => DI::l10n()->t('Playback blocked by browser.'),
        'txtLoadError'   => DI::l10n()->t('Error loading the new station.'),
        'txtConnError'   => DI::l10n()->t('Connection to stream server lost.'),
    ]);
}
