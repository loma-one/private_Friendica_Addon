<?php
/**
 * Name: Current Date & Weather
 * Description: Shows the current date with weekday, calendar week, and current temperature/sunrise/sunset based on location and timezone on the user's network page.
 * Version: 2.0
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

    $userTimezoneString = DI::pConfig()->get($userId, 'date', 'timezone');

    if (empty($userTimezoneString)) {
        if (method_exists(DI::userSession(), 'getLocalUserTimezone')) {
            $userTimezoneString = DI::userSession()->getLocalUserTimezone();
        } else {
            $userTimezoneString = date_default_timezone_get();
        }
    }

    try {
        $timeZone = new DateTimeZone($userTimezoneString);
    } catch (Exception $e) {
        $timeZone = new DateTimeZone('UTC');
    }

    $now = new DateTime('now', $timeZone);
    $dateFormat = DI::pConfig()->get($userId, 'date', 'date_format') ?? 'd.m.Y';
    $currentDate = $now->format($dateFormat);
    $weekday = DI::l10n()->t($now->format('l'));
    $weekNumber = $now->format('W');

    $location = DI::pConfig()->get($userId, 'date', 'location') ?? 'DE,10115';
    $showSunriseSunset = intval(DI::pConfig()->get($userId, 'date', 'show_sunrise_sunset'));
    $showTemperature = intval(DI::pConfig()->get($userId, 'date', 'show_temperature') ?? 1);

    $sunrise = $sunset = 'N/A';
    $temperature = 'N/A';

    if ($showSunriseSunset || $showTemperature) {
        list($sunrise, $sunset, $temperature) = get_weather_and_sun_data($userId, $location, $timeZone);
    }

    $t = Renderer::getMarkupTemplate('widget.tpl', 'addon/date/');
    $html = Renderer::replaceMacros($t, [
        '$baseurl'            => DI::baseUrl(),
        '$currentDate'        => $currentDate,
        '$weekday'            => $weekday,
        '$weekNumber'         => $weekNumber,
        '$temperature'        => $temperature,
        '$sunrise'            => $sunrise,
        '$sunset'             => $sunset,
        '$showTemperature'    => $showTemperature,
        '$showSunriseSunset'  => $showSunriseSunset,
        '$week_label'         => DI::l10n()->t('Week'),
        '$temp_label'         => DI::l10n()->t('Temperature'),
        '$sunrise_label'      => DI::l10n()->t('Sunrise'),
        '$sunset_label'       => DI::l10n()->t('Sunset'),
    ]);

    DI::page()['aside'] .= $html;
}

function get_weather_and_sun_data(int $userId, string $location, DateTimeZone $timeZone)
{
    $cacheFile = "sunrise_sunset_cache_{$userId}.json";
    $cacheDuration = 1800;

    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        $cacheTime = $cacheData['timestamp'] ?? 0;
        $cachedLocation = $cacheData['location'] ?? '';

        if (time() - $cacheTime < $cacheDuration && $cachedLocation === $location) {
            return [$cacheData['sunrise'], $cacheData['sunset'], $cacheData['temperature']];
        }
    }

    $locationClean = preg_replace('/\s+/', '', $location);
    if (!preg_match('/^[A-Z]{2},[a-zA-Z0-9\-]+$/', $locationClean)) {
        return ['N/A', 'N/A', 'N/A'];
    }

    list($countryCode, $postalCode) = explode(',', $locationClean);
    $apiKey = DI::pConfig()->get($userId, 'date', 'api_key');

    if (empty($apiKey)) {
        return ['N/A', 'N/A', 'N/A'];
    }

    $geocodingUrl = "https://api.opencagedata.com/geocode/v1/json?q=" . urlencode($postalCode) . "+" . urlencode($countryCode) . "&key=" . urlencode($apiKey);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $geocodingUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $geocodingResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$geocodingResponse) {
        return ['N/A', 'N/A', 'N/A'];
    }

    $geocodingData = json_decode($geocodingResponse, true);

    if ($geocodingData && isset($geocodingData['results'][0]['geometry']['lat'], $geocodingData['results'][0]['geometry']['lng'])) {
        $lat = $geocodingData['results'][0]['geometry']['lat'];
        $lng = $geocodingData['results'][0]['geometry']['lng'];
    } else {
        $lat = '52.5200';
        $lng = '13.4050';
    }

    $sunUrl = "https://api.sunrise-sunset.org/json?lat={$lat}&lng={$lng}&date=today&formatted=0";
    $weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lng}&current=temperature_2m";

    $sunrise = $sunset = 'N/A';
    $temperature = 'N/A';

    $chSun = curl_init($sunUrl);
    curl_setopt($chSun, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chSun, CURLOPT_TIMEOUT, 5);
    $sunResponse = curl_exec($chSun);
    curl_close($chSun);

    $chWeather = curl_init($weatherUrl);
    curl_setopt($chWeather, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chWeather, CURLOPT_TIMEOUT, 5);
    $weatherResponse = curl_exec($chWeather);
    curl_close($chWeather);

    if ($sunResponse) {
        $sunData = json_decode($sunResponse, true);
        if ($sunData && isset($sunData['results']['sunrise'])) {
            try {
                $sunrise = (new DateTime($sunData['results']['sunrise']))->setTimezone($timeZone)->format('H:i');
                $sunset = (new DateTime($sunData['results']['sunset']))->setTimezone($timeZone)->format('H:i');
            } catch (Exception $e) {
            }
        }
    }

    if ($weatherResponse) {
        $weatherData = json_decode($weatherResponse, true);
        if ($weatherData && isset($weatherData['current']['temperature_2m'])) {
            $temperature = round($weatherData['current']['temperature_2m'], 1);
        }
    }

    $cacheData = [
        'timestamp' => time(),
        'location' => $locationClean,
        'sunrise' => $sunrise,
        'sunset' => $sunset,
        'temperature' => $temperature,
    ];
    file_put_contents($cacheFile, json_encode($cacheData));

    return [$sunrise, $sunset, $temperature];
}

function date_addon_settings_post($post)
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId || empty($_POST['date-submit'])) {
        return;
    }

    DI::pConfig()->set($userId, 'date', 'date_enable', !empty($_POST['date_enable']) ? 1 : 0);

    if (!empty($_POST['date_format'])) {
        DI::pConfig()->set($userId, 'date', 'date_format', $_POST['date_format']);
    }

    if (isset($_POST['timezone'])) {
            $oldTimezone = DI::pConfig()->get($userId, 'date', 'timezone');
            DI::pConfig()->set($userId, 'date', 'timezone', $_POST['timezone']);

            if ($oldTimezone !== $_POST['timezone']) {
                $cacheFile = "sunrise_sunset_cache_{$userId}.json";
                if (file_exists($cacheFile)) {
                    unlink($cacheFile);
                }
            }
        }

    if (!empty($_POST['location'])) {
        $location = preg_replace('/\s+/', '', $_POST['location']);
        if (preg_match('/^[A-Z]{2},[a-zA-Z0-9\-]+$/', $location)) {
            $oldLocation = DI::pConfig()->get($userId, 'date', 'location');
            DI::pConfig()->set($userId, 'date', 'location', $location);

            if ($oldLocation !== $location) {
                $cacheFile = "sunrise_sunset_cache_{$userId}.json";
                if (file_exists($cacheFile)) {
                    unlink($cacheFile);
                }
            }
        }
    }

    if (!empty($_POST['api_key'])) {
        DI::pConfig()->set($userId, 'date', 'api_key', $_POST['api_key']);
    }

    DI::pConfig()->set($userId, 'date', 'show_sunrise_sunset', !empty($_POST['show_sunrise_sunset']) ? 1 : 0);
    DI::pConfig()->set($userId, 'date', 'show_temperature', !empty($_POST['show_temperature']) ? 1 : 0);
}

function date_addon_settings(array &$data)
{
    $userId = DI::userSession()->getLocalUserId();
    if (!$userId) {
        return;
    }

    $enabled = intval(DI::pConfig()->get($userId, 'date', 'date_enable'));
    $dateFormat = DI::pConfig()->get($userId, 'date', 'date_format') ?? 'd.m.Y';
    $userTimezone = DI::pConfig()->get($userId, 'date', 'timezone') ?? '';
    $location = DI::pConfig()->get($userId, 'date', 'location') ?? 'DE,10115';
    $apiKey = DI::pConfig()->get($userId, 'date', 'api_key') ?? '';
    $showSunriseSunset = intval(DI::pConfig()->get($userId, 'date', 'show_sunrise_sunset'));
    $showTemperature = intval(DI::pConfig()->get($userId, 'date', 'show_temperature') ?? 1);

    $dateFormatOptions = [
        'd.m.Y' => DI::l10n()->t('DD.MM.YYYY'),
        'Y-m-d' => DI::l10n()->t('YYYY-MM-DD'),
        'm/d/Y' => DI::l10n()->t('MM/DD/YYYY'),
    ];

    $timezoneOptions = [
        '' => DI::l10n()->t('Use account timezone'),
    ];

    $regions = [
        DateTimeZone::AFRICA,
        DateTimeZone::AMERICA,
        DateTimeZone::ANTARCTICA,
        DateTimeZone::ASIA,
        DateTimeZone::ATLANTIC,
        DateTimeZone::AUSTRALIA,
        DateTimeZone::EUROPE,
        DateTimeZone::INDIAN,
        DateTimeZone::PACIFIC
    ];

    foreach ($regions as $region) {
        $tzs = DateTimeZone::listIdentifiers($region);
        foreach ($tzs as $tz) {
            $timezoneOptions[$tz] = $tz;
        }
    }

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/date/');
    $html = Renderer::replaceMacros($t, [
        '$enabled' => ['date_enable', DI::l10n()->t('Enable Addon'), $enabled, ''],
        '$date_format' => ['date_format', DI::l10n()->t('Date Format'), $dateFormat, '', $dateFormatOptions],
        '$timezone' => ['timezone', DI::l10n()->t('Timezone'), $userTimezone, '', $timezoneOptions],
        '$show_sunrise_sunset' => ['show_sunrise_sunset', DI::l10n()->t('Show Sunrise and Sunset'), $showSunriseSunset, ''],
        '$show_temperature' => ['show_temperature', DI::l10n()->t('Show Temperature'), $showTemperature, ''],
        '$location' => ['location', DI::l10n()->t('Location (Country code, Postal code)'), $location, 'DE,10115'],
        '$api_key' => ['api_key', DI::l10n()->t('OpenCage API Key'), $apiKey, ''],
        '$description' => DI::l10n()->t('Displays the current date, weather, and sunrise/sunset times.'),
    ]);

    $data = [
        'addon' => 'date',
        'title' => DI::l10n()->t('Current Date & Weather Settings'),
        'html'  => $html,
    ];
}
