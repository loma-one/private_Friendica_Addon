<?php

/**
 * Name: QuickPhoto
 * Description: Easily edit an image description by replacing the BBCode
 * Version: 1.8.1
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\DI;

function quickphoto_install()
{
    Hook::register('page_header', 'addon/quickphoto/quickphoto.php', 'quickphoto_header');
    Hook::register('post_post', 'addon/quickphoto/quickphoto.php', 'quickphoto_post_hook');
}

function quickphoto_header(&$header)
{
    $desc_label = DI::l10n()->t('Image description');
    $js_label = json_encode($desc_label);

    $addon_path = '/addon/quickphoto/';
    $local_path = 'addon/quickphoto/';

    $header .= "\n" . '<script type="text/javascript">var qp_i18n = { imageDesc: ' . $js_label . ' };</script>';

    if (file_exists($local_path . 'quickphoto.js')) {
        $v_js = filemtime($local_path . 'quickphoto.js');
        $header .= "\n" . '<script type="text/javascript" src="' . $addon_path . 'quickphoto.js?v=' . $v_js . '"></script>' . "\n";
    }
}

function quickphoto_post_hook(&$item)
{
    if (empty($item['body']) || strpos($item['body'], '[img]') === false || strpos($item['body'], '|') === false) {
        return;
    }

    $pattern = '/\\[img\\](.*?)\\|([^\\[]*?)\\[\\/img\\]/i';

    $item['body'] = preg_replace_callback($pattern, function($matches) {
        $imgPath = $matches[1];
        $description = $matches[2];

        if (preg_match('/\/photo\/([a-f0-9]+)/i', $imgPath, $idMatches)) {
            $resourceId = $idMatches[1];

            $condition = [
                'resource-id' => $resourceId,
                'uid'         => DI::userSession()->getLocalUserId(),
                'imgscale'    => 0
            ];

            $photo = DI::dba()->selectFirst('photo', ['url'], $condition);

            if ($photo && !empty($photo['url'])) {
                return '[url=' . $photo['url'] . '][img=' . $imgPath . ']' . $description . '[/img][/url]';
            }
        }

        return '[img]' . $imgPath . '[/img]';
    }, $item['body']);
}
