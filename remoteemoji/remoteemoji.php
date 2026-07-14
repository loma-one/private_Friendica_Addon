<?php
/**
 * Name: RemoteEmoji Hybrid
 * Description: Combines a local emoji package with the dynamic integration of custom emojis via the standard API (https://instanz.tld/api/v1/custom_emojis).
 * Version: 1.9.2
 * Author: Matthias Ebers
 */

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Util\Network;

function remoteemoji_install()
{
    Hook::register('smilie', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_smilies');
    Hook::register('plugin_admin', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_addon_admin');
    Hook::register('plugin_admin_post', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_addon_admin_post');
}

function remoteemoji_uninstall()
{
    Hook::unregister('smilie', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_smilies');
    Hook::unregister('plugin_admin', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_addon_admin');
    Hook::unregister('plugin_admin_post', 'addon/remoteemoji/remoteemoji.php', 'remoteemoji_addon_admin_post');
}

function remoteemoji_generate_pack()
{
    $baseDir = __DIR__ . '/icons';
    $jsonFile = __DIR__ . '/emoji_pack.json';

    if (!is_dir($baseDir)) {
        return ['error' => 'Der Ordner /icons existiert nicht. Bitte lege diesen im Addon-Verzeichnis an.'];
    }

    $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webm'];
    $emojiPack = [];

    $directory = new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directory);

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $allowedExtensions)) {
                $relativePath = 'icons/' . ltrim(str_replace($baseDir, '', $file->getPathname()), '/\\');
                $relativePath = str_replace('\\', '/', $relativePath);

                $filenameKey = $file->getBasename('.' . $file->getExtension());
                $jsonKey = strtolower($filenameKey);

                $emojiPack[$jsonKey] = [
                    'shortname' => ':' . $filenameKey . ':',
                    'filepath'  => $relativePath
                ];
            }
        }
    }

    ksort($emojiPack);

    if (file_put_contents($jsonFile, json_encode($emojiPack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        return ['success' => count($emojiPack) . ' Emojis erfolgreich in die emoji_pack.json eingetragen.'];
    }

    return ['error' => 'Datei emoji_pack.json konnte nicht geschrieben werden. Bitte Schreibrechte prüfen.'];
}

function remoteemoji_addon_admin(&$o)
{
    $jsonFile = __DIR__ . '/emoji_pack.json';
    $currentCount = 0;
    if (file_exists($jsonFile)) {
        $pack = json_decode(file_get_contents($jsonFile), true);
        $currentCount = is_array($pack) ? count($pack) : 0;
    }

    $statusHtml = '';
    if (!empty($_SESSION['remoteemoji_status_success'])) {
        $statusHtml = '<div class="alert alert-success">' . htmlspecialchars($_SESSION['remoteemoji_status_success']) . '</div>';
        unset($_SESSION['remoteemoji_status_success']);
    } elseif (!empty($_SESSION['remoteemoji_status_error'])) {
        $statusHtml = '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['remoteemoji_status_error']) . '</div>';
        unset($_SESSION['remoteemoji_status_error']);
    }

    $o = '
        <div class="addon-admin-page">
            <h3>RemoteEmoji Hybrid Einstellungen</h3>
            ' . $statusHtml . '
            <p>Mit diesem Addon kannst du lokale Emojis verwalten und dynamisch mit Remote-Instanzen synchronisieren.</p>

            <div class="well">
                <p>Aktuell in der <code>emoji_pack.json</code> registrierte Emojis: <strong>' . $currentCount . '</strong></p>
                <p><strong>Anleitung:</strong> Lade neue Emoji-Dateien (.png, .jpg, .jpeg, .gif, .webm) per FTP/SSH in den Ordner <code>addon/remoteemoji/icons/</code> (Unterordner sind erlaubt). Klicke anschließend auf den Button unten, um die Index-Datei zu aktualisieren.</p>
            </div>

            <form action="' . DI::baseUrl() . '/admin/addons/remoteemoji" method="post">
                <input type="submit" name="remoteemoji_rebuild" class="btn btn-primary" value="emoji_pack.json jetzt generieren / aktualisieren" />
            </form>
        </div>
    ';
}

function remoteemoji_addon_admin_post()
{
    if (!empty($_POST['remoteemoji_rebuild'])) {
        $result = remoteemoji_generate_pack();
        if (isset($result['success'])) {
            $_SESSION['remoteemoji_status_success'] = $result['success'];
        } else {
            $_SESSION['remoteemoji_status_error'] = $result['error'];
        }
    }
}

function remoteemoji_smilies(array &$b)
{
    if (empty($b['text'])) {
        return;
    }

    static $local_cache = null;
    $registered_shortnames = [];

    if ($local_cache === null) {
        $jsonFile = __DIR__ . '/emoji_pack.json';
        $local_cache = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?: []) : [];
    }

    $baseUrl = DI::baseUrl() . '/addon/remoteemoji/';
    $local_img_style = 'display:inline-block !important;width:20px;height:20px;vertical-align:middle;object-fit:contain;border:0;margin-right:4px;';

    foreach ($local_cache as $emoji) {
        if (empty($emoji['shortname']) || empty($emoji['filepath'])) {
            continue;
        }

        $shortname = $emoji['shortname'];
        $normalized_local_key = trim($shortname, ':');

        if (preg_match('/(?<![a-zA-Z0-9="\'\/])' . preg_quote($shortname, '/') . '(?![^<]*>)/', $b['text'])) {
            $display_name = htmlspecialchars($normalized_local_key, ENT_QUOTES, 'UTF-8');

            $b['texts'][] = $shortname;
            $b['icons'][] = '<img class="smiley remoteemoji-local" style="' . $local_img_style . '" src="' . $baseUrl . $emoji['filepath'] . '" alt="' . $display_name . '" title="' . $display_name . '" />';

            $registered_shortnames[$normalized_local_key] = true;
        }
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

        $remote_code = $emoji['shortcode'];

        if (isset($registered_shortnames[$remote_code])) {
            continue;
        }

        $shortname = ':' . $remote_code . ':';

        if (preg_match('/(?<![a-zA-Z0-9="\'\/])' . preg_quote($shortname, '/') . '(?![^<]*>)/', $b['text'])) {
            $display_name = htmlspecialchars($remote_code, ENT_QUOTES, 'UTF-8');
            $img_url = htmlspecialchars($emoji['url'], ENT_QUOTES, 'UTF-8');
            $host = htmlspecialchars($url_parts['host'], ENT_QUOTES, 'UTF-8');

            $b['texts'][] = $shortname;
            $b['icons'][] = '<img class="smiley remoteemoji-remote" style="width:20px;height:20px;vertical-align:middle;object-fit:contain;border:0;" src="' . $img_url . '" alt="' . $display_name . '" title="' . $display_name . ' (' . $host . ')" />';

            $registered_shortnames[$remote_code] = true;
        }
    }
}
