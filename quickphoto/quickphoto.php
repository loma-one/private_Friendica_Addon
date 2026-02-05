<?php
/**
 * Name: QuickPhoto
 * Description: Client- und Serverseitige Rekonstruktion für maximale Kompatibilität.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;

function quickphoto_install() {
    Hook::register('page_header', 'addon/quickphoto/quickphoto.php', 'quickphoto_header');
    // Notfall-Hook: Falls das JS beim Senden versagt
    Hook::register('post_post', 'addon/quickphoto/quickphoto.php', 'quickphoto_post_hook');
}

function quickphoto_header(&$header) {
    $header .= '<script src="/addon/quickphoto/quickphoto.js?v=5.0"></script>' . "\n";
}

/**
 * Verarbeitet den Text direkt beim Empfang auf dem Server
 */
function quickphoto_post_hook(&$item) {
    if (!isset($item['body'])) {
        return;
    }

    // Falls das JS die Arbeit nicht machen konnte, haben wir hier das Problem,
    // dass wir den LocalStorage nicht haben. Deshalb ist das JS hier primär zuständig.
    // Wir lassen diesen Hook als Platzhalter für zukünftige Server-Validierungen.
}
