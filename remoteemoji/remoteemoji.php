<?php
/**
 * Name: RemoteEmoji Hybrid
 * Description: Combines a local emoji package with the dynamic integration of custom emojis via the standard API (https://instanz.tld/api/v1/custom_emojis).
 * Version: 1.6
 * Author: Matthias Ebers
 */

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Util\Network;

function remoteemoji_install()
{
    Hook::register('smilie', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_smilies');
}

function remoteemoji_uninstall()
{
    Hook::unregister('smilie', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_smilies');
}

function remoteemoji_smilies(array &$b)
{
    static $local_cache = null;

    if ($local_cache === null) {
        $jsonFile = __DIR__ . '/emoji_pack.json';
        $local_cache = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?: []) : [];
    }

    $baseUrl = DI::baseUrl() . '/addon/remoteemoji/';

    foreach ($local_cache as $emoji) {
        if (empty($emoji['shortname']) || empty($emoji['filepath'])) {
            continue;
        }

        $shortname = $emoji['shortname'];
        $display_name = htmlspecialchars(trim($shortname, ':'), ENT_QUOTES, 'UTF-8');

        $b['texts'][] = $shortname;
        $b['icons'][] = '<img class="smiley remoteemoji-local" style="width:20px;height:20px;vertical-align:middle;object-fit:contain;border:0;" src="' . $baseUrl . $emoji['filepath'] . '" alt="' . $display_name . '" title="' . $display_name . '" />';
    }

    if (empty($b['item']) || empty($b['item']['author-link'])) {
        return;
    }

    $author_url = $b['item']['author-link'];
    $url_parts = parse_url($author_url);
    if (empty($url_parts['host']) || empty($url_parts['scheme'])) {
        return;
    }

    $instance_url = $url_parts['scheme'] . '://' . $url_parts['host'];
    if ($instance_url === DI::baseUrl()) {
        return;
    }

    $cache_key = 'remoteemoji_v3_2_' . md5($instance_url);
    $remote_emojis = DI::cache()->get($cache_key);

    if ($remote_emojis === null) {
        $api_endpoint = $instance_url . '/api/v1/custom_emojis';
        $response = Network::fetchUrl($api_endpoint);
        if ($response) {
            $remote_emojis = json_decode($response, true) ?: [];
            DI::cache()->set($cache_key, $remote_emojis, 43200);
        } else {
            DI::cache()->set($cache_key, [], 3600);
            return;
        }
    }

    foreach ($remote_emojis as $emoji) {
        if (empty($emoji['shortcode']) || empty($emoji['url'])) {
            continue;
        }

        $shortname = ':' . $emoji['shortcode'] . ':';

        if (preg_match('/(?![^<]*>)' . preg_quote($shortname, '/') . '/', $b['text'])) {
            $display_name = htmlspecialchars($emoji['shortcode'], ENT_QUOTES, 'UTF-8');
            $img_url = htmlspecialchars($emoji['url'], ENT_QUOTES, 'UTF-8');
            $host = htmlspecialchars($url_parts['host'], ENT_QUOTES, 'UTF-8');

            $b['texts'][] = $shortname;
            $b['icons'][] = '<img class="smiley remoteemoji-remote" style="width:20px;height:20px;vertical-align:middle;object-fit:contain;border:0;" src="' . $img_url . '" alt="' . $display_name . '" title="' . $display_name . ' (' . $host . ')" />';
        }
    }
}
