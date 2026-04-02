<?php
/**
 * Name: Follow Suggestions
 * Description: Onboarding widget for local Friendica/Fediverse contacts
 * Version: 4.4.2
 * Author: Matthias Ebers
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

    $cacheKey = 'follow_local_directory_v1';
    $externalItems = DI::cache()->get($cacheKey);

    if ($externalItems === null) {
        $externalItems = [];
        $apiUrl = DI::baseUrl() . '/api/v1/directory';

        try {
                    $response = DI::httpClient()->get($apiUrl, 'application/json');
                    if ($response->isSuccess()) {
                        $json = json_decode($response->getBody(), true);
                        if (is_array($json)) {
                            foreach ($json as $entry) {
                                // 1. Filtere Bots und System-Accounts aus
                                // Im Fediverse werden Instanz-Accounts oft als 'bot' markiert
                                if (!empty($entry['bot'])) {
                                    continue;
                                }
        
                                $url = $entry['url'] ?? '';
                                if (empty($url)) continue;
        
                                // 2. Zusätzlicher Check: Überspringe Accounts ohne Avatar
                                // System-Accounts haben oft kein Bild, was das Widget unschön macht
                                $icon = $entry['avatar'] ?? '';
                                if (empty($icon) || strpos($icon, 'static/avatars/missing.png') !== false) {
                                    continue;
                                }
        
                                $name = !empty($entry['display_name']) ? $entry['display_name'] : ($entry['username'] ?? 'User');
        
                                $contactId = Contact::getIdForURL($url);
                                $localProfileUrl = ($contactId) ? DI::baseUrl() . '/contact/' . $contactId . '/conversations' : $url;
        
                                $externalItems[] = [
                                    'name'         => (string)$name,
                                    'url'          => (string)$localProfileUrl,
                                    'icon'         => (string)$icon,
                                    'original_url' => (string)$url,
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    DI::logger()->info("FOLLOW-DIRECTORY-ERROR: " . $e->getMessage());
                }
        DI::cache()->set($cacheKey, $externalItems, 10800);
    }

    if (empty($externalItems)) return;

        $urls = array_column($externalItems, 'original_url');

        $existingUrls = [];
        $r = DBA::select('contact', ['url'], [
            'uid' => $userId,
            'url' => $urls,
            'rel' => [1, 2, 3, 4, 5, 6, 7]
        ]);

        if (DBA::isResult($r)) {
            while ($row = DBA::fetch($r)) {
                $existingUrls[] = $row['url'];
            }
            DBA::close($r);
        }

        $finalSuggestions = [];
        foreach ($externalItems as $item) {
            if (!in_array($item['original_url'], $existingUrls)) {
                $finalSuggestions[] = $item;
            }
            if (count($finalSuggestions) >= 50) break;
        }

    if (empty($finalSuggestions)) return;

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
    $cacheKey = 'follow_local_directory_v1';
    $cachedData = DI::cache()->get($cacheKey);
    $count = is_array($cachedData) ? count($cachedData) : 0;

    $currentTime = date('Y-m-d H:i:s');
    $nextUpdate = date('Y-m-d H:i:s', time() + 10800);

    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/follow/');
    $o = Renderer::replaceMacros($t, [
        '$info'           => sprintf(DI::l10n()->t('There are currently %d profiles in the pool.'), $count),
        '$last_update'    => $count > 0 ? $currentTime : DI::l10n()->t('No data in cache'),
        '$next_update'    => $count > 0 ? $nextUpdate : DI::l10n()->t('After next widget load'),
    ]);
}

function follow_addon_admin_post()
{
    if (!empty($_POST['follow_reset_cache'])) {
        DI::cache()->delete('follow_local_directory_v1');
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
