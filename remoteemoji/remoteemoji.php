<?php
/**
 * Name: RemoteEmoji
 * Description: Provides a local emoji pack. Supports multi-word shortnames (e.g., :winking face:) and features a cleaned hover effect.
 * Version: 1.3
 * Author: Matthias Ebers
 */

use Friendica\Core\Hook;
use Friendica\DI;

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
    static $emoji_cache = null;

    if ($emoji_cache === null) {
        $jsonFile = __DIR__ . '/emoji_pack.json';
        if (file_exists($jsonFile)) {
            $json = file_get_contents($jsonFile);
            $emoji_cache = json_decode($json, true) ?? [];
        } else {
            $emoji_cache = [];
        }
    }

    if (empty($emoji_cache)) {
        return;
    }

    foreach ($emoji_cache as $emoji) {
        if (empty($emoji['shortname']) || empty($emoji['filepath'])) {
            continue;
        }

        $shortname = $emoji['shortname'];

        // Clean the hover text: ":winking face with tongue:" becomes "winking face with tongue"
        $display_name = trim($shortname, ':');

        $url = DI::baseUrl() . '/addon/remoteemoji/' . $emoji['filepath'];

        // Add the full shortname (including spaces and colons) to the search array
        $b['texts'][] = $shortname;

        // Add the corresponding HTML icon
        $b['icons'][] = '<img class="smiley" style="width: 20px; height: 20px;" src="'
            . $url . '" alt="' . $display_name . '" title="' . $display_name . '" />';
    }
}
