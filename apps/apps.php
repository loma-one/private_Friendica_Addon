<?php
/**
 * Name: Apps
 * Description: Show icon links to various apps and services on the right side of the page.
 * Version: 1.5
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

    // 1. Leere Einträge vollständig entfernen
    $links = array_values(array_filter($links, function($link) {
        return !empty($link['url']) && !empty($link['label']);
    }));

    // 2. Falls weniger als 10 Einträge, eine leere Zeile anhängen
    if (count($links) < 10) {
        $links[] = ['url' => '', 'label' => '', 'open_in_new_tab' => false];
    }

    $form = '<p>' . DI::l10n()->t('Manage your app links below. Enter the URL and label for each app. Only valid links will be displayed.') . '</p>';

    $totalFields = min(count($links), 10);

    // Kopfzeile nur einmal
    $form .= <<<HTML
    <div style="margin-bottom:5px; font-weight:bold;">
        <span style="display:inline-block; width:45%; margin-right:5px;">URL</span>
        <span style="display:inline-block; width:35%; margin-right:5px;">Label</span>
        <span style="display:inline-block;">New Tab</span>
    </div>
    HTML;

    for ($i = 0; $i < $totalFields; $i++) {
        $url = htmlspecialchars($links[$i]['url'] ?? '');
        $label = htmlspecialchars($links[$i]['label'] ?? '');
        $openInNewTab = !empty($links[$i]['open_in_new_tab']) ? 'checked' : '';

        $form .= <<<HTML
        <div class="form-group" style="margin-bottom:5px;">
            <input type="url" name="apps_link_url_$i" value="$url" placeholder="URL"
                class="form-control" style="width:45%; display:inline-block; margin-right:5px;" />
            <input type="text" name="apps_link_label_$i" value="$label" placeholder="Label"
                class="form-control" style="width:40%; display:inline-block; margin-right:5px;" />
            <label style="display:inline-block;">
                <input type="checkbox" name="apps_link_new_tab_$i" id="apps_link_new_tab_$i" $openInNewTab />
            </label>
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
        $openInNewTab = isset($_POST["apps_link_new_tab_$i"]);

        if (!empty($url) && !empty($label) && filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#', $url)) {
            $links[] = ['url' => $url, 'label' => $label, 'open_in_new_tab' => $openInNewTab];
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
        $isNewTab = !empty($link['open_in_new_tab']);
        $target = $isNewTab ? '_blank' : '_self';
        $dataOpen = $isNewTab ? '1' : '0';

        $html .= sprintf(
            '<a href="%s" title="%s" class="open-window" target="%s" data-open-in-new-tab="%s">
                <img src="%s" alt="%s" />
            </a>',
            htmlspecialchars($link['url']),
            htmlspecialchars($link['label']),
            $target,
            $dataOpen,
            apps_get_favicon($domain),
            htmlspecialchars($link['label'])
        );
    }
    $html .= '</div>';

    $b .= $html . apps_styles() . apps_scripts();
}

function apps_get_favicon(string $domain): string
{
    $duckDuckGoUrl = sprintf('https://icons.duckduckgo.com/ip3/%s.ico', $domain);
    $faviconKitUrl = sprintf('https://api.faviconkit.com/%s/32', $domain);
    $bestIconUrl = sprintf('https://besticon-demo.herokuapp.com/icon?url=%s&size=32', $domain);

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
    </style>
    CSS;
}

function apps_scripts(): string
{
    return <<<JS
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".open-window").forEach(function(link) {
                link.addEventListener("click", function(event) {
                    var openInNewTab = this.getAttribute("data-open-in-new-tab") === "1";

                    if (!openInNewTab) {
                        event.preventDefault();
                        var width = 480;
                        var height = 800;
                        var left = window.screenX + window.outerWidth - width - 48;
                        var top = window.screenY + (window.outerHeight - height) / 2;
                        var windowFeatures = "width=" + width + ",height=" + height +
                                             ",top=" + top + ",left=" + left +
                                             ",scrollbars=yes,resizable=yes";
                        window.open(this.href, "_blank", windowFeatures);
                    }
                });
            });
        });
    </script>
    JS;
}
