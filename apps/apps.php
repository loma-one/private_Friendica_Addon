<?php
/**
 * Name: Apps
 * Description: Show icon links to various apps and services on the right side of the page.
 * Version: 0.4
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function apps_install()
{
    // Registriere den Hook 'page_end' für das Addon und für die Einstellungen
    Hook::register('page_end', __FILE__, 'apps_active');
    Hook::register('addon_settings', __FILE__, 'apps_settings');
    Hook::register('addon_settings_post', __FILE__, 'apps_settings_post');
}

function apps_uninstall()
{
    // Entferne den Hook bei Deinstallation
    Hook::unregister('page_end', __FILE__, 'apps_active');
}

function apps_settings_post()
{
    // Überprüfen, ob ein Benutzer eingeloggt ist und das Formular abgeschickt wurde
    if (!DI::userSession()->getLocalUserId() || empty($_POST['apps-submit'])) {
        return;
    }

    // Speichere die Benutzereinstellung (1 = aktiviert, 0 = deaktiviert)
    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'apps', 'use', intval($_POST['apps_use']));
}

function apps_settings(array &$data)
{
    // Nur für angemeldete Benutzer anzeigen
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    // Hole die aktuelle Einstellung des Benutzers
    $use = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'apps', 'use', false);

    // Vorlage für das Einstellungsformular laden
    $tpl = Renderer::getMarkupTemplate('settings.tpl', 'addon/apps');
    $html = Renderer::replaceMacros($tpl, [
        '$description' => DI::l10n()->t('Show or hide the sidebar with links to apps and services.'),
        '$apps_use'    => ['apps_use', DI::l10n()->t('Show the apps sidebar'), $use, ''],
    ]);

    $data = [
        'addon' => 'apps',
        'title' => DI::l10n()->t('Apps Sidebar Settings'),
        'html'  => $html,
    ];
}

function apps_active(string &$b)
{
    // Überprüfen, ob der Benutzer eingeloggt ist und die Sidebar aktiviert hat
    if (!DI::userSession()->getLocalUserId() || !DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'apps', 'use', false)) {
        return; // Wenn kein Benutzer eingeloggt ist oder die Sidebar deaktiviert ist, nichts tun
    }

    // HTML für die Icons-Leiste
    $b .= '<div id="icon_wrapper" style="position: fixed; top: 50%; right: 0; transform: translateY(-50%); width: 50px; background-color: var(--background-color, rgba(255, 255, 255, 0)); box-shadow: -2px 0 5px rgba(0,0,0,0.5); padding: 10px 0;">
                <a href="https://friendica-hilfe.gitbook.io/friendica-hilfe" title="Friendica Hilfe" class="open-window">
                    <img src="addon/apps/icon/help.jpg" alt="Hilfe" style="width:30px; height:30px;" />
                </a>
                <a href="https://audon.space" title="Open Audon" class="open-window">
                    <img src="addon/apps/icon/audon_icon.png" alt="Audon" style="width:30px; height:30px; margin-bottom: 10px;" />
                </a>
                <a href="https://web.libera.chat" title="Open Chat" class="open-window">
                    <img src="addon/apps/icon/libera_icon.png" alt="Libera.Chat" style="width:30px; height:30px; margin-bottom: 10px;" />
                </a>
                <a href="https://app.cinny.in" title="Matrix Client" class="open-window">
                    <img src="addon/apps/icon/cinny_icon.png" alt="Cinny" style="width:30px; height:30px; margin-bottom: 10px;" />
                </a>
                <a href="https://www.jamendo.com/start" title="Open Music" class="open-window">
                    <img src="addon/apps/icon/jamendo_icon.png" alt="Jamendo" style="width:30px; height:30px; margin-bottom: 10px;" />
                </a>
                <a href="https://podcastaddict.com" title="Open Podcast Index" class="open-window">
                    <img src="addon/apps/icon/podcast_icon.png" alt="Podcast Index" style="width:30px; height:30px; margin-bottom: 10px;" />
                </a>
                <a href="https://conversejs.org/fullscreen.html" title="Open XMPP" class="open-window">
                    <img src="addon/apps/icon/conversejs_icon.png" alt="ConverseJS" style="width:30px; height:30px;" />
                </a>
                <a href="https://www.deepl.com/de/translator" title="Open Deepl" class="open-window">
                    <img src="addon/apps/icon/deepl_icon.png" alt="Deepl" style="width:30px; height:30px;" />
                </a>
            </div>';

    // CSS für das Layout der Icons-Leiste und das Ausblenden auf mobilen Geräten
    $b .= '<style>
                #icon_wrapper {
                    background-color: var(--background-color, rgba(255, 255, 255, 0));
                    border-left: 1px solid var(--border-color, #ccc);
                    box-shadow: -2px 0 5px rgba(0,0,0,0.2);
                    padding: 10px 0;
                    text-align: center;
                }

                #icon_wrapper a {
                    display: block;
                    margin-bottom: 10px;
                }

                #icon_wrapper img {
                    width: 30px;
                    height: 30px;
                }

                #icon_wrapper a:last-child {
                    margin-bottom: 0;
                }

                /* Sidebar auf mobilen Geräten ausblenden */
                @media (max-width: 768px) {
                    #icon_wrapper {
                        display: none;
                    }
                }
            </style>';

    $b .= '<script>
                document.querySelectorAll(".open-window").forEach(function(link) {
                    link.addEventListener("click", function(event) {
                        event.preventDefault();
                        var width = 800;
                        var height = 600;
                        var left = (screen.width - width) / 2;
                        var top = (screen.height - height) / 2;
                        window.open(this.href, "newwindow", "width=" + width + ", height=" + height + ", top=" + top + ", left=" + left);
                    });
                });
            </script>';
}
?>
