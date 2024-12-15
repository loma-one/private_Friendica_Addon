<?php
/**
 * Name: Current Date
 * Description: Shows the current date with weekday, calendar week, day of the year, and remaining days until the new year on the user's network page.
 * Version: 1.3
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

    $now = new DateTime();
    $dateFormat = DI::pConfig()->get($userId, 'date', 'date_format') ?? 'd.m.Y';
    $currentDate = $now->format($dateFormat);
    $weekday = $now->format('l');
    $weekNumber = $now->format('W');
    $location = DI::pConfig()->get($userId, 'date', 'location') ?? 'DE,10115';
    $showSunriseSunset = intval(DI::pConfig()->get($userId, 'date', 'show_sunrise_sunset'));

    if ($showSunriseSunset) {
        list($sunrise, $sunset) = get_sunrise_sunset($location);
    } else {
        $sunrise = $sunset = 'N/A';
    }

    $html = '<div id="curweather-network" class="widget">
        <div class="pull-left">
            <ul class="curdate-details">
                <li><strong>' . DI::l10n()->t($weekday) . ', ' . $currentDate . '</strong></li>
                <li>
                    <img src="' . DI::baseUrl() . '/addon/date/icon/calendar.png" width="20" height="20" alt="Calendar" style="margin-right: 8px;"> Week ' . $weekNumber . '
                </li>';

    if ($showSunriseSunset) {
        $html .= '
                <li>
                    <img src="' . DI::baseUrl() . '/addon/date/icon/sunrise.png" width="20" height="20" alt="Sunrise" style="margin-right: 8px;"> ' . $sunrise . ' h
                </li>
                <li>
                    <img src="' . DI::baseUrl() . '/addon/date/icon/sunset.png" width="20" height="20" alt="Sunset" style="margin-right: 8px;"> ' . $sunset . ' h
                </li>';
    }

    $html .= '
            </ul>
        </div>
        <div class="clear"></div>
    </div>
    <div class="clear"></div>';

    DI::page()['aside'] .= $html;
}

function get_sunrise_sunset($location)
{
    $cacheFile = 'sunrise_sunset_cache.json';
    $cacheDuration = 43200; // 12 hour

    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        $cacheTime = $cacheData['timestamp'] ?? 0;
        $cachedLocation = $cacheData['location'] ?? '';

        if (time() - $cacheTime < $cacheDuration && $cachedLocation === $location) {
            return [$cacheData['sunrise'], $cacheData['sunset']];
        }
    }

    // Remove any whitespace from the location string
    $location = preg_replace('/\s+/', '', $location);

    // Split the location into country code and postal code
    list($countryCode, $postalCode) = explode(',', $location);
    $apiKey = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'date', 'api_key');
    $geocodingUrl = "https://api.opencagedata.com/geocode/v1/json?q={$postalCode}+{$countryCode}&key={$apiKey}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $geocodingUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $geocodingResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$geocodingResponse) {
        return ['N/A', 'N/A'];
    }

    $geocodingData = json_decode($geocodingResponse, true);

    if ($geocodingData && isset($geocodingData['results'][0]['geometry']['lat'], $geocodingData['results'][0]['geometry']['lng'])) {
        $lat = $geocodingData['results'][0]['geometry']['lat'];
        $lng = $geocodingData['results'][0]['geometry']['lng'];
    } else {
        $lat = '52.5200';
        $lng = '13.4050';
    }

    $url = "https://api.sunrise-sunset.org/json?lat={$lat}&lng={$lng}&date=today&formatted=0";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return ['N/A', 'N/A'];
    }

    $data = json_decode($response, true);

    if ($data && isset($data['results'])) {
        $timeZone = new DateTimeZone('Europe/Berlin');
        $sunrise = (new DateTime($data['results']['sunrise']))->setTimezone($timeZone)->format('H:i');
        $sunset = (new DateTime($data['results']['sunset']))->setTimezone($timeZone)->format('H:i');

        // Cache the data
        $cacheData = [
            'timestamp' => time(),
            'location' => $location,
            'sunrise' => $sunrise,
            'sunset' => $sunset,
        ];
        file_put_contents($cacheFile, json_encode($cacheData));

        return [$sunrise, $sunset];
    }

    return ['N/A', 'N/A'];
}

function date_addon_settings_post($post)
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId || empty($_POST['date-submit'])) {
        return;
    }

    // Save addon enable status
    DI::pConfig()->set($userId, 'date', 'date_enable', intval($_POST['date_enable']));

    // Save date format if provided
    if (!empty($_POST['date_format'])) {
        DI::pConfig()->set($userId, 'date', 'date_format', $_POST['date_format']);
    }

    // Save location if valid and clear cache if it changes
    if (!empty($_POST['location'])) {
        $location = preg_replace('/\s+/', '', $_POST['location']); // Remove whitespace
        if (preg_match('/^[A-Z]{2},[0-9]+$/', $location)) { // Validate format (e.g., DE,10115)
            DI::pConfig()->set($userId, 'date', 'location', $location);
            $cacheFile = 'sunrise_sunset_cache.json';
            if (file_exists($cacheFile)) {
                unlink($cacheFile); // Clear cached sunrise/sunset data
            }
        }
    }

    // Save API key if provided
    if (!empty($_POST['api_key'])) {
        DI::pConfig()->set($userId, 'date', 'api_key', $_POST['api_key']);
    }

    // Save sunrise/sunset visibility; default to 0 if checkbox is unchecked
    DI::pConfig()->set($userId, 'date', 'show_sunrise_sunset', !empty($_POST['show_sunrise_sunset']) ? 1 : 0);
}

function date_addon_settings(array &$data)
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId) {
        return;
    }

    $enabled = intval(DI::pConfig()->get($userId, 'date', 'date_enable'));
    $dateFormat = DI::pConfig()->get($userId, 'date', 'date_format') ?? 'd.m.Y';
    $location = DI::pConfig()->get($userId, 'date', 'location') ?? 'DE,10115';
    $apiKey = DI::pConfig()->get($userId, 'date', 'api_key') ?? '';
    $showSunriseSunset = intval(DI::pConfig()->get($userId, 'date', 'show_sunrise_sunset'));

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

    $html = '<span class="description">' . DI::l10n()->t('Displays the current date, including the day of the week, week number, and the day of the year.') . '</span><br>
    <input type="checkbox" name="date_enable" value="1"' . ($enabled ? ' checked="checked"' : '') . '>
    <span>' . DI::l10n()->t('Show date and time for sunrise and sunset') . '</span><br>
    <div class="form-group">
        <span>' . DI::l10n()->t('Date Format:') . '</span><br>
        ' . $dateFormatDropdown . '
    </div>
    <div class="form-group">
        <input type="checkbox" name="show_sunrise_sunset" value="1"' . ($showSunriseSunset ? ' checked="checked"' : '') . '>
        <span>' . DI::l10n()->t('Show Sunrise and Sunset:') . '</span>
    </div>
    <div class="form-group">
        <span>' . DI::l10n()->t('Location (Country code, Postal code = DE,10115):') . '</span><br>
        <input type="text" name="location" value="' . $location . '" style="width: 15ch;">
    </div>
    <div class="form-group">
        <span>' . DI::l10n()->t('API Key:') . '</span><br>
        <span>' . DI::l10n()->t('You can obtain an API key from the <a href="https://opencagedata.com" target="_blank">OpenCage Geocoding API website</a>.') . '</span><br>
        <input type="text" name="api_key" value="' . $apiKey . '" style="width: 30ch;">
    </div>';

    $data = [
        'addon' => 'date',
        'title' => DI::l10n()->t('Current Date Settings'),
        'html'  => $html,
    ];
}
