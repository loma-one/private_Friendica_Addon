<?php

/**
 * Name: Fediverse Timecapsule
 * Description: Displays a memorial banner on the profile and posts a predefined message after a period of inactivity.
 * Version: 1.1.1
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Util\DateTimeFormat;

function timecapsule_install()
{
    Hook::register('cron_daily', __FILE__, 'timecapsule_cron_daily');
    Hook::register('addon_settings', __FILE__, 'timecapsule_settings');
    Hook::register('addon_settings_post', __FILE__, 'timecapsule_settings_post');
    Hook::register('page_content_top', __FILE__, 'timecapsule_page_content_top');
}

function timecapsule_uninstall()
{
    Hook::unregister('cron_daily', __FILE__, 'timecapsule_cron_daily');
    Hook::unregister('addon_settings', __FILE__, 'timecapsule_settings');
    Hook::unregister('addon_settings_post', __FILE__, 'timecapsule_settings_post');
    Hook::unregister('page_content_top', __FILE__, 'timecapsule_page_content_top');
}

function timecapsule_page_content_top(string &$b)
{
    $owner = DI::appHelper()->getProfileOwner();
    if (!$owner) {
        return;
    }

    $enabled = (int) DI::pConfig()->get($owner, 'timecapsule', 'enabled', 0);
    $stage   = (int) DI::pConfig()->get($owner, 'timecapsule', 'stage', 0);
    $months  = (int) DI::pConfig()->get($owner, 'timecapsule', 'months', 6);

    if (!$enabled || ($stage < 1 && $months !== 0)) {
        return;
    }

    $customMessage = trim(DI::pConfig()->get($owner, 'timecapsule', 'message', ''));
    $warning       = !empty($customMessage)
        ? nl2br(htmlspecialchars($customMessage, ENT_QUOTES, 'UTF-8'))
        : DI::l10n()->t('This account is currently in a memorial status. The owner has configured an automated service to manage their digital presence in case of long-term inactivity.');

    $b = '<div id="timecapsule-memorial-banner" class="alert alert-warning text-center" style="margin-bottom: 20px;">'
       . '<i class="ri-information-line" aria-hidden="true"></i> ' . $warning
       . '</div>' . $b;
}

function timecapsule_settings(array &$data)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    $monthOptions = [
        0  => DI::l10n()->t('Immediately'),
        3  => DI::l10n()->tt('%d month', '%d months', 3),
        6  => DI::l10n()->tt('%d month', '%d months', 6),
        12 => DI::l10n()->tt('%d month', '%d months', 12),
        24 => DI::l10n()->tt('%d month', '%d months', 24)
    ];

    $visibilityOptions = [
        '<public>'   => DI::l10n()->t('Public'),
        '<contacts>' => DI::l10n()->t('Following'),
        '<disabled>' => DI::l10n()->t('No Fediverse Post')
    ];

    $tplVars = [
        '$title'       => DI::l10n()->t('Fediverse Timecapsule'),
        '$description' => DI::l10n()->t('Settings for automatic memorial status upon inactivity.'),
        '$enabled'     => ['timecapsule_enabled', DI::l10n()->t('Activate timecapsule'), DI::pConfig()->get($uid, 'timecapsule', 'enabled', 0), ''],
        '$months'      => ['timecapsule_months', DI::l10n()->t('Inactivity period'), (int) DI::pConfig()->get($uid, 'timecapsule', 'months', 6), '', $monthOptions],
        '$visibility'  => ['timecapsule_visibility', DI::l10n()->t('Fediverse post visibility'), DI::pConfig()->get($uid, 'timecapsule', 'visibility', '<public>'), '', $visibilityOptions],
        '$message'     => ['timecapsule_message', DI::l10n()->t('Custom memorial message (optional)'), DI::pConfig()->get($uid, 'timecapsule', 'message', ''), DI::l10n()->t('If filled, this text will replace the default memorial notification in your profile banner.')]
    ];

    $html = Renderer::replaceMacros(Renderer::getMarkupTemplate('settings.tpl', 'addon/timecapsule/'), $tplVars);
    $data = ['addon' => 'timecapsule', 'title' => DI::l10n()->t('Fediverse Timecapsule'), 'html' => $html];
}

function timecapsule_settings_post(array &$b)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid || (!isset($_POST['timecapsule-submit']) && !isset($_POST['timecapsule_enabled']))) {
        return;
    }

    $enabled    = (int) ($_POST['timecapsule_enabled'] ?? 0);
    $months     = (int) ($_POST['timecapsule_months'] ?? 6);
    $visibility = trim($_POST['timecapsule_visibility'] ?? '<public>');

    DI::pConfig()->set($uid, 'timecapsule', 'enabled', $enabled);
    DI::pConfig()->set($uid, 'timecapsule', 'months', $months);
    DI::pConfig()->set($uid, 'timecapsule', 'message', trim($_POST['timecapsule_message'] ?? ''));

    if (in_array($visibility, ['<public>', '<contacts>', '<disabled>'], true)) {
        DI::pConfig()->set($uid, 'timecapsule', 'visibility', $visibility);
    }

    if ($enabled && $months === 0) {
        DI::pConfig()->set($uid, 'timecapsule', 'stage', 1);
        DI::pConfig()->set($uid, 'timecapsule', 'warned_at', time());
    } elseif (!$enabled) {
        DI::pConfig()->set($uid, 'timecapsule', 'stage', 0);
    }
}

function timecapsule_cron_daily(&$a, &$b)
{
    $users = DBA::select('pconfig', ['uid'], ['cat' => 'timecapsule', 'k' => 'enabled', 'v' => '1']);
    if (!DBA::isResult($users)) {
        return;
    }

    while ($row = DBA::fetch($users)) {
        $uid  = (int) $row['uid'];
        $user = DBA::selectFirst('user', ['uid', 'aid', 'username', 'nickname', 'email', 'login_date', 'url'], ['uid' => $uid]);
        if (!$user) {
            continue;
        }

        $lastLoginTS = !empty($user['login_date']) && $user['login_date'] !== '0000-00-00 00:00:00'
            ? DateTimeFormat::utc($user['login_date'])->getTimestamp()
            : 0;

        if ($lastLoginTS === 0) {
            continue;
        }

        $months    = (int) DI::pConfig()->get($uid, 'timecapsule', 'months', 6);
        $threshold = $months * 30 * 86400;
        $stage     = (int) DI::pConfig()->get($uid, 'timecapsule', 'stage', 0);
        $now       = time();

        if ($months === 0) {
            if ($stage === 0) {
                DI::pConfig()->set($uid, 'timecapsule', 'stage', 1);
                DI::pConfig()->set($uid, 'timecapsule', 'warned_at', $now);
                $stage = 1;
            }
        } elseif ($lastLoginTS > ($now - 86400)) {
            if ($stage > 0) {
                DI::pConfig()->set($uid, 'timecapsule', 'stage', 0);
            }
            continue;
        }

        if ($stage === 0 && $months > 0 && ($now - $lastLoginTS) > $threshold) {
            DI::pConfig()->set($uid, 'timecapsule', 'stage', 1);
            DI::pConfig()->set($uid, 'timecapsule', 'warned_at', $now);

            if (!empty($user['email'])) {
                $siteName    = DI::config()->get('config', 'sitename');
                $senderEmail = DI::config()->get('system', 'email_address') ?: DI::config()->get('config', 'sender_email');
                $subject     = DI::l10n()->t('Timecapsule: Memorial status activated for your account');
                $body        = DI::l10n()->t(
                    "Hello %s,\n\nYour account on %s has been inactive for the configured period (%d months). Your profile has now been set to memorial status.\n\nIf you do not log in within the next 14 days, your memorial message will automatically be posted to the Fediverse.\n\nTo prevent this, simply log in to your account.",
                    $user['username'],
                    $siteName,
                    $months
                );

                DI::email()->send([
                    'fromName'  => $siteName,
                    'fromEmail' => $senderEmail,
                    'toEmail'   => $user['email'],
                    'message'   => $body,
                    'subject'   => $subject
                ]);
            }
        } elseif ($stage === 1) {
            $warnedAt = (int) DI::pConfig()->get($uid, 'timecapsule', 'warned_at', 0);
            if (($now - $warnedAt) > (14 * 86400)) {
                timecapsule_trigger_final_action($uid, $user);
                DI::pConfig()->set($uid, 'timecapsule', 'stage', 2);
            }
        }
    }

    DBA::close($users);
}

function timecapsule_trigger_final_action(int $uid, array $user)
{
    $visibility = DI::pConfig()->get($uid, 'timecapsule', 'visibility', '<public>');
    if ($visibility === '<disabled>') {
        return;
    }

    $customMessage = trim(DI::pConfig()->get($uid, 'timecapsule', 'message', ''));
    $postBody      = !empty($customMessage)
        ? $customMessage
        : DI::l10n()->t('The account of %s has been inactive for the configured period and is now in memorial status.', $user['username']);

    $profileUrl = !empty($user['url']) ? $user['url'] : DI::baseUrl() . '/profile/' . $user['nickname'];

    Item::insert([
        'uid'         => $uid,
        'aid'         => (int) ($user['aid'] ?? 0),
        'wall'        => 1,
        'body'        => $postBody,
        'allow_cid'   => ($visibility === '<public>') ? '' : '<' . $uid . '>',
        'private'     => ($visibility === '<public>') ? 0 : 1,
        'owner-name'  => $user['username'],
        'author-name' => $user['username'],
        'owner-link'  => $profileUrl,
        'author-link' => $profileUrl,
    ]);
}
