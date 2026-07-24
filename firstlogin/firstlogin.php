<?php
/**
 * Name: FirstLogin
 * Description: Zeigt neuen Nutzern beim ersten Login ein Onboarding-Banner an, das genau 7 Tage sichtbar bleibt.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Model\User;

function firstlogin_install()
{
    Hook::register('page_header', 'addon/firstlogin/firstlogin.php', 'firstlogin_page_header');
}

function firstlogin_uninstall()
{
    Hook::unregister('page_header', 'addon/firstlogin/firstlogin.php', 'firstlogin_page_header');
}

function firstlogin_page_header(&$b)
{
    $uid = DI::userSession()->getLocalUserId();

    if (!$uid) {
        return;
    }

    $isExpired = DI::pConfig()->get($uid, 'firstlogin', 'banner_expired', false);
    if ($isExpired) {
        return;
    }

    $now = time();
    $sevenDaysInSeconds = 7 * 86400; // 7 Tage

    $firstShownTime = DI::pConfig()->get($uid, 'firstlogin', 'first_shown_time', null);

    if ($firstShownTime === null) {

        $user = User::getById($uid);
        if (!$user || empty($user['register_date'])) {
            return;
        }

        $regTimestamp = strtotime($user['register_date']);

        // HINWEIS FÜR TEST-ACCOUNTS: Wenn dein Account älter als 14 Tage ist,
        // greift dieser Block. Für echte neue User greift er nicht.
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

    $b .= '<div class="firstlogin-welcome-banner alert alert-info text-center" style="margin: 15px;">';
    $b .= $l10n->t('Welcome! Here you can get additional information about onboarding on Friendica. Learn more about the features that Friendica offers you:') . ' ';
    $b .= '<a href="' . $targetUrl . '" class="btn btn-sm btn-primary ml-2">' . $l10n->t('To Onboarding') . '</a>';
    $b .= '</div>';
}
