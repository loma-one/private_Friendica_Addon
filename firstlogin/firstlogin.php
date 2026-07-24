<?php
/**
 * Name: FirstLogin
 * Description: Zeigt neuen Nutzern beim ersten Login ein Onboarding-Banner an, das genau 7 Tage sichtbar bleibt.
 * Version: 1.1
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Model\User;

function firstlogin_install()
{
    Hook::register('page_content_top', 'addon/firstlogin/firstlogin.php', 'firstlogin_page_header');
}

function firstlogin_uninstall()
{
    Hook::unregister('page_content_top', 'addon/firstlogin/firstlogin.php', 'firstlogin_page_header');
}

function firstlogin_page_header(&$b)
{
    $uid = DI::userSession()->getLocalUserId();

    if (!$uid) {
        return;
    }

    if (isset($_POST['firstlogin_dismiss']) && $_POST['firstlogin_dismiss'] === '1') {
        DI::pConfig()->set($uid, 'firstlogin', 'banner_expired', true);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }

    $isExpired = DI::pConfig()->get($uid, 'firstlogin', 'banner_expired', false);
    if ($isExpired) {
        return;
    }

    $now = time();
    $sevenDaysInSeconds = 7 * 86400;
    $firstShownTime = DI::pConfig()->get($uid, 'firstlogin', 'first_shown_time', null);

    if ($firstShownTime === null) {
        $user = User::getById($uid);
        if (!$user || empty($user['register_date'])) {
            return;
        }

        $regTimestamp = strtotime($user['register_date']);

        if (($now - $regTimestamp) > (14 * 86400)) {
            DI::pConfig()->set($uid, 'firstlogin', 'banner_expired', true);
            return;
        }

        DI::pConfig()->set($uid, 'firstlogin', 'first_shown_time', $now);
    } else {
        if (($now - (int)$firstShownTime) > $sevenDaysInSeconds) {
            DI::pConfig()->set($uid, 'firstlogin', 'banner_expired', true);
            return;
        }
    }

    $l10n = DI::l10n();
    $targetUrl = DI::baseUrl() . '/newmember';

    DI::page()->registerStylesheet('addon/firstlogin/firstlogin.css');

    $b .= '
    <div id="firstlogin-banner" class="alert alert-info alert-dismissible firstlogin-alert" role="alert">
        <button type="button" class="close" id="firstlogin-close-btn" data-dismiss="alert" aria-label="' . $l10n->t('Schließen') . '">
            <span aria-hidden="true">&times;</span>
        </button>

        <div class="row firstlogin-row">
            <div class="col-xs-12 col-sm-8 col-md-9 firstlogin-text-col">
                <h4 class="firstlogin-heading">
                    <i class="fa fa-check-square" aria-hidden="true"></i> ' . $l10n->t('Willkommen auf Friendica!') . '
                </h4>
                <p class="firstlogin-text">' . $l10n->t('Um die wichtigsten Profil-Einstellungen vorzunehmen, nutze gerne das Onboarding Tool. Hierüber kannst du dir eine mobile App installieren, dein Profilbild ändern oder Hashtags folgen. Have Fun') . '</p>
            </div>
            <div class="col-xs-12 col-sm-4 col-md-3 firstlogin-btn-col">
                <a href="' . $targetUrl . '" class="btn btn-primary btn-block firstlogin-btn">' . $l10n->t('Onboarding') . ' <i class="fa fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <script>
    document.getElementById("firstlogin-close-btn").addEventListener("click", function() {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", window.location.href, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("firstlogin_dismiss=1");
    });
    </script>';
}
