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
    private const GRACE_PERIOD = 'now - 1 day'; // 24 Stunden Frist
    private const ITEMS_PER_PAGE = 50;
    private const CACHE_TTL = 86400; // 24 Stunden Cache

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

        // Beim ersten Aufruf standardmäßig 'lost' erzwingen, damit die Checkbox nicht aktiv ist
        $tab = isset($_GET['tab']) && $_GET['tab'] === 'used' ? 'used' : 'lost';

        // 1. Verwendete Resource-IDs aus Cache oder performanter Index-Suche abrufen
        $usedRids = $this->getUsedResourceIdsFromCacheOrDb($uid);

        // 2. Alle Bild-Kandidaten des Benutzers laden
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

        // Hinweistext
        $hintText = ($tab === 'lost')
            ? DI::l10n()->t('Zeigt verwaiste Bilder (ohne Verwendung in Beiträgen, Terminen, Nachrichten oder Profiltexten). Klick auf das Bild öffnet die Galerie zum Löschen.')
            : DI::l10n()->t('Zeigt verwendete Bilder an. Diese Bilder bitte nicht löschen, da sie in Beiträgen, Events oder Profilinformationen angezeigt werden');

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

        $usedRids = $this->collectUsedResourceIdsFast($uid);
        DI::cache()->set($cacheKey, $usedRids, self::CACHE_TTL);

        return $usedRids;
    }

    /**
     * Holt alle Hauptbilder des Benutzers (scale = 0, profil = 0, Alter > 24h, keine Systemalben).
     */
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

    /**
     * Hochoptimierte Abfrage genutzter Resource-IDs über relationale Datenbank-Indizes.
     */
    private function collectUsedResourceIdsFast(int $uid): array
    {
        $rids = [];

        // 1. Indizierte Suche über post-content (Friendica verknüpft Medien hier strukturiert)
        $sqlPostContent = "SELECT DISTINCT `pc`.`resource-id`
                           FROM `post-content` `pc`
                           INNER JOIN `post-user` `pu` ON `pu`.`uri-id` = `pc`.`uri-id`
                           WHERE `pu`.`uid` = ?";
        $stmt = DBA::p($sqlPostContent, $uid);
        if ($stmt) {
            while ($row = DBA::fetch($stmt)) {
                $cleanRid = ltrim($row['resource-id'], '0');
                if ($cleanRid !== '') {
                    $rids[$cleanRid] = true;
                }
            }
            DBA::close($stmt);
        }

        // 2. Indizierte Suche über post-media
        $sqlPostMedia = "SELECT DISTINCT `pm`.`url`, `pm`.`preview`
                         FROM `post-media` `pm`
                         INNER JOIN `post-user` `pu` ON `pu`.`uri-id` = `pm`.`uri-id`
                         WHERE `pu`.`uid` = ?";
        $stmt = DBA::p($sqlPostMedia, $uid);
        if ($stmt) {
            while ($row = DBA::fetch($stmt)) {
                foreach ([$row['url'], $row['preview']] as $url) {
                    if (is_string($url) && $url !== '') {
                        foreach ($this->extractRids($url) as $rid) {
                            $rids[$rid] = true;
                        }
                    }
                }
            }
            DBA::close($stmt);
        }

        // 3. Suche in Mails
        $sqlMail = "SELECT `body` FROM `mail` WHERE `uid` = ? AND `body` LIKE '%/photo%'";
        $stmt = DBA::p($sqlMail, $uid);
        if ($stmt) {
            while ($row = DBA::fetch($stmt)) {
                foreach ($this->extractRids($row['body']) as $rid) {
                    $rids[$rid] = true;
                }
            }
            DBA::close($stmt);
        }

        // 4. Suche in Events
        $sqlEvent = "SELECT `summary`, `desc` FROM `event` WHERE `uid` = ? AND (`summary` LIKE '%/photo%' OR `desc` LIKE '%/photo%')";
        $stmt = DBA::p($sqlEvent, $uid);
        if ($stmt) {
            while ($row = DBA::fetch($stmt)) {
                foreach ([$row['summary'], $row['desc']] as $text) {
                    foreach ($this->extractRids($text) as $rid) {
                        $rids[$rid] = true;
                    }
                }
            }
            DBA::close($stmt);
        }

        // 5. Suche im Profiltext
        $sqlProfile = "SELECT `about` FROM `profile` WHERE `uid` = ? AND `about` LIKE '%/photo%'";
        $stmt = DBA::p($sqlProfile, $uid);
        if ($stmt) {
            while ($row = DBA::fetch($stmt)) {
                foreach ($this->extractRids($row['about']) as $rid) {
                    $rids[$rid] = true;
                }
            }
            DBA::close($stmt);
        }

        return $rids;
    }

    /**
     * Ermittelt für die aktuell angezeigten genutzten Bilder die URL des passenden Beitrags.
     */
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
