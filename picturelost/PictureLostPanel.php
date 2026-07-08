<?php

namespace Friendica\Addon\picturelost;

use Friendica\DI;
use Friendica\Core\Renderer;
use Friendica\Content\Pager;
use Friendica\Database\DBA;

class PictureLostPanel
{
    public function __construct()
    {
    }

    public function getLostContent(): string
    {
        // Absolut sichere Konvertierung in eine reine Zahl
        $local_user_id = intval(DI::userSession()->getLocalUserId());

        $is_enabled = DI::pConfig()->get($local_user_id, 'picturelost', 'enabled', 0);
        if (!$is_enabled) {
            return '<div class="generic-page-wrapper"><div class="panel-body">' .
                   DI::l10n()->t('Dieses Addon ist in deinen Einstellungen nicht aktiviert.') .
                   '</div></div>';
        }

        // Nickname direkt aus der Datenbank holen
        $nickname = '';
        $user_sql = "SELECT nickname FROM user WHERE uid = " . $local_user_id . " LIMIT 1";
        $user_ste = DBA::p($user_sql);
        if ($user_ste) {
            $user_row = $user_ste->fetch();
            $nickname = $user_row['nickname'] ?? '';
        }

        // KORREKTUR: Die SQL-Query liefert AUSSCHLIESSLICH verwaiste Bilder.
        // Ein Bild gilt als verwaist, wenn seine resource-id weder normal noch mit einer
        // führenden '0' in der Tabelle post-content existiert.
        $sql = "SELECT
                    p.id,
                    p.`resource-id` AS resource_id,
                    p.filename,
                    p.album,
                    p.created
                FROM photo p
                WHERE p.uid = " . $local_user_id . "
                  AND p.scale = 0
                  AND p.profile = 0
                  AND p.`photo-type` = 0
                  AND NOT EXISTS (
                      SELECT 1 FROM `post-content` pc1 WHERE pc1.`resource-id` = p.`resource-id`
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM `post-content` pc2 WHERE pc2.`resource-id` = CONCAT('0', p.`resource-id`)
                  )
                ORDER BY p.created DESC";

        $ste = DBA::p($sql);
        $lost_photos = $ste ? $ste->fetchAll() : [];

        // Paging aufsetzen (50 Elemente pro Seite)
        $pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 50);
        $paginated_photos = array_slice($lost_photos, $pager->getStart(), 50);

        return Renderer::replaceMacros(Renderer::getMarkupTemplate('picturelost.tpl', 'addon/picturelost'), [
            '$title'       => 'PictureLost - Verwaiste Bilder',
            '$base_url'    => DI::baseUrl(),
            '$nickname'    => $nickname,
            '$count'       => count($lost_photos),
            '$photos'      => $paginated_photos,
            '$pager'       => $pager->renderFull(count($lost_photos)),
        ]);
    }
}
