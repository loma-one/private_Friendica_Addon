<?php
/**
 * Name: User Notice
 * Description: Displays a one-time notification in a modal for logged-in users. Supports BBCode for links.
 * Version: 1.5
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Content\Text\BBCode;

function usernotice_install()
{
    Hook::register('page_content_top', __FILE__, 'usernotice_fetch');
    Hook::register('head', __FILE__, 'usernotice_head');
    Hook::register('footer', __FILE__, 'usernotice_footer');
}

function usernotice_head(string &$b)
{
    DI::page()->registerStylesheet(__DIR__ . '/css/style.css');
}

function usernotice_footer(string &$b)
{
    if (DI::userSession()->getLocalUserId() && DI::config()->get('usernotice', 'text')) {
        DI::page()->registerFooterScript(__DIR__ . '/js/usernotice.js');
    }
}

function usernotice_addon_admin(string &$s)
{
    if (!DI::userSession()->isSiteAdmin()) {
        return;
    }

    $noticeText  = DI::config()->get('usernotice', 'text', '');
    $noticeColor = DI::config()->get('usernotice', 'color', 'green');

    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/usernotice');
    if (!$t) {
        throw new \Exception('Template admin.tpl could not be loaded.');
    }

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
            DI::l10n()->t('Notification message (BBCode supported, e.g. [url=https://example.com]Link[/url])')
        ],
        '$submit'     => DI::l10n()->t('Save Settings')
    ]);
}

function usernotice_addon_admin_post()
{
    if (!DI::userSession()->isSiteAdmin()) {
        return;
    }

    if (!empty($_POST['usernotice-submit'])) {
        if (isset($_POST['usernotice-text'])) {
            $noticeText = trim($_POST['usernotice-text']);
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
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $noticeText  = DI::config()->get('usernotice', 'text');
    $noticeColor = DI::config()->get('usernotice', 'color', 'green');

    if ($noticeText) {
        $renderedText = BBCode::convert($noticeText);

        $b .= '
        <div id="usernotice-modal" class="usernotice-modal usernotice-' . htmlspecialchars($noticeColor, ENT_QUOTES, 'UTF-8') . '" data-notice-text="' . htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8') . '" style="display: none;">
            <div class="usernotice-modal-content">
                <h2>' . DI::l10n()->t('User-Notice') . '</h2>
                <hr class="usernotice-divider">
                <div class="usernotice-body">' . $renderedText . '</div>
                <hr class="usernotice-divider">
                <div class="usernotice-button-container">
                    <button id="usernotice-ok-btn" class="usernotice-button">OK</button>
                </div>
            </div>
        </div>';
    }
}
