<?php
/**
 * Name: Guardian
 * Description: Honeypot-Schutz und erweitertes Moderations-Panel
 * Version: 0.8.1
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\DI;

function guardian_install()
{
    Hook::register('register_post', 'addon/guardian/guardian.php', 'guardian_register_post');
    Hook::register('moderation_users_tabs', 'addon/guardian/guardian.php', 'guardian_users_tabs');
}

/**
 * REPARATUR: Nur ein Argument akzeptieren, da Friendica nur eines sendet.
 * Das verhindert den ArgumentCountError.
 */
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
    $arr['tabs'][] = [
        'label' => 'Guardian Audit',
        'url'   => DI::baseUrl() . '/guardian?view=48h',
        'sel'   => (DI::args()->getCommand() == 'guardian' ? 'active' : ''),
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
        /**
         * REPARATUR: Einfache Instanziierung.
         * DI::getDice()->create() ist hier zu fehleranfällig.
         */
        if (class_exists($className)) {
            $panel = new $className();
            return $panel->getAuditContent();
        }
        return "Guardian konnte nicht geladen werden: Klasse nicht gefunden.";
    } catch (\Exception $e) {
        return "Guardian konnte nicht geladen werden. Fehler: " . $e->getMessage();
    }
}
