<?php
/*
 * Name: proxigram
 * Description: Replaces links to instagram to an proxigram instance in all displays of postings on a node.
 * Version: 0.1
 * Author: Matthias Ebers <@feb@loma.ml>
 *
 */

use Friendica\App;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function proxigram_install()
{
    Hook::register('prepare_body_final', 'addon/proxigram/proxigram.php', 'proxigram_render');
}

/* Handle the send data from the admin settings
 */
function proxigram_addon_admin_post()
{
    DI::config()->set('proxigram', 'server', rtrim(trim($_POST['proxigramserver']), '/'));
}

/* Hook into the admin settings to let the admin choose an
 * proxigram server to use for the replacement.
 */
function proxigram_addon_admin(string &$o)
{
    $proxigramserver = DI::config()->get('proxigram', 'server');
    $t = Renderer::getMarkupTemplate('admin.tpl', 'addon/proxigram/');
    $o = Renderer::replaceMacros($t, [
        '$settingdescription' => DI::l10n()->t('Which proxigram server shall be used for the replacements in the post bodies? Use the URL with servername and protocol. See %s for a list of available public proxigram servers.', 'https://codeberg.org/ThePenguinDev/proxigram/wiki/Instances#user-content-public'),
        '$proxigramserver' => ['proxigramserver', DI::l10n()->t('proxigram server'), $proxigramserver, 'https://example.com'],
        '$submit' => DI::l10n()->t('Save Settings'),
    ]);
}

/*
 *  replace "instagram" with the chosen proxigram instance
 */
function proxigram_render(array &$b)
{
    // this needs to be a system setting
    $replaced = false;
    $proxigram = DI::config()->get('proxigram', 'server', 'https://proxigram.lunar.icu');

    $instagramUrls = [
        'https://www.instagram.com',
        'https://ig.me',
    ];

    foreach ($instagramUrls as $instagramUrl) {
        if (strstr($b['html'], $instagramUrl)) {
            $b['html'] = str_replace($instagramUrl, $proxigram, $b['html']);
            $replaced = true;
        }
    }

    if ($replaced) {
        $b['html'] .= '<hr><p><small>' . DI::l10n()->t('(proxigram addon enabled: Instagram links via %s)', $proxigram) . '</small></p>';
    }
}
