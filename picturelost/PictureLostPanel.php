<?php

namespace Friendica\Addon\picturelost;

use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Util\DateTimeFormat;

class PictureLostPanel
{
    private const GRACE_PERIOD = 'now - 1 day'; // 24h Frist
    private const ITEMS_PER_PAGE = 50;
    private const CACHE_TTL = 86400; // 24h Cache

    public function getLostContent(): string
    {
        $uid = DI::userSession()->getLocalUserId();

        if (!$uid) {
            return '';
        }

        $uid = (int) $uid;

        $isEnabled = DI::pConfig()->get($uid, 'picturelost', 'enabled', 0);
        if (!$isEnabled) {
            return '<div class="generic-page-wrapper"><div class="panel-body">'
                   . DI::l10n()->t('Dieses Addon ist in deinen Einstellungen nicht aktiviert.')
                   . '</div></div>';
        }

        $user = DBA::selectFirst('user', ['nickname'], ['uid' => $uid]);
        $nickname = is_array($user) ? ($user['nickname'] ?? '') : '';

        // Beim initialen Aufruf immer 'lost' setzen, damit die Checkbox nicht aktiv ist
        $tab = isset($_GET['tab']) && $_GET['tab'] === 'used' ? 'used' : 'lost';

        // 1. Verwendete Resource-IDs laden (Cache/DB)
        $usedRids = $this->getUsedResourceIdsFromCacheOrDb($uid);

        // 2. Alle Bild-Kandidaten abrufen
        $allCandidates = $this->fetchAllCandidates($uid);

        $lostPhotos = [];
        $usedPhotos = [];

        foreach ($allCandidates as $photo) {
            $rid = $photo['resource_id'];
            if (!isset($usedRids[$rid])) {
                $lostPhotos[] = $photo;
            } else {
                $usedPhotos[] = $photo;
            }
        }

        $activeList = ($tab === 'lost') ? $lostPhotos : $usedPhotos;
        $totalCount = count($activeList);

        // 3. Pagination anwenden
        $pager = new Pager(DI::l10n(), DI::args()->getQueryString(), self::ITEMS_PER_PAGE);
        $pagedPhotos = array_slice($activeList, $pager->getStart(), $pager->getItemsPerPage());

        // 4. Nur im "used"-Modus Beitrags-URLs für die aktuell sichtbaren 50 Bilder ermitteln
        if ($tab === 'used' && !empty($pagedPhotos)) {
            $pagedPhotos = $this->attachPostUrls($uid, $pagedPhotos);
        }

        $hintText = ($tab === 'lost')
            ? DI::l10n()->t('Zeigt verwaiste Bilder (ohne Verwendung in Beiträgen, DM, Terminen oder Profiltexten). Klick auf das Bild öffnet die Galerie zum Löschen.')
            : DI::l10n()->t('Zeigt verwendete Bilder an. Diese Bilder bitte nicht löschen, da sie in Beiträgen, DM, Terminen oder Profiltexten angezeigt werden');

        return Renderer::replaceMacros(Renderer::getMarkupTemplate('picturelost.tpl', 'addon/picturelost'), [
            '$title'      => DI::l10n()->t('PictureLost - Verwaiste Bilder'),
            '$hint'       => $hintText,
            '$base_url'   => DI::baseUrl(),
            '$nickname'   => $nickname,
            '$tab'        => $tab,
            '$count'      => $totalCount,
            '$photos'     => $pagedPhotos,
            '$pager'      => $pager->renderFull($totalCount),
        ]);
    }

    private function getUsedResourceIdsFromCacheOrDb(int $uid): array
    {
        $cacheKey = 'picturelost_used_rids_' . $uid;

        $cachedRids = DI::cache()->get($cacheKey);
        if ($cachedRids !== null && is_array($cachedRids)) {
            return $cachedRids;
        }

        $usedRids = $this->collectUsedResourceIds($uid);
        DI::cache()->set($cacheKey, $usedRids, self::CACHE_TTL);

        return $usedRids;
    }

    private function fetchAllCandidates(int $uid): array
    {
        $albums       = $this->getSystemAlbums();
        $placeholders = implode(', ', array_fill(0, count($albums), '?'));

        $sql = "SELECT
                    `p`.`id`,
                    `p`.`resource-id` AS `resource_id`,
                    `p`.`filename`,
                    `p`.`album`,
                    `p`.`created`
                FROM `photo` `p`
                WHERE `p`.`uid` = ?
                  AND `p`.`scale` = 0
                  AND `p`.`profile` = 0
                  AND `p`.`photo-type` = ?
                  AND `p`.`created` < ?
                  AND `p`.`album` NOT IN (" . $placeholders . ")
                ORDER BY `p`.`created` DESC";

        $params = array_merge([$uid, Photo::DEFAULT, DateTimeFormat::utc(self::GRACE_PERIOD)], $albums);

        $stmt = DBA::p($sql, ...$params);
        if (!$stmt) {
            return [];
        }

        return DBA::toArray($stmt);
    }

