<?php
/**
 * Name: Apps
 * Description: Zeigt eine anpassbare Sidebar mit App-Links auf der rechten oder linken Seite an. Dynamische Eingabefelder (1 bis max. 10).
 * Version: 2.1
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

    if (count($form_links) < 10) {
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
        if (!$host) continue;

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

    $b .= $html . apps_styles($position) . apps_scripts();
}

function apps_styles(string $position): string
{
    $side = ($position === 'left') ? 'left: 0; border-radius: 0 10px 10px 0; border-right: 1px solid rgba(0,0,0,0.1);' : 'right: 0; border-radius: 10px 0 0 10px; border-left: 1px solid rgba(0,0,0,0.1);';

    return "<style>
        #icon_wrapper { position: fixed; top: 50%; $side transform: translateY(-50%); width: 50px; background: rgba(221,221,221,0.3); padding: 10px 0; z-index: 9999; backdrop-filter: blur(5px); }
        .app-link { display: block; margin: 10px 0; text-align: center; transition: transform 0.2s; }
        .app-link:hover { transform: scale(1.1); }
        .app-icon { width: 30px; height: 30px; border-radius: 4px; object-fit: contain; }
        @media (max-width: 768px) { #icon_wrapper { display: none; } }
    </style>";
}

function apps_scripts(): string
{
    return <<<JS
<script>
    (function() {
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".app-icon").forEach(img => {
                img.addEventListener("error", function() {
                    let fallbacks = this.dataset.fallbacks ? this.dataset.fallbacks.split(",") : [];
                    if (fallbacks.length > 0) {
                        let next = fallbacks.shift();
                        this.dataset.fallbacks = fallbacks.join(",");
                        this.src = next;
                    } else {
                        this.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23666' d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z'/%3E%3C/svg%3E";
                        this.onerror = null;
                    }
                });
            });

            document.querySelectorAll(".app-link[data-popup='1']").forEach(link => {
                link.addEventListener("click", function(e) {
                    e.preventDefault();
                    const width = 480;
                    const height = 800;

                    const left = window.screenX + window.outerWidth - width - 48;
                    const top = window.screenY + (window.outerHeight - height) / 2;

                    const features = "width=" + width + ",height=" + height +
                                     ",top=" + top + ",left=" + left +
                                     ",scrollbars=yes,resizable=yes";

                    window.open(this.href, '_blank', features);
                });
            });
        });
    })();
</script>
JS;
}
