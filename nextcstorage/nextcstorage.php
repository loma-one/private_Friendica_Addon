<?php
/**
 * Name: NextCStorage
 * Description: OCM-based connection to cloud storage
 * Version: 1.6.1
 * Author: PoC Developer
 */

require_once __DIR__ . '/lib/OCMClient.php';
require_once __DIR__ . '/lib/WebDavClient.php';

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function nextcstorage_install()
{
    Hook::register('photo_upload_end', __FILE__, 'nextcstorage_dispatcher');
    Hook::register('post_upload',       __FILE__, 'nextcstorage_dispatcher');
    Hook::register('addon_settings',    __FILE__, 'nextcstorage_settings');
    Hook::register('addon_settings_post', __FILE__, 'nextcstorage_settings_post');
}

function nextcstorage_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) return;
    $uid = DI::userSession()->getLocalUserId();

    $cloud_id = DI::pConfig()->get($uid, 'nextcstorage', 'cloud_id') ?: '';
    $path     = DI::pConfig()->get($uid, 'nextcstorage', 'path') ?: 'Friendica';
    $webdav   = DI::pConfig()->get($uid, 'nextcstorage', 'webdav_url') ?: '';
    $status   = DI::pConfig()->get($uid, 'nextcstorage', 'status') ?: 'Bereit';

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/nextcstorage');
    $html = Renderer::replaceMacros($t, [
        '$header'       => 'NextCStorage (OCM)',
        '$description'  => 'Sichert Fotos automatisch in die Cloud.',
        '$status_label' => 'Status',
        '$status'       => $status,
        '$active'       => !empty($webdav),
        // Fix für Array-to-string conversion: Sicherstellen, dass Arrays korrekt definiert sind
        '$cloud_id'     => ['nextc_cloud_id', 'Cloud ID', $cloud_id, 'user@domain.de'],
        '$app_password' => ['nextc_app_pass', 'App-Passwort', '', 'Token'],
        '$path'         => ['nextc_path', 'Zielverzeichnis', $path, 'Ordnername'],
        '$disconnect'   => ['nextc_disconnect', 'Verbindung trennen', false, 'Daten löschen']
    ]);

    $data = [
        'addon' => 'nextcstorage',
        'title' => 'NextCStorage',
        'html'  => $html
    ];
}

function nextcstorage_settings_post()
{
    if (!DI::userSession()->getLocalUserId()) return;
    $uid = DI::userSession()->getLocalUserId();

    if (!empty($_POST['nextc_disconnect'])) {
        DI::pConfig()->delete($uid, 'nextcstorage', 'cloud_id');
        DI::pConfig()->delete($uid, 'nextcstorage', 'app_password');
        DI::pConfig()->delete($uid, 'nextcstorage', 'webdav_url');
        DI::pConfig()->delete($uid, 'nextcstorage', 'path');
        DI::pConfig()->set($uid, 'nextcstorage', 'status', 'Getrennt');
        return;
    }

    if (isset($_POST['nextc_cloud_id'])) {
        $cloud_id = trim($_POST['nextc_cloud_id']);
        $path = trim($_POST['nextc_path'] ?: 'Friendica');
        DI::pConfig()->set($uid, 'nextcstorage', 'cloud_id', $cloud_id);
        DI::pConfig()->set($uid, 'nextcstorage', 'path', $path);

        $ocm = new OCMClient($cloud_id);
        $endpoints = $ocm->discover();

        if ($endpoints && !empty($endpoints['webdav'])) {
            DI::pConfig()->set($uid, 'nextcstorage', 'webdav_url', $endpoints['webdav']);
            $pass = trim($_POST['nextc_app_pass'] ?: DI::pConfig()->get($uid, 'nextcstorage', 'app_password') ?: '');
            if (!empty($pass)) {
                $user = explode('@', $cloud_id)[0];
                $client = new WebDavClient($endpoints['webdav'], $user, $pass);
                $client->createFolder($path);
                DI::pConfig()->set($uid, 'nextcstorage', 'status', 'Verbunden & Ordner bereit');
            }
        }
    }
    if (!empty($_POST['nextc_app_pass'])) {
        DI::pConfig()->set($uid, 'nextcstorage', 'app_password', trim($_POST['nextc_app_pass']));
    }
}

function nextcstorage_dispatcher(&$b)
{
    // Absoluter Not-Logbefehl für das System-Log
    error_log("NextCStorage: Dispatcher triggered!");

    $uid = $b['uid'] ?? DI::userSession()->getLocalUserId();
    if (!$uid) return;

    $webdav_url = DI::pConfig()->get($uid, 'nextcstorage', 'webdav_url');
    $cloud_id   = DI::pConfig()->get($uid, 'nextcstorage', 'cloud_id');
    $password   = DI::pConfig()->get($uid, 'nextcstorage', 'app_password');
    $path       = DI::pConfig()->get($uid, 'nextcstorage', 'path');

    if ($webdav_url && $cloud_id && $password) {
        // Wir ignorieren Skalierungen (Vorschaubilder), nur das Original (0) zählt
        if (isset($b['scale']) && (int)$b['scale'] !== 0) {
            return;
        }

        $filename = $b['filename'] ?? ($b['name'] ?? 'upload_' . time());
        $data = $b['data'] ?? ($b['content'] ?? null);

        if (!$data) return;

        $user = explode('@', $cloud_id)[0];
        try {
            $client = new WebDavClient($webdav_url, $user, $password);
            $client->upload($filename, $data, $path);
            error_log("NextCStorage: SUCCESS! Datei $filename übertragen.");
        } catch (\Exception $e) {
            error_log("NextCStorage: EXCEPTION: " . $e->getMessage());
        }
    }
}
