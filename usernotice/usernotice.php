<?php
/**
 * Name: User Notice
 * Description: Displays a one-time notification in a blue modal for logged-in users. Use the addon to inform your users about important things on your node.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function usernotice_install() {
    // Registriere das Hook, um das Modal in den Seiteninhalt einzufügen
    Hook::register('page_content_top', __FILE__, 'usernotice_fetch');
}

function usernotice_addon_admin(string &$s)
{
    // Überprüfe, ob der Benutzer ein Administrator ist
    if (!DI::userSession()->isSiteAdmin()) {
        return;
    }

    // Lade das Stylesheet für die Admin-Seite
    DI::page()->registerStylesheet(__DIR__ . '/css/usernotice.css');

    // Hole den aktuellen Text aus der Konfiguration
    $noticeText = DI::config()->get('usernotice', 'text', '');

    // Lade das Admin-Template
    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/usernotice');
    if (!$t) {
        throw new \Exception('Template admin.tpl could not be loaded.');
    }

    // Admin-Seite rendern
    $s .= Renderer::replaceMacros($t, [
        '$title' => DI::l10n()->t('"usernotice" Settings'),
        '$noticeText' => ['usernotice-text', DI::l10n()->t('Message'), $noticeText, DI::l10n()->t('Notification message to display in a modal')],
        '$submit' => DI::l10n()->t('Save Settings')
    ]);
}

function usernotice_addon_admin_post()
{
    // Nur Administratoren dürfen die Einstellungen ändern
    if (!DI::userSession()->isSiteAdmin()) {
        return;
    }

    // Speichere den neuen Text, wenn das Formular abgeschickt wurde
    if (!empty($_POST['usernotice-submit']) && isset($_POST['usernotice-text'])) {
        DI::config()->set('usernotice', 'text', trim(strip_tags($_POST['usernotice-text'])));
    }
}

function usernotice_fetch(string &$b)
{
    // Überprüfe, ob der Benutzer eingeloggt ist
    if (!DI::userSession()->getLocalUserId()) {
        return; // Modal wird nur für angemeldete Benutzer angezeigt
    }

    // Hole den Benachrichtigungstext aus der Konfiguration
    $noticeText = DI::config()->get('usernotice', 'text');

    // Wenn ein Text vorhanden ist, zeige das Modal an
    if ($noticeText) {
        // Lade das Stylesheet
        DI::page()->registerStylesheet(__DIR__ . '/css/usernotice.css');

        // HTML und JavaScript für das Modal hinzufügen
        $b .= '
        <div id="usernotice-modal" class="usernotice-modal">
            <div class="usernotice-modal-content">
                <span class="usernotice-close">&times;</span>
                <h2>' . DI::l10n()->t('User-Notice') . '</h2>
                <p>' . htmlspecialchars($noticeText) . '</p>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var modal = document.getElementById("usernotice-modal");
                var closeBtn = document.getElementsByClassName("usernotice-close")[0];
                var currentText = "' . addslashes($noticeText) . '";

                // Zeige das Modal an, wenn es noch nicht geschlossen wurde oder der Text sich geändert hat
                if (!localStorage.getItem("usernoticeClosed") || localStorage.getItem("usernoticeText") !== currentText) {
                    modal.style.display = "block";
                }

                // Schließe das Modal und speichere den Zustand in localStorage
                closeBtn.onclick = function() {
                    modal.style.display = "none";
                    localStorage.setItem("usernoticeClosed", "true");
                    localStorage.setItem("usernoticeText", currentText);
                }
            });
        </script>';
    }
}
