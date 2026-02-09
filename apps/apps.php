<?php
/**
 * Name: Apps
 * Description: Zeigt eine anpassbare Sidebar mit App-Links auf der rechten oder linken Seite an. Dynamische Eingabefelder (1 bis max. 10).
 * Version: 1.8
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

function apps_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $userId = DI::userSession()->getLocalUserId();
    $links = json_decode(DI::pConfig()->get($userId, 'apps', 'links', '[]'), true);
    $position = DI::pConfig()->get($userId, 'apps', 'position', 'right');

    // Nur tatsächlich ausgefüllte Links für die Anzeige vorbereiten
    $form_links = array_values(array_filter((array)$links, function($link) {
        return !empty($link['url']) && !empty($link['label']);
    }));

    // Ein freies Feld hinzufügen, falls das Limit von 10 noch nicht erreicht ist
    if (count($form_links) < 10) {
        $form_links[] = ['url' => '', 'label' => '', 'open_in_new_tab' => false];
    }

    $t = Renderer::getMarkupTemplate('settings.tpl', 'addon/apps/');
    $html = Renderer::replaceMacros($t, [
        '$title' => DI::l10n()->t('Apps Sidebar Settings'),
        '$desc' => DI::l10n()->t('Manage your app links below. Choose the sidebar position and enter URL/Labels.'),
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

    if (isset($_POST['apps_position'])) {
        DI::pConfig()->set($userId, 'apps', 'position', $_POST['apps_position']);
    }

    $links = [];
    // Wir prüfen alle potenziellen 10 Indizes ab
    for ($i = 0; $i < 10; $i++) {
        $url = trim($_POST["apps_link_url_$i"] ?? '');
        $label = trim($_POST["apps_link_label_$i"] ?? '');
        $openInNewTab = isset($_POST["apps_link_new_tab_$i"]);

        // Nur speichern, wenn URL und Label vorhanden sind und die URL valide ist
        if (!empty($url) && !empty($label) && filter_var($url, FILTER_VALIDATE_URL)) {
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
    $position = DI::pConfig()->get($userId, 'apps', 'position', 'right');

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

    $b .= $html . apps_styles($position) . apps_scripts();
}

function apps_get_favicon(string $domain): string
{
    $duckDuckGoUrl = sprintf('https://icons.duckduckgo.com/ip3/%s.ico', $domain);
    $faviconKitUrl = sprintf('https://api.faviconkit.com/%s/32', $domain);
    $bestIconUrl   = sprintf('https://besticon-demo.herokuapp.com/icon?url=%s&size=32', $domain);

    if (method_exists(DI::class, 'proxy')) {
        $proxiedDDG        = DI::proxy()->url($duckDuckGoUrl);
        $proxiedFaviconKit = DI::proxy()->url($faviconKitUrl);
        $proxiedBestIcon   = DI::proxy()->url($bestIconUrl);
    } else {
        $proxiedDDG        = $duckDuckGoUrl;
        $proxiedFaviconKit = $faviconKitUrl;
        $proxiedBestIcon   = $bestIconUrl;
    }

    return sprintf(
        '%s" onerror="this.onerror=null;this.src=\'%s\';this.onerror=function(){this.src=\'%s\';}"',
        $proxiedDDG,
        $proxiedFaviconKit,
        $proxiedBestIcon
    );
}

function apps_styles(string $position): string
{
    $side = ($position === 'left') ? 'left: 0;' : 'right: 0;';
    $shadow = ($position === 'left') ? '2px 0 5px rgba(0,0,0,0.2)' : '-2px 0 5px rgba(0,0,0,0.2)';
    $radius = ($position === 'left') ? 'border-radius: 0 10px 10px 0;' : 'border-radius: 10px 0 0 10px;';
    $border = ($position === 'left') ? 'border-right: 1px solid rgba(0,0,0,0.1);' : 'border-left: 1px solid rgba(0,0,0,0.1);';

    return <<<CSS
<style>
    #icon_wrapper {
        position: fixed;
        top: 50%;
        $side
        transform: translateY(-50%);
        width: 50px;
        background-color: rgba(221, 221, 221, 0.2);
        padding: 10px 0;
        box-shadow: $shadow;
        $border
        $radius
        z-index: 9999;
    }
    #icon_wrapper a { display: block; margin-bottom: 10px; text-align: center; }
    #icon_wrapper img { width: 30px; height: 30px; border-radius: 50%; }
    @media (max-width: 768px) { #icon_wrapper { display: none; } }
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
