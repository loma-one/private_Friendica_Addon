<?php
/**
 * Name: User Notice
 * Description: Displays a one-time notification in a modal for logged-in users. Use the addon to inform your users about important things on your node.
 * Version: 1.2
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function usernotice_install()
{
    // Modal beim Seiteninhalt einfügen
    Hook::register('page_content_top', __FILE__, 'usernotice_fetch');
}

function usernotice_addon_admin(string &$s)
{
    // Nur für Administratoren sichtbar
    if (!DI::userSession()->isSiteAdmin()) {
        return;
    }

    // Stylesheet für Adminbereich laden
    DI::page()->registerStylesheet(__DIR__ . '/css/style.css');

    // Werte aus der Konfiguration laden
    $noticeText  = DI::config()->get('usernotice', 'text', '');
    $noticeColor = DI::config()->get('usernotice', 'color', 'green');

    // Admin-Template laden
    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/usernotice');
    if (!$t) {
        throw new \Exception('Template admin.tpl could not be loaded.');
    }

    // Adminseite rendern
    $s .= Renderer::replaceMacros($t, [
        '$title'       => DI::l10n()->t('"usernotice" Settings'),
        '$noticeColor' => [
            'usernotice-color',
            DI::l10n()->t('Background color'),
            $noticeColor,
            DI::l10n()->t('Choose the background color for the notice modal.'),
            [
                'green'  => DI::l10n()->t('Green - Information'),
                'yellow' => DI::l10n()->t('Yellow - Notice'),
                'red'    => DI::l10n()->t('Red - Important Information')
            ]
        ],
        '$noticeText' => [
            'usernotice-text',
            DI::l10n()->t('Message'),
            $noticeText,
            DI::l10n()->t('Notification message to display in a modal')
        ],
        '$submit'     => DI::l10n()->t('Save Settings')
    ]);
}

function usernotice_addon_admin_post()
{
    // Nur Administratoren dürfen Änderungen speichern
    if (!DI::userSession()->isSiteAdmin()) {
        return;
    }

    // Einstellungen speichern
    if (!empty($_POST['usernotice-submit'])) {
        if (isset($_POST['usernotice-text'])) {
            // HTML entfernen, Zeilenumbrüche beibehalten
            $noticeText = trim($_POST['usernotice-text']);
            $noticeText = strip_tags($noticeText);
            DI::config()->set('usernotice', 'text', $noticeText);
        }

        if (isset($_POST['usernotice-color'])) {
            $color = $_POST['usernotice-color'];
            if (in_array($color, ['green', 'yellow', 'red'], true)) {
                DI::config()->set('usernotice', 'color', $color);
            }
        }
    }
}

function usernotice_fetch(string &$b)
{
    // Nur für eingeloggte Benutzer
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    // Text und Farbe aus der Config holen
    $noticeText  = DI::config()->get('usernotice', 'text');
    $noticeColor = DI::config()->get('usernotice', 'color', 'green');

    // Modal nur anzeigen, wenn Text vorhanden
    if ($noticeText) {
        // Stylesheet laden
        DI::page()->registerStylesheet(__DIR__ . '/css/style.css');

        // Modal-HTML und JS einfügen
        $b .= '
        <div id="usernotice-modal" class="usernotice-modal usernotice-' . htmlspecialchars($noticeColor, ENT_QUOTES, 'UTF-8') . '">
            <div class="usernotice-modal-content">
                <span class="usernotice-close">&times;</span>
                <h2>' . DI::l10n()->t('User-Notice') . '</h2>
                <hr class="usernotice-divider">
                <p>' . nl2br(htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8')) . '</p>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var modal = document.getElementById("usernotice-modal");
                var closeBtn = document.getElementsByClassName("usernotice-close")[0];
                var currentText = ' . json_encode($noticeText) . ';

                // Modal anzeigen, wenn nicht schon geschlossen oder Text geändert
                if (!localStorage.getItem("usernoticeClosed") || localStorage.getItem("usernoticeText") !== currentText) {
                    modal.style.display = "block";
                }

                // Modal schließen
                closeBtn.onclick = function() {
                    modal.style.display = "none";
                    localStorage.setItem("usernoticeClosed", "true");
                    localStorage.setItem("usernoticeText", currentText);
                }
            });
        </script>';
    }
}
