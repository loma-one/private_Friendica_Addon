<?php
/**
 * Name: Follow Suggestions
 * Description: Onboarding widget for local Friendica/Fediverse contacts
 * Version: 4.5.2
 * Author: Matthias Ebers <feb@loma.ml>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Database\DBA;
use Friendica\Model\Contact;

function follow_install()
{
    Hook::register('network_mod_init', __FILE__, 'follow_network_mod_init');
    Hook::register('addon_settings', __FILE__, 'follow_addon_settings');
    Hook::register('addon_settings_post', __FILE__, 'follow_addon_settings_post');
    Hook::register('addon_admin', __FILE__, 'follow_addon_admin');
    Hook::register('addon_admin_post', __FILE__, 'follow_addon_admin_post');
}

function follow_uninstall()
{
    Hook::unregister('network_mod_init', __FILE__, 'follow_network_mod_init');
    Hook::unregister('addon_settings', __FILE__, 'follow_addon_settings');
    Hook::unregister('addon_settings_post', __FILE__, 'follow_addon_settings_post');
    Hook::unregister('addon_admin', __FILE__, 'follow_addon_admin');
    Hook::unregister('addon_admin_post', __FILE__, 'follow_addon_admin_post');
}

function follow_network_mod_init()
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId || !DI::pConfig()->get($userId, 'follow', 'enabled')) {
        return;
    }

    $cacheKey = 'follow_local_directory_v2';
    $cacheEntry = DI::cache()->get($cacheKey);

    if ($cacheEntry === null || !is_array($cacheEntry) || !isset($cacheEntry['data'])) {
        $externalItems = [];
        $apiUrl = DI::baseUrl() . '/api/v1/directory';

        try {
            $response = DI::httpClient()->get($apiUrl, 'application/json');
            if ($response->isSuccess()) {
                $json = json_decode($response->getBody(), true);
                if (is_array($json)) {
                    foreach ($json as $entry) {
                        if (!empty($entry['bot'])) {
                            continue;
                        }

                        $accountType = $entry['type'] ?? '';

                        // Falls 'type' leer ist, schauen wir in 'source', aber nur wenn es ein String ist
                        if (empty($accountType) && isset($entry['source']) && is_string($entry['source'])) {
                            $accountType = $entry['source'];
                        }

                        $nonHumanTypes = ['service', 'application', 'relay', 'news', 'group'];

                        foreach ($nonHumanTypes as $badType) {
                            // Sicherstellen, dass wir nur suchen, wenn accountType wirklich ein String ist
                            if (is_string($accountType) && stripos($accountType, $badType) !== false) {
                                continue 2;
                            }
                        }

                        $url = $entry['url'] ?? '';
                        if (empty($url)) {
                            continue;
                        }

                        $icon = $entry['avatar'] ?? '';
                        if (empty($icon) || strpos($icon, 'static/avatars/missing.png') !== false) {
                            continue;
                        }

                        $name = !empty($entry['display_name']) ? $entry['display_name'] : ($entry['username'] ?? 'User');

                        $externalItems[] = [
                            'name'         => (string)$name,
                            'icon'         => (string)$icon,
                            'original_url' => (string)$url,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            DI::logger()->info("FOLLOW-DIRECTORY-ERROR: " . $e->getMessage());
        }

        $cacheData = [
            'timestamp' => time(),
            'data'      => $externalItems
        ];

        DI::cache()->set($cacheKey, $cacheData, 10800);
        $externalItems = $cacheData['data'];
    } else {
        $externalItems = $cacheEntry['data'];
    }

    if (empty($externalItems)) {
        return;
    }

    $urls = array_column($externalItems, 'original_url');
    $excludedUrls = [];
    $urlToContactId = [];

    $r = DBA::select('contact', ['id', 'url', 'rel', 'blocked', 'ignored'], [
            'uid' => $userId,
            'url' => $urls
        ]);

        if (DBA::isResult($r)) {
            // Nutze foreach statt while(DBA::fetch), um maximale Kompatibilität zu gewährleisten
            foreach ($r as $row) {
                if ($row['rel'] != Contact::NOTHING || $row['blocked'] || $row['ignored']) {
                    $excludedUrls[] = $row['url'];
                } else {
                    $urlToContactId[$row['url']] = $row['id'];
                }
            }
        }

    $finalSuggestions = [];
    foreach ($externalItems as $item) {
        if (in_array($item['original_url'], $excludedUrls)) {
            continue;
        }

        $url = $item['original_url'];
        $contactId = $urlToContactId[$url] ?? Contact::getIdForURL($url);
        $localProfileUrl = ($contactId) ? DI::baseUrl() . '/contact/' . $contactId . '/' : $url;

        $item['url'] = $localProfileUrl;
        $finalSuggestions[] = $item;

        if (count($finalSuggestions) >= 50) {
            break;
        }
    }

    if (empty($finalSuggestions)) {
        return;
    }

    shuffle($finalSuggestions);
    $displayItems = array_slice($finalSuggestions, 0, 9);

    $t = Renderer::getMarkupTemplate('widget.tpl', 'addon/follow/');
    $html = Renderer::replaceMacros($t, [
        '$title'    => DI::l10n()->t('Discover people'),
        '$fallback' => DI::baseUrl() . '/images/person-80.jpg',
        '$items'    => $displayItems,
    ]);

    DI::page()['aside'] = $html . DI::page()['aside'];
}

function follow_addon_admin(string &$o)
{
    $cacheKey = 'follow_local_directory_v2';
    $cacheEntry = DI::cache()->get($cacheKey);

    $hasData = is_array($cacheEntry) && isset($cacheEntry['data']);
    $count = $hasData ? count($cacheEntry['data']) : 0;

    if ($hasData) {
        $lastUpdateTs = $cacheEntry['timestamp'];
        $lastUpdateText = date('Y-m-d H:i:s', $lastUpdateTs);
        $nextUpdateText = date('Y-m-d H:i:s', $lastUpdateTs + 10800);
    } else {
        $lastUpdateText = DI::l10n()->t('No data in cache');
        $nextUpdateText = DI::l10n()->t('After next widget load');
    }

    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/follow/');
    $o = Renderer::replaceMacros($t, [
        '$info'        => sprintf(DI::l10n()->t('There are currently %d profiles in the pool.'), $count),
        '$last_update' => $lastUpdateText,
        '$next_update' => $nextUpdateText,
    ]);
}

function follow_addon_admin_post()
{
    if (!empty($_POST['follow_reset_cache'])) {
        DI::cache()->delete('follow_local_directory_v2');
    }
}

function follow_addon_settings(array &$data)
{
    $userId = DI::userSession()->getLocalUserId();
    $enabled = DI::pConfig()->get($userId, 'follow', 'enabled');

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/follow/');
    $html = Renderer::replaceMacros($t, [
        '$title'   => DI::l10n()->t('Follow Suggestions'),
        '$enabled' => ['follow_enabled', DI::l10n()->t('Show in the sidebar'), $enabled, ''],
        '$submit'  => DI::l10n()->t('Save'),
    ]);

    $data = [
        'addon' => 'follow',
        'title' => DI::l10n()->t('Follow Suggestions'),
        'html'  => $html,
    ];
}

function follow_addon_settings_post(array &$data)
{
    if (!empty($_POST['addon']) && $_POST['addon'] === 'follow') {
        DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'follow', 'enabled', intval($_POST['follow_enabled']));
    }
}
