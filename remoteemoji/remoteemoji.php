<?php
/*
 * Name: RemoteEmoji
 * Description: Enables the use of emojis from remote servers.
 * Version: 1.0
 *  Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\DI;

function remoteemoji_install()
{
    Hook::register('smilie', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_smilies');
}

function remoteemoji_smilies(array &$b)
{
    // JSON-Datei mit Emoji-Definitionen
    $jsonFile = __DIR__ . '/emoji_pack.json';

    if (!file_exists($jsonFile)) {
        return;
    }

    $json = file_get_contents($jsonFile);
    $emojis = json_decode($json, true);

    if (!is_array($emojis)) {
        return;
    }

    foreach ($emojis as $emoji) {
        if (!isset($emoji['shortname']) || !isset($emoji['filepath'])) {
            continue;
        }

        $shortname = $emoji['shortname'];
        $filepath  = $emoji['filepath']; // z. B. "icons/basic/verified.png"

        $b['texts'][] = $shortname;
        $b['icons'][] = '<img class="smiley" src="'
            . DI::baseUrl() . '/addon/remoteemoji/' . $filepath
            . '" alt="' . $shortname . '" />';
    }
}
