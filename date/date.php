<?php
/**
 * Name: Current Date
 * Description: Shows the current date with weekday, calendar week, day of the year, and remaining days until the new year on the user's network page.
 * Version: 1.1
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
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId || !intval(DI::pConfig()->get($userId, 'date', 'date_enable'))) {
        return;
    }

    // Aktuelles Datum
    $now = new DateTime();

    // Benutzerdefiniertes Datumsformat
    $dateFormat = DI::pConfig()->get($userId, 'date', 'date_format') ?? 'd.m.Y'; // Standard: TT.MM.JJJJ
    $currentDate = $now->format($dateFormat); // Formatierung des Datums
    $weekday = $now->format('l'); // z.B. "Monday"
    $weekNumber = $now->format('W'); // Kalenderwoche

    $html = '
    <div id="curweather-network" class="widget">
        <div class="pull-left">
            <ul class="curdate-details">
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
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId || empty($_POST['date-submit'])) {
        return;
    }

    DI::pConfig()->set($userId, 'date', 'date_enable', intval($_POST['date_enable']));

    // Speichern des benutzerdefinierten Datumsformats
    if (isset($_POST['date_format'])) {
        DI::pConfig()->set($userId, 'date', 'date_format', $_POST['date_format']);
    }
}

function date_addon_settings(array &$data)
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId) {
        return;
    }

    $enabled = intval(DI::pConfig()->get($userId, 'date', 'date_enable'));
    $dateFormat = DI::pConfig()->get($userId, 'date', 'date_format') ?? 'd.m.Y';

    // Dropdown für Datumsformate
    $dateFormatOptions = [
        'd.m.Y' => DI::l10n()->t('DD.MM.YYYY'),
        'Y-m-d' => DI::l10n()->t('YYYY-MM-DD'),
        'm/d/Y' => DI::l10n()->t('MM/DD/YYYY'),
    ];

    $dateFormatDropdown = '<select name="date_format">';
    foreach ($dateFormatOptions as $format => $label) {
        $selected = ($dateFormat === $format) ? ' selected="selected"' : '';
        $dateFormatDropdown .= '<option value="' . $format . '"' . $selected . '>' . $label . '</option>';
    }
    $dateFormatDropdown .= '</select>';

    $html = '
    <span class="description">' . DI::l10n()->t('Displays the current date, including the day of the week, week number, and the day of the year.') . '</span>
    <br>
    <input type="checkbox" name="date_enable" value="1"' . ($enabled ? ' checked="checked"' : '') . '>
    <label for="date_enable">' . DI::l10n()->t('Show date data') . '</label>
    <br><br>
    <label for="date_format">' . DI::l10n()->t('Date Format') . ':</label>
    ' . $dateFormatDropdown . '
    <br><br>
    ';

    $data = [
        'addon' => 'date',
        'title' => DI::l10n()->t('Current Date Settings'),
        'html'  => $html,
    ];
}
