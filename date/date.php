<?php
/**
 * Name: Current Date
 * Description: Shows the current date with weekday, calendar week, day of the year, and remaining days until the new year on the user's network page.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function date_install()
{
    Hook::register('network_mod_init', 'addon/date/date.php', 'date_network_mod_init');
    Hook::register('addon_settings', 'addon/date/date.php', 'date_addon_settings');
    Hook::register('addon_settings_post', 'addon/date/date.php', 'date_addon_settings_post');
}

function date_network_mod_init(string &$body)
{
    if (!intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'date', 'date_enable'))) {
        return;
    }

    // Aktuelles Datum
    $now = new DateTime();
    $currentDate = $now->format('d.m.Y'); // Formatierung auf "tt.mm.JJJJ"
    $weekday = $now->format('l'); // z.B. "Monday"
    $weekNumber = $now->format('W'); // Kalenderwoche

    $html = '
    <div id="curweather-network" class="widget">
        <div class="pull-left">
            <ul class="curdate-details"> <!-- Hier wurde die Klasse geändert -->
                <li><strong>' . DI::l10n()->t($weekday) . ', ' . $currentDate . '</strong></li>
                <li>' . DI::l10n()->t('Week') . ': ' . $weekNumber . '</li>
            </ul>
        </div>
        <div class="clear"></div>
    </div>
    <div class="clear"></div>';

    DI::page()['aside'] .= $html;
}

function date_addon_settings_post($post)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['date-submit'])) {
        return;
    }

    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'date', 'date_enable', intval($_POST['date_enable']));
}

function date_addon_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $enabled = intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'date', 'date_enable'));

    $html = '
    <span class="description">' . DI::l10n()->t('Displays the current date, including the day of the week, week number, and the day of the year.') . '</span>
    <br>
    <input type="checkbox" name="date_enable" value="1"' . ($enabled ? ' checked="checked"' : '') . '>
    <label for="date_enable">' . DI::l10n()->t('Show date data') . '</label>
    <br><br>
    ';

    $data = [
        'addon' => 'date',
        'title' => DI::l10n()->t('Current Date Settings'),
        'html'  => $html,
    ];
}