    private function attachPostUrls(int $uid, array $photos): array
    {
        $rids = array_column($photos, 'resource_id');
        if (empty($rids)) {
            return $photos;
        }

        $placeholders = implode(',', array_fill(0, count($rids), '?'));

        $sql = "SELECT `pc`.`resource-id` AS `rid`, `item`.`plink`
                FROM `post-content` `pc`
                INNER JOIN `item` ON `item`.`uri-id` = `pc`.`uri-id`
                WHERE `item`.`uid` = ? AND `pc`.`resource-id` IN (" . $placeholders . ")
                LIMIT 100";

        $params = array_merge([$uid], $rids);
        $stmt = DBA::p($sql, ...$params);

        $urls = [];
        if ($stmt) {
            while ($row = DBA::fetch($stmt)) {
                $cleanRid = ltrim($row['rid'], '0');
                $urls[$cleanRid] = $row['plink'];
            }
            DBA::close($stmt);
        }

        foreach ($photos as &$photo) {
            $photo['post_url'] = $urls[$photo['resource_id']] ?? null;
        }

        return $photos;
    }

    private function collectUsedResourceIds(int $uid): array
    {
        $rids = [];

        // 1. Post Media
        $this->addRidsFromQuery(
            $rids,
            "SELECT `pm`.`url`, `pm`.`preview`
             FROM `post-user` `pu`
             INNER JOIN `post-media` `pm` ON `pm`.`uri-id` = `pu`.`uri-id`
             WHERE `pu`.`uid` = ? AND `pu`.`origin`
               AND (`pm`.`url` LIKE ? OR `pm`.`preview` LIKE ?)",
            [$uid, '%/photo%', '%/photo%']
        );

        // 2. Post Content
        $this->addRidsFromQuery(
            $rids,
            "SELECT `pc`.`body`, `pc`.`raw-body`
             FROM `post-user` `pu`
             INNER JOIN `post-content` `pc` ON `pc`.`uri-id` = `pu`.`uri-id`
             WHERE `pu`.`uid` = ? AND `pu`.`origin`
               AND (`pc`.`body` LIKE ? OR `pc`.`raw-body` LIKE ?)",
            [$uid, '%/photo%', '%/photo%']
        );

        // 3. Nachrichten
        $this->addRidsFromQuery(
            $rids,
            "SELECT `body` FROM `mail` WHERE `uid` = ? AND `body` LIKE ?",
            [$uid, '%/photo%']
        );

        // 4. Events
        $this->addRidsFromQuery(
            $rids,
            "SELECT `summary`, `desc` FROM `event` WHERE `uid` = ? AND (`summary` LIKE ? OR `desc` LIKE ?)",
            [$uid, '%/photo%', '%/photo%']
        );

        // 5. Profiltext
        $this->addRidsFromQuery(
            $rids,
            "SELECT `about` FROM `profile` WHERE `uid` = ? AND `about` LIKE ?",
            [$uid, '%/photo%']
        );

        return $rids;
    }

    private function addRidsFromQuery(array &$rids, string $sql, array $params): void
    {
        $stmt = DBA::p($sql, ...$params);
        if (!$stmt) {
            return;
        }

        while ($row = DBA::fetch($stmt)) {
            foreach ($row as $value) {
                if (is_string($value) && $value !== '') {
                    foreach ($this->extractRids($value) as $rid) {
                        $rids[$rid] = true;
                    }
                }
            }
        }

        DBA::close($stmt);
    }

    private function extractRids(string $text): array
    {
        if (strpos($text, '/photo') === false) {
            return [];
        }

        if (!preg_match_all('#/photos?/(?:[^\s\]"\']+/image/|)([A-Za-z0-9]+)#i', $text, $matches)) {
            return [];
        }

        return $matches[1];
    }

    private function getSystemAlbums(): array
    {
        $albums = [Photo::PROFILE_PHOTOS, Photo::CONTACT_PHOTOS, Photo::BANNER_PHOTOS];

        $translated = [];
        foreach ($albums as $album) {
            $translated[] = DI::l10n()->t($album);
        }

        return array_values(array_unique(array_merge($albums, $translated)));
    }
}
