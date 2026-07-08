<?php
/**
 * Name: PictureLost
 * Description: Findet verwaiste Bilder, die in keinem Beitrag (Content) verwendet werden.
 * Version: 0.0.1
 * Author: Matthias Ebers
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function picturelost_install()
{
    Hook::register('module_loaded', 'addon/picturelost/picturelost.php', 'picturelost_module_loaded');
    Hook::register('addon_settings', 'addon/picturelost/picturelost.php', 'picturelost_addon_settings');
    Hook::register('addon_settings_post', 'addon/picturelost/picturelost.php', 'picturelost_addon_settings_post');
}

function picturelost_uninstall()
{
    Hook::unregister('module_loaded', 'addon/picturelost/picturelost.php', 'picturelost_module_loaded');
    Hook::unregister('addon_settings', 'addon/picturelost/picturelost.php', 'picturelost_addon_settings');
    Hook::unregister('addon_settings_post', 'addon/picturelost/picturelost.php', 'picturelost_addon_settings_post');
}

function picturelost_module_loaded(\Friendica\Core\Module &$mod)
{
    if ($mod->getCommand() === 'picturelost') {
        $mod->setActive(true);
    }
}

function picturelost_module()
{
    if (!DI::userSession()->getLocalUserId()) {
        header('Location: ' . DI::baseUrl() . '/login');
        exit();
    }
}

function picturelost_content()
{
    if (!DI::userSession()->getLocalUserId()) {
        return '';
    }

    $classFile = __DIR__ . '/PictureLostPanel.php';
    if (file_exists($classFile)) {
        require_once($classFile);
    }

    $className = '\Friendica\Addon\picturelost\PictureLostPanel';

    try {
        if (class_exists($className)) {
            $panel = new $className();
            return $panel->getLostContent();
        }
        return "PictureLost konnte nicht geladen werden: Klasse nicht gefunden.";
    } catch (\Exception $e) {
        return "PictureLost konnte nicht geladen werden. Fehler: " . $e->getMessage();
    }
}

function picturelost_addon_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $enabled = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'picturelost', 'enabled', 0);

    $t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/picturelost/');
    $html = Renderer::replaceMacros($t, [
        '$enabled'   => ['picturelost-enabled', DI::l10n()->t('PictureLost aktivieren'), $enabled, DI::l10n()->t('Aktiviert die Analyse-Seite für deinen Account.')],
        '$is_active' => $enabled,
        '$app_url'   => DI::baseUrl() . '/picturelost',
    ]);

    $data = [
        'addon' => 'picturelost',
        'title' => DI::l10n()->t('PictureLost Einstellungen'),
        'html'  => $html,
    ];
}

function picturelost_addon_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['picturelost-submit'])) {
        return;
    }

    $enable = (!empty($_POST['picturelost-enabled']) ? 1 : 0);
    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'picturelost', 'enabled', $enable);
}
