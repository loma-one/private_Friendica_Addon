<?php
/**
 * Name: Apps
 * Description: Zeigt eine anpassbare Sidebar mit App-Links auf der rechten oder linken Seite an. Dynamische Eingabefelder (1 bis max. 10). SPA-kompatibel.
 * Version: 2.2
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function apps_install()
{
    Hook::register('page_end', __FILE__, 'apps_render');
    Hook::register('addon_settings', __FILE__, 'apps_settings');
    Hook::register('addon_settings_post', __FILE__, 'apps_save_links');
}

function apps_uninstall()
{
    Hook::unregister('page_end', __FILE__, 'apps_render');
    Hook::unregister('addon_settings', __FILE__, 'apps_settings');
    Hook::unregister('addon_settings_post', __FILE__, 'apps_save_links');
}

function apps_get_sources(string $domain): array
{
    $sources = [
        "https://icon.horse/icon/" . $domain,
        "https://icons.duckduckgo.com/ip3/" . $domain . ".ico",
        "https://www.google.com/s2/favicons?domain=" . $domain . "&sz=32"
    ];

    if (method_exists(DI::class, 'proxy')) {
        return array_map(fn($src) => DI::proxy()->url($src), $sources);
    }

    return $sources;
}

function apps_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $userId = DI::userSession()->getLocalUserId();
    $links = json_decode(DI::pConfig()->get($userId, 'apps', 'links', '[]'), true);
    $position = DI::pConfig()->get($userId, 'apps', 'position', 'right');

    $form_links = array_values(array_filter((array)$links, function($link) {
        return !empty($link['url']) && !empty($link['label']);
    }));

    if (empty($form_links)) {
        $form_links[] = ['url' => '', 'label' => '', 'open_in_new_tab' => false];
    }

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/apps/');
    $html = Renderer::replaceMacros($t, [
        '$title' => DI::l10n()->t('Apps Sidebar Settings'),
        '$desc' => DI::l10n()->t('Manage your app links. Choose position and enter URLs.'),
        '$label_pos' => DI::l10n()->t('Sidebar Position'),
        '$position' => $position,
        '$links' => $form_links,
    ]);

    $data = [
        'addon' => 'apps',
        'title' => DI::l10n()->t('Apps Sidebar'),
        'html'  => $html,
    ];
}

function apps_save_links()
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['apps-submit'])) {
        return;
    }

    $userId = DI::userSession()->getLocalUserId();
    DI::pConfig()->set($userId, 'apps', 'position', $_POST['apps_position'] ?? 'right');

    $links = [];
    for ($i = 0; $i < 10; $i++) {
        $url = trim($_POST["apps_link_url_$i"] ?? '');
        $label = trim($_POST["apps_link_label_$i"] ?? '');

        if (!empty($url) && !empty($label) && filter_var($url, FILTER_VALIDATE_URL)) {
            $links[] = [
                'url' => $url,
                'label' => $label,
                'open_in_new_tab' => isset($_POST["apps_link_new_tab_$i"])
            ];
        }
    }

    DI::pConfig()->set($userId, 'apps', 'links', json_encode($links));
}

function apps_render(string &$b)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $userId = DI::userSession()->getLocalUserId();
    $links = json_decode(DI::pConfig()->get($userId, 'apps', 'links', '[]'), true);
    $position = DI::pConfig()->get($userId, 'apps', 'position', 'right');

    if (empty($links)) {
        return;
    }

    $html = '<div id="icon_wrapper" class="apps-sidebar-' . $position . '">';
    foreach ($links as $link) {
        $host = parse_url($link['url'], PHP_URL_HOST);
        if (!$host) {
            continue;
        }

        $sources = apps_get_sources($host);
        $initialImg = array_shift($sources);
        $isNewTab = !empty($link['open_in_new_tab']);

        $html .= sprintf(
            '<a href="%s" title="%s" class="app-link" target="%s" data-popup="%s">
                <img src="%s" data-fallbacks="%s" alt="" class="app-icon" />
            </a>',
            htmlspecialchars($link['url']),
            htmlspecialchars($link['label']),
            $isNewTab ? '_blank' : '_self',
            $isNewTab ? '0' : '1',
            $initialImg,
            htmlspecialchars(implode(',', $sources))
        );
    }
    $html .= '</div>';

    $cssPath = DI::baseUrl() . '/addon/apps/styles.css';
    $jsPath = DI::baseUrl() . '/addon/apps/app.js';

    $assets = '<link rel="stylesheet" href="' . $cssPath . '">';
    $assets .= '<script src="' . $jsPath . '"></script>';

    $b .= $html . $assets;
}
