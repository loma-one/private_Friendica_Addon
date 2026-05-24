<?php

/**
 * Name: Fediverse Timecapsule
 * Description: [BETA] Sends a predefined message via templates to a trusted person after a defined period of inactivity.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Database\DBA;
use Friendica\Library\Email;

function timecapsule_install()
{
    Hook::register('cron_daily', 'addon/timecapsule/timecapsule.php', 'timecapsule_cron_daily');
    Hook::register('addon_settings', 'addon/timecapsule/timecapsule.php', 'timecapsule_settings');
    Hook::register('addon_settings_post', 'addon/timecapsule/timecapsule.php', 'timecapsule_settings_post');
    Hook::register('plugin_settings', 'addon/timecapsule/timecapsule.php', 'timecapsule_admin');
    Hook::register('plugin_settings_post', 'addon/timecapsule/timecapsule.php', 'timecapsule_admin_post');
}

function timecapsule_uninstall()
{
    Hook::unregister('cron_daily', 'addon/timecapsule/timecapsule.php', 'timecapsule_cron_daily');
    Hook::unregister('addon_settings', 'addon/timecapsule/timecapsule.php', 'timecapsule_settings');
    Hook::unregister('addon_settings_post', 'addon/timecapsule/timecapsule.php', 'timecapsule_settings_post');
    Hook::unregister('plugin_settings', 'addon/timecapsule/timecapsule.php', 'timecapsule_admin');
    Hook::unregister('plugin_settings_post', 'addon/timecapsule/timecapsule.php', 'timecapsule_admin_post');
}

function timecapsule_settings(array &$data)
{
    $uid = DI::session()->get('uid');

    if (!$uid) {
        return;
    }

    $enabled    = DI::pConfig()->get($uid, 'timecapsule', 'enabled', 0);
    $monthsVal  = DI::pConfig()->get($uid, 'timecapsule', 'months', 6);
    $visibility = DI::pConfig()->get($uid, 'timecapsule', 'visibility', '<public>');
    $recipient  = DI::pConfig()->get($uid, 'timecapsule', 'recipient', '');
    $message    = DI::pConfig()->get($uid, 'timecapsule', 'message', '');

    $monthOptions = [];
    foreach ([3, 6, 12, 24] as $m) {
        $monthOptions[$m] = DI::l10n()->tt('%d month', '%d months', $m);
    }

    $visibilityOptions = [
        '<public>'   => DI::l10n()->t('Public'),
        '<contacts>' => DI::l10n()->t('Following'),
        '<friends>'  => DI::l10n()->t('Mutual Friends'),
        '<disabled>' => DI::l10n()->t('No Fediverse Post (Email Only)')
    ];

    $tplVars = [
        '$title' => DI::l10n()->t('Fediverse Timecapsule'),
        '$description' => DI::l10n()->t('Settings for automatic notification upon inactivity. Multiple email addresses can be entered separated by commas.'),
        '$enabled' => ['timecapsule_enabled', DI::l10n()->t('Activate timecapsule'), $enabled, ''],
        '$months' => ['timecapsule_months', DI::l10n()->t('Inactivity period'), $monthsVal, '', $monthOptions],
        '$visibility' => ['timecapsule_visibility', DI::l10n()->t('Fediverse post visibility'), $visibility, '', $visibilityOptions],
        '$recipient' => ['timecapsule_recipient', DI::l10n()->t('Email address(es) of the trusted person(s)'), $recipient, ''],
        '$message' => ['timecapsule_message', DI::l10n()->t('Your message to be transmitted'), $message, '']
    ];

    $tpl  = Renderer::getMarkupTemplate('settings.tpl', 'addon/timecapsule/');
    $html = Renderer::replaceMacros($tpl, $tplVars);

    // Separator and additional information
    $html .= '<hr style="border-top: 1px solid #ddd; margin: 20px 0;">';
    $html .= '<p class="help-block">' . DI::l10n()->t('You can use this addon to send a message when you are no longer able to do so yourself. If you do not log in to your Friendica account after the set time, you will receive a notification at your email address. If you do not log back into your account within 14 days, the stored message will be sent automatically.') . '</p>';

    $data = [
        'addon' => 'timecapsule',
        'title' => DI::l10n()->t('Fediverse Timecapsule'),
        'html'  => $html
    ];
}

function timecapsule_settings_post(array &$b)
{
    $uid = DI::session()->get('uid');
    if (!$uid) return;

    if (isset($_POST['timecapsule-submit']) || isset($_POST['timecapsule_enabled'])) {
        DI::pConfig()->set($uid, 'timecapsule', 'enabled', intval($_POST['timecapsule_enabled'] ?? 0));
        DI::pConfig()->set($uid, 'timecapsule', 'months', intval($_POST['timecapsule_months'] ?? 6));

        $visibility = trim($_POST['timecapsule_visibility'] ?? '<public>');
        if (in_array($visibility, ['<public>', '<contacts>', '<friends>', '<disabled>'])) {
            DI::pConfig()->set($uid, 'timecapsule', 'visibility', $visibility);
        }

        $rawEmail = trim($_POST['timecapsule_recipient'] ?? '');
        $emailParts = explode(',', $rawEmail);
        $validEmails = [];
        foreach ($emailParts as $part) {
            $clean = trim($part);
            if (filter_var($clean, FILTER_VALIDATE_EMAIL)) $validEmails[] = $clean;
        }
        DI::pConfig()->set($uid, 'timecapsule', 'recipient', implode(',', $validEmails));
        DI::pConfig()->set($uid, 'timecapsule', 'message', strip_tags(trim($_POST['timecapsule_message'] ?? '')));
        DI::pConfig()->set($uid, 'timecapsule', 'stage', 0);
        DI::pConfig()->set($uid, 'timecapsule', 'warned_at', 0);
    }
}

function timecapsule_admin(array &$data)
{
    if (!DI::app()->isSiteAdmin()) return;
    $adminContact = DI::config()->get('timecapsule', 'admin_contact', '');
    $tplVars = [
        '$title' => DI::l10n()->t('Timecapsule Addon Admin Settings'),
        '$description' => DI::l10n()->t('Global configuration for the Timecapsule addon.'),
        '$submit' => DI::l10n()->t('Save'),
        '$admin_contact' => ['timecapsule_admin_contact', DI::l10n()->t('Global contact email'), $adminContact, '']
    ];
    $html = Renderer::replaceMacros(Renderer::getMarkupTemplate('admin.tpl', 'addon/timecapsule/'), $tplVars);
    $data = ['addon' => 'timecapsule', 'title' => 'Timecapsule Admin', 'html' => $html];
}

function timecapsule_admin_post(array &$b)
{
    if (DI::app()->isSiteAdmin() && isset($_POST['timecapsule_admin_contact'])) {
        DI::config()->set('timecapsule', 'admin_contact', strip_tags(trim($_POST['timecapsule_admin_contact'])));
    }
}

function timecapsule_cron_daily(&$a, &$b)
{
    $users = DBA::select('pconfig', ['uid'], ['cat' => 'timecapsule', 'k' => 'enabled', 'v' => '1']);
    if (!DBA::isResult($users)) return;

    foreach ($users as $row) {
        $uid = intval($row['uid']);
        $user = DBA::selectFirst('user', ['uid', 'username', 'email', 'login_date'], ['uid' => $uid]);
        if (!$user) continue;

        $months = intval(DI::pConfig()->get($uid, 'timecapsule', 'months', 6));
        $stage = intval(DI::pConfig()->get($uid, 'timecapsule', 'stage', 0));
        $threshold = $months * 30 * 24 * 60 * 60;
        $now = time();
        $lastLogin = strtotime($user['login_date']);

        if ($stage === 0 && ($now - $lastLogin) > $threshold) {
            timecapsule_send_warning_mail($user);
            DI::pConfig()->set($uid, 'timecapsule', 'stage', 1);
            DI::pConfig()->set($uid, 'timecapsule', 'warned_at', $now);
        } elseif ($stage === 1 && ($now - $lastLogin) < $threshold) {
            DI::pConfig()->set($uid, 'timecapsule', 'stage', 0);
        } elseif ($stage === 1 && ($now - intval(DI::pConfig()->get($uid, 'timecapsule', 'warned_at', 0))) > (14 * 24 * 60 * 60)) {
            timecapsule_trigger_final_action($uid, $user);
            DI::pConfig()->set($uid, 'timecapsule', 'stage', 2);
        }
    }
}

function timecapsule_send_warning_mail(array $user)
{
    $subject = sprintf(DI::l10n()->t('Inactivity warning for %s'), DI::baseUrl());
    $body = DI::l10n()->t("Hello %s,\n\nYou haven't logged in for a long time. If you don't log in within 14 days, your timecapsule message will be sent.", $user['username']);
    Email::send($user['email'], $subject, $body, DI::config()->get('config', 'sender_email'));
}

function timecapsule_trigger_final_action(int $uid, array $user)
{
    $recipients = explode(',', DI::pConfig()->get($uid, 'timecapsule', 'recipient', ''));
    $message = DI::pConfig()->get($uid, 'timecapsule', 'message', '');
    if (empty($message)) return;

    foreach ($recipients as $email) {
        if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            Email::send(trim($email), 'Timecapsule Message', $message, DI::config()->get('config', 'sender_email'));
        }
    }

    $visibility = DI::pConfig()->get($uid, 'timecapsule', 'visibility', '<public>');
    if ($visibility === '<disabled>') return;

    \Friendica\Model\Post::create([
        'uid' => $uid,
        'body' => $message,
        'allow_gid' => ($visibility === '<public>') ? '' : $visibility,
        'private' => ($visibility === '<public>') ? 0 : 1,
        'owner-name' => $user['username'],
        'author-name' => $user['username']
    ]);
}
