<?php
/**
 * Name: Guardian
 * Description: Honeypot-Schutz und erweitertes Moderations-Panel
 * Version: 0.8.3
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\DI;

function guardian_install()
{
    Hook::register('register_post', 'addon/guardian/guardian.php', 'guardian_register_post');
    Hook::register('moderation_users_tabs', 'addon/guardian/guardian.php', 'guardian_users_tabs');
}

function guardian_uninstall()
{
    Hook::unregister('register_post', 'addon/guardian/guardian.php', 'guardian_register_post');
    Hook::unregister('moderation_users_tabs', 'addon/guardian/guardian.php', 'guardian_users_tabs');
}

function guardian_register_post(&$b)
{
    if (!empty($_POST['special_mail_field'])) {
        header('HTTP/1.1 403 Forbidden');
        echo "Spam protection triggered.";
        killme();
    }
}

function guardian_users_tabs(array &$arr)
{
    // Nur für Admins anzeigen
    if (!DI::userSession()->isSiteAdmin()) {
        return;
    }

    // Sicherstellen, dass der Key 'tabs' existiert
    if (!isset($arr['tabs'])) {
        $arr['tabs'] = [];
    }

    $arr['tabs'][] = [
        'label' => 'Guardian Audit',
        'url'   => 'guardian',
        'sel'   => (DI::args()->getCommand() === 'guardian' ? 'active' : ''),
        'title' => 'Spam-Analyse',
        'id'    => 'admin-users-guardian',
    ];
}

function guardian_module()
{
    // Double-Check: Nutzer muss eingeloggt UND Admin sein
    if (!DI::userSession()->getLocalUserId() || !DI::userSession()->isSiteAdmin()) {
        header('Location: ' . DI::baseUrl() . '/login');
        exit();
    }
}

function guardian_content()
{
    // Double-Check: Nutzer muss eingeloggt UND Admin sein
    if (!DI::userSession()->getLocalUserId() || !DI::userSession()->isSiteAdmin()) {
        header('Location: ' . DI::baseUrl() . '/login');
        exit();
    }

    // Restlicher Code bleibt unverändert
    $classFile = __DIR__ . '/GuardianPanel.php';
    if (file_exists($classFile)) {
        require_once($classFile);
    }

    $className = '\Friendica\Addon\guardian\GuardianPanel';

    try {
        if (class_exists($className)) {
            $panel = new $className();
            return $panel->getAuditContent();
        }
        return "Guardian konnte nicht geladen werden: Klasse nicht gefunden.";
    } catch (\Exception $e) {
        return "Guardian konnte nicht geladen werden. Fehler: " . $e->getMessage();
    }
}
