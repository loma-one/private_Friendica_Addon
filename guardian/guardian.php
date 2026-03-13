<?php
/**
 * Name: Guardian
 * Description: Honeypot-Schutz und erweitertes Moderations-Panel
 * Version: 0.7
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * BETA VERSION
 */

use Friendica\Core\Hook;
use Friendica\DI;

function guardian_install()
{
    Hook::register('register_post', 'addon/guardian/guardian.php', 'guardian_register_post');
    Hook::register('moderation_users_tabs', 'addon/guardian/guardian.php', 'guardian_users_tabs');
}

function guardian_register_post(&$a, &$b)
{
    if (!empty($_POST['special_mail_field'])) {
        header('HTTP/1.1 403 Forbidden');
        echo "Spam protection triggered.";
        killme();
    }
}

function guardian_users_tabs(array &$arr)
{
    $arr['tabs'][] = [
        'label' => 'Guardian Audit',
        'url'   => 'guardian',
        'sel'   => ($arr['selectedTab'] == 'guardian' ? 'active' : ''),
        'title' => 'Spam-Analyse',
        'id'    => 'admin-users-guardian',
    ];
}

function guardian_module() {}

function guardian_content()
{
    $classFile = __DIR__ . '/GuardianPanel.php';
    if (file_exists($classFile)) {
        require_once($classFile);
    }

    $className = '\Friendica\Addon\guardian\GuardianPanel';

    try {
        // Wir nutzen Dice, überlassen ihm aber die Parameter-Wahl.
        // Falls Dice scheitert, nehmen wir den manuellen Weg ohne DI-Methoden.
        $panel = DI::getDice()->create($className, [$_SERVER]);
        return $panel->getAuditContent();
    } catch (\Exception $e) {
        // Letzter Rettungsanker: Manuelle Instanz ohne Argumente, falls die Elternklasse es erlaubt
        // oder wir nutzen eine Reflection, um den Fehler zu umgehen.
        return "Guardian konnte nicht geladen werden. Fehler: " . $e->getMessage();
    }
}
