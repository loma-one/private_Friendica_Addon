<?php
/**
 * Name: Translate
 * Description: Show link to Friendica translate site at bottom of page. Based on Buglink Addon
 * Version: 0.1
 * Author: Mike Macgirvin <mike@macgirvin.com>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\DI;

function translate_install()
{
    Hook::register('page_end', 'addon/translate/translate.php', 'translate_active');
}

function translate_active(string &$b)
{
    $b .= '<div id="buglink_wrapper" style="position: fixed; bottom: 15px; left: 20px;">
                <a href="javascript:void(0);" onclick="openPopup(\'https://www.deepl.com/translator\');" title="' . DI::l10n()->t('Support Translate') . '">
                    <img src="addon/translate/translate.png" alt="' . DI::l10n()->t('Support Translate') . '" />
                </a>
            </div>';

    // JavaScript-Funktion für das Popup
    $b .= '<script>
                function openPopup(url) {
                    window.open(url, "_blank", "width=840, height=850, top=100, left=100, resizable=no, scrollbars=no, toolbar=no, menubar=no, location=no, status=no");
                }
            </script>';
}
?>
