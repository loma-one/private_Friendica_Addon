<?php
/**
 * Name: Apps
 * Description: Show icon links to various apps and services on the right side of the page.
 * Version: 0.5
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
}

function apps_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $userId = DI::userSession()->getLocalUserId();
    $links = json_decode(DI::pConfig()->get($userId, 'apps', 'links', '[]'), true);

    $form = '<p>' . DI::l10n()->t('Manage your app links below. Enter the URL and label for each app. Only valid links will be displayed.') . '</p>';
    for ($i = 0; $i < 10; $i++) {
        $url = htmlspecialchars($links[$i]['url'] ?? '');
        $label = htmlspecialchars($links[$i]['label'] ?? '');
        $form .= <<<HTML
        <div>
            <label for="apps_link_url_$i">URL:</label>
            <input type="url" name="apps_link_url_$i" value="$url" id="apps_link_url_$i" style="font-weight: normal;" />
            <label for="apps_link_label_$i">Label:</label>
            <input type="text" name="apps_link_label_$i" value="$label" id="apps_link_label_$i" style="font-weight: normal;" />
        </div>
        HTML;
    }

    $data = [
        'addon' => 'apps',
        'title' => DI::l10n()->t('Apps Sidebar Settings'),
        'html'  => $form,
    ];
}

function apps_save_links()
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['apps-submit'])) {
        return;
    }

    $userId = DI::userSession()->getLocalUserId();
    $links = [];

    for ($i = 0; $i < 10; $i++) {
        $url = trim($_POST["apps_link_url_$i"] ?? '');
        $label = trim($_POST["apps_link_label_$i"] ?? '');

        if (!empty($url) && !empty($label) && filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#', $url)) {
            $links[] = ['url' => $url, 'label' => $label];
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

    if (empty($links)) {
        return;
    }

    $html = '<div id="icon_wrapper">';
    foreach ($links as $link) {
        $domain = htmlspecialchars(parse_url($link['url'], PHP_URL_HOST));
        $html .= sprintf(
            '<a href="%s" title="%s" class="open-window">
                <img src="%s" alt="%s" />
            </a>',
            htmlspecialchars($link['url']),
            htmlspecialchars($link['label']),
            apps_get_favicon($domain),
            htmlspecialchars($link['label'])
        );
    }
    $html .= '</div>';

    $b .= $html . apps_styles() . apps_scripts();
}

function apps_get_favicon(string $domain): string
{
    // Primäre Quelle: DuckDuckGo
    $duckDuckGoUrl = sprintf('https://icons.duckduckgo.com/ip3/%s.ico', $domain);

    // Fallback 1: FaviconKit
    $faviconKitUrl = sprintf('https://api.faviconkit.com/%s/32', $domain);

    // Fallback 2: Besticon
    $bestIconUrl = sprintf('https://besticon-demo.herokuapp.com/icon?url=%s&size=32', $domain);

    // JavaScript-gestützte Fallback-Kette
    return sprintf(
        '%s" onerror="this.onerror=null;this.src=\'%s\';this.onerror=function(){this.src=\'%s\';}"',
        $duckDuckGoUrl,
        $faviconKitUrl,
        $bestIconUrl
    );
}

function apps_styles(): string
{
    return <<<CSS
    <style>
        :root {
            --sidebar-bg-color: rgba(221, 221, 221, 0.2);
            --icon-hover-bg-color: rgba(0, 0, 0, 0.1);
            --box-shadow-color: rgba(0, 0, 0, 0.2);
        }

        #icon_wrapper {
            position: fixed;
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            width: 50px;
            background-color: var(--sidebar-bg-color);
            padding: 10px 0;
            box-shadow: -2px 0 5px var(--box-shadow-color);
            border-left: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px 0 0 10px;
        }

        #icon_wrapper a {
            display: block;
            margin-bottom: 10px;
            text-align: center;
        }

        #icon_wrapper img {
            width: 30px;
            height: 30px;
        }

        @media (max-width: 768px) {
            #icon_wrapper {
                display: none;
            }
        }
    </style>
    CSS;
}

function apps_scripts(): string
{
    return <<<JS
    <script>
        document.querySelectorAll(".open-window").forEach(function(link) {
            link.addEventListener("click", function(event) {
                event.preventDefault();
                var width = 800;
                var height = 800;
                var left = (screen.width - width) / 2;
                var top = (screen.height - height) / 2;
                window.open(this.href, "newwindow", "width=" + width + ", height=" + height + ", top=" + top + ", left=" + left);
            });
        });
    </script>
    JS;
}
