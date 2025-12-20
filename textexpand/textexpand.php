<?php
/**
 * Name: TextExpand
 * Description: Automatically collapses long posts and allows expanding/collapsing.
 * Version: 1.2
 * Author: Michael Vogel <ike@piratenpartei.de>
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Util\Strings;

/**
 * Register the addon hooks
 */
function textexpand_install()
{
    Hook::register('prepare_body', 'addon/textexpand/textexpand.php', 'textexpand_prepare_body');
    Hook::register('addon_settings', 'addon/textexpand/textexpand.php', 'textexpand_addon_settings');
    Hook::register('addon_settings_post', 'addon/textexpand/textexpand.php', 'textexpand_addon_settings_post');
}

/**
 * Render the addon settings page
 */
function textexpand_addon_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    DI::page()->registerStylesheet(__DIR__ . '/textexpand.css', 'all');

    $enabled = !DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'textexpand', 'disable');
    $chars   = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'textexpand', 'chars', 1100);

    $t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/textexpand/');
    $html = Renderer::replaceMacros($t, [
        '$enabled' => ['textexpand-enable', DI::l10n()->t('Enable TextExpand'), $enabled],
        '$chars'   => ['textexpand-chars', DI::l10n()->t('Cutting posts after how many characters'), $chars],
    ]);

    $data = [
        'addon' => 'textexpand',
        'title' => DI::l10n()->t('TextExpand Settings'),
        'html'  => $html,
    ];
}

/**
 * Save addon settings
 */
function textexpand_addon_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId() || empty($_POST['textexpand-submit'])) {
        return;
    }

    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'textexpand', 'chars', (int)trim($_POST['textexpand-chars']));
    $enable = (!empty($_POST['textexpand-enable']) ? 1 : 0);
    DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'textexpand', 'disable', 1 - $enable);
}

/**
 * Calculate visible character length using DOMDocument
 */
function textexpand_get_body_length($body)
{
    if (empty(trim($body))) {
        return 0;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $body . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($doc);
    $xr = $xpath->query('//*[contains(@style, "display") and contains(@style, "none")]');
    foreach ($xr as $node) {
        $node->parentNode->removeChild($node);
    }

    $visibleText = $doc->getElementsByTagName('body')->item(0)->textContent;
    return mb_strlen(trim($visibleText), 'UTF-8');
}

/**
 * Process the post body to add expand/collapse functionality
 */
function textexpand_prepare_body(&$hook_data)
{
    if (empty($hook_data['html']) || !empty($hook_data['filter_reasons'])) {
        return;
    }

    $userId = DI::userSession()->getLocalUserId();
    $limit = (int) DI::pConfig()->get($userId, 'textexpand', 'chars', 1100);

    if (DI::pConfig()->get($userId, 'textexpand', 'disable')) {
        return;
    }

    if (textexpand_get_body_length($hook_data['html']) > $limit) {
        $rnd = Strings::getRandomHex(8);
        $shortened = trim(textexpand_cutitem($hook_data['html'], $limit)) . "...";

        // Teaser block with "show more"
        $teaser = '<div id="textexpand-teaser-' . $rnd . '" class="textexpand-teaser" dir="auto">' .
            $shortened .
            '<div class="textexpand-wrap fakelink" onclick="openClose(\'textexpand-' . $rnd . '\'); openClose(\'textexpand-teaser-' . $rnd . '\');">' .
            DI::l10n()->t('show more') .
            '</div></div>';

        // Full content block with "show less" at the bottom
        $full = '<div id="textexpand-' . $rnd . '" class="textexpand-content" style="display: none;" dir="auto">' .
            $hook_data['html'] .
            '<div class="textexpand-wrap textexpand-less fakelink" onclick="openClose(\'textexpand-' . $rnd . '\'); openClose(\'textexpand-teaser-' . $rnd . '\');">' .
            DI::l10n()->t('show less') .
            '</div></div>';

        $hook_data['html'] = $teaser . $full;
    }
}

/**
 * Safely cut HTML and repair tags
 */
function textexpand_cutitem($text, $limit)
{
    $shortened = mb_substr($text, 0, $limit, 'UTF-8');

    $lastOpen = mb_strrpos($shortened, '<', 0, 'UTF-8');
    $lastClose = mb_strrpos($shortened, '>', 0, 'UTF-8');
    if ($lastOpen !== false && ($lastClose === false || $lastOpen > $lastClose)) {
        $shortened = mb_substr($shortened, 0, $lastOpen, 'UTF-8');
    }

    $lastAmp = mb_strrpos($shortened, '&', 0, 'UTF-8');
    $lastSemi = mb_strrpos($shortened, ';', 0, 'UTF-8');
    if ($lastAmp !== false && ($lastSemi === false || $lastAmp > $lastSemi)) {
        $shortened = mb_substr($shortened, 0, $lastAmp, 'UTF-8');
    }

    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8"><div>' . $shortened . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $container = $doc->getElementsByTagName('div')->item(0);
    $finalHtml = "";
    if ($container) {
        foreach ($container->childNodes as $child) {
            $finalHtml .= $doc->saveHTML($child);
        }
    }

    return $finalHtml ?: $shortened;
}
