<?php

/**
 * Name: MediaCompress (PoC)
 * Description: Verkleinert Video- und MP3-Anlagen serverseitig per ffmpeg. Ersetzt das Original nur, wenn das Ergebnis kleiner und valide ist.
 * Version: 0.1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 * License: AGPL-3.0-or-later
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Util\DateTimeFormat;

function mediacompress_install(): void
{
	Hook::register('cron', __FILE__, 'mediacompress_cron');
	Hook::register('addon_admin', __FILE__, 'mediacompress_addon_admin');
	Hook::register('addon_admin_post', __FILE__, 'mediacompress_addon_admin_post');
}

function mediacompress_uninstall(): void
{
	Hook::unregister('cron', __FILE__, 'mediacompress_cron');
	Hook::unregister('addon_admin', __FILE__, 'mediacompress_addon_admin');
	Hook::unregister('addon_admin_post', __FILE__, 'mediacompress_addon_admin_post');
}

function mediacompress_conf(): array
{
	return [
		'enabled'       => (bool) DI::config()->get('mediacompress', 'enabled', false),
		'interval'      => max(1, (int) DI::config()->get('mediacompress', 'interval', 10)),       // Minuten zwischen Läufen
		'maxsize'       => max(0, (int) DI::config()->get('mediacompress', 'maxsize', 200)),      // MB; 0 = unbegrenzt
		'video_height'  => max(144, (int) DI::config()->get('mediacompress', 'video_height', 720)),
		'crf'           => min(40, max(18, (int) DI::config()->get('mediacompress', 'crf', 26))),
		'audio_bitrate' => min(192, max(32, (int) DI::config()->get('mediacompress', 'audio_bitrate', 64))), // kbit/s
		'timeout'       => max(60, (int) DI::config()->get('mediacompress', 'timeout', 600)),      // Sekunden pro ffmpeg-Lauf
		'retention'     => max(0, (int) DI::config()->get('mediacompress', 'retention', 0)),       // Tage Original behalten (0 = sofort löschen)
		'fileperm'      => preg_match('/^0[0-7]{3}$/', (string) DI::config()->get('mediacompress', 'fileperm', '0644')) ? (string) DI::config()->get('mediacompress', 'fileperm') : '0644',
		'ffmpeg_path'   => (string) DI::config()->get('mediacompress', 'ffmpeg_path', 'ffmpeg'),
	];
}

function mediacompress_list(string $key): array
{
	$list = DI::config()->get('mediacompress', $key, []);
	return is_array($list) ? $list : [];
}

function mediacompress_list_add(string $key, int $id): void
{
	$list = mediacompress_list($key);
	$list[$id] = true;
	if (count($list) > 5000) {
		$list = array_slice($list, -5000, preserve_keys: true);
	}
	DI::config()->set('mediacompress', $key, $list);
}

function mediacompress_cron(&$b): void
{
	if (!mediacompress_conf()['enabled']) {
		return;
	}

	$last = (int) DI::config()->get('mediacompress', 'last_run', 0);
	if ((time() - $last) < mediacompress_conf()['interval'] * 60) {
		return;
	}
	DI::config()->set('mediacompress', 'last_run', time());

	mediacompress_handle_pending_deletes();
	mediacompress_process(1);
}

/**
 * Verarbeitet bis zu $max Dateien.
 *
 * @return array Ergebnis des Laufs (converted, skipped, error)
 */
function mediacompress_process(int $max = 1): array
{
	$conf = mediacompress_conf();
	$result = ['converted' => 0, 'skipped' => 0, 'error' => null];

	if (!mediacompress_ffmpeg_available($conf['ffmpeg_path'])) {
		DI::config()->set('mediacompress', 'last_error', 'ffmpeg nicht gefunden (Pfad prüfen).');
		$result['error'] = 'ffmpeg nicht verfügbar';
		return $result;
	}

	if (!DI::lock()->acquire('mediacompress_worker', 0, 600)) {
		$result['error'] = 'Ein anderer Lauf ist noch aktiv.';
		return $result;
	}

	try {
		@set_time_limit(0);

		$done   = mediacompress_list('done');
		$failed = mediacompress_list('failed');

		// Groesste Dateien zuerst
		$rows = Attach::selectToArray(
			[],
			["`filetype` LIKE 'video/%' OR `filetype` = 'audio/mpeg'"],
			['order' => ['filesize' => true], 'limit' => 200]
		);

		foreach ($rows as $row) {
			if ($result['converted'] + $result['skipped'] >= $max) {
				break;
			}
			if (isset($done[$row['id']]) || isset($failed[$row['id']])) {
				continue;
			}
			// Groesser als das Limit - zaehlt nicht als Arbeit, sonst wuerde eine
			// einzige zu grosse Datei (Sortierung: groesste zuerst) den Cron dauerhaft blockieren
			if ($conf['maxsize'] > 0 && (int) $row['filesize'] > $conf['maxsize'] * 1048576) {
				continue;
			}
			if ((int) $row['filesize'] < 256 * 1024) {
				// Kleinere Dateien lohnen nicht
				mediacompress_list_add('done', (int) $row['id']);
				continue;
			}

			try {
				$res = mediacompress_convert($row, $conf);
			} catch (\Throwable $e) {
				DI::logger()->error('mediacompress: unexpected exception during conversion', ['id' => $row['id'], 'exception' => $e]);
				$res = ['error' => 'Unerwarteter Fehler bei Anlage #' . $row['id'] . ' (siehe Log).'];
			}
			if (!empty($res['error'])) {
				mediacompress_list_add('failed', (int) $row['id']);
				DI::config()->set('mediacompress', 'last_error', $res['error']);
				$result['skipped']++;
			} elseif (!empty($res['skipped'])) {
				// Ergebnis nicht kleiner als Original -> dauerhaft als erledigt markieren
				mediacompress_list_add('done', (int) $row['id']);
				$result['skipped']++;
			} else {
				mediacompress_list_add('done', (int) $row['id']);
				$result['converted']++;
				DI::config()->set('mediacompress', 'last_error', null);
			}
		}
	} finally {
		DI::lock()->release('mediacompress_worker');
	}

	return $result;
}

/**
 * Konvertiert eine einzelne Anlage.
 */
function mediacompress_convert(array $row, array $conf): array
{
	$isAudio = str_starts_with((string) $row['filetype'], 'audio/');
	$ext     = $isAudio ? 'mp3' : 'mp4';

	// tempnam() erzeugt die Datei; das Suffix haengen wir per atomarem rename an,
	// damit keine extensionslosen 0-Byte-Waisen in /tmp zurueckbleiben
	$tmpInBase  = tempnam(sys_get_temp_dir(), 'mc_in_');
	$tmpOutBase = tempnam(sys_get_temp_dir(), 'mc_out_');
	$tmpIn  = $tmpInBase . '.' . $ext;
	$tmpOut = $tmpOutBase . '.' . $ext;
	@rename($tmpInBase, $tmpIn);
	@rename($tmpOutBase, $tmpOut);

	try {
		$data = Attach::getData($row);
		if ($data === null || $data === '') {
			return ['error' => sprintf('Anlage #%d konnte nicht aus dem Storage-Backend gelesen werden.', $row['id'])];
		}
		if (file_put_contents($tmpIn, $data) === false) {
			return ['error' => 'Temp-Datei konnte nicht geschrieben werden (Disk voll? /tmp prüfen).'];
		}
		unset($data);

		if ($isAudio) {
			$cmd = sprintf(
				'nice -n 19 timeout %d %s -y -i %s -vn -c:a libmp3lame -b:a %dk %s',
				$conf['timeout'],
				escapeshellarg($conf['ffmpeg_path']),
				escapeshellarg($tmpIn),
				$conf['audio_bitrate'],
				escapeshellarg($tmpOut)
			);
		} else {
			// Nie hochskalieren
			$vf = 'scale=-2:min(' . $conf['video_height'] . '\,ih)';
			$cmd = sprintf(
				'nice -n 19 timeout %d %s -y -i %s -vf %s -c:v libx264 -crf %d -preset veryfast -profile:v main -c:a aac -b:a %dk -movflags +faststart %s',
				$conf['timeout'],
				escapeshellarg($conf['ffmpeg_path']),
				escapeshellarg($tmpIn),
				escapeshellarg($vf),
				$conf['crf'],
				$conf['audio_bitrate'],
				escapeshellarg($tmpOut)
			);
		}

		exec($cmd . ' 2>&1', $output, $ret);

		if ($ret !== 0 || !file_exists($tmpOut) || filesize($tmpOut) === 0) {
			return ['error' => sprintf('ffmpeg für Anlage #%d fehlgeschlagen (Exit-Code %d).', $row['id'], $ret)];
		}

		$newSize = (int) filesize($tmpOut);
		if ($newSize >= (int) $row['filesize']) {
			// Kein Gewinn
			return ['skipped' => 'no-gain'];
		}

		// Zieldatei mit ffprobe pruefen, falls vorhanden
		$probe = $conf['ffmpeg_path'];
		if (str_contains($probe, '/')) {
			$probe = rtrim(dirname($probe), '/') . '/ffprobe';
		} else {
			$probe = 'ffprobe';
		}
		exec(sprintf('command -v %s >/dev/null 2>&1', escapeshellarg($probe)), $ignore, $probeExists);
		if ($probeExists !== 0) {
			DI::logger()->warning('mediacompress: ffprobe not available, skipping output validation');
		} else {
			exec(sprintf('%s -v error -show_entries format=duration -of csv=p=0 %s 2>&1', escapeshellarg($probe), escapeshellarg($tmpOut)), $probeOut, $probeRet);
			if ($probeRet !== 0 || empty($probeOut[0]) || (float) $probeOut[0] <= 0) {
				return ['error' => sprintf('Zieldatei für Anlage #%d ist nicht valide (ffprobe).', $row['id'])];
			}
		}

		$newData = file_get_contents($tmpOut);
		if ($newData === false || strlen($newData) !== $newSize) {
			return ['error' => sprintf('Zieldatei für Anlage #%d konnte nicht gelesen werden.', $row['id'])];
		}

		// Neues Blob schreiben
		$newRef     = DI::storage()->put($newData);
		$newBackend = (string) DI::storage();
		unset($newData);

		// Filesystem-Backend: Der Cron laeuft oft unter einem anderen User als
		// der Webserver. Rechte explizit setzen; scheitert das, wird die Anlage
		// nicht umgestellt (sonst waere die Datei fuer den Webserver unlesbar).
		if (str_contains($newBackend, 'Filesystem')) {
			$basePath = rtrim((string) DI::config()->get('storage', 'filesystem_path', 'storage'), '/');
			$dir1 = $basePath . '/' . substr($newRef, 0, 2);
			$dir2 = $dir1 . '/' . substr($newRef, 2, 2);
			$newPath = $dir2 . '/' . substr($newRef, 4);
			$perm    = (int) octdec($conf['fileperm']);
			$dirPerm = ($perm & 0004) ? 0755 : 0750;
			@chmod($dir1, $dirPerm);
			@chmod($dir2, $dirPerm);
			if (!is_file($newPath) || !@chmod($newPath, $perm) || !is_readable($newPath)) {
				$why = !is_file($newPath) ? 'Datei nicht gefunden' : (!@chmod($newPath, $perm) ? 'chmod fehlgeschlagen' : 'nicht lesbar');
				@unlink($newPath);
				return ['error' => sprintf('Dateirechte für Anlage #%d: %s (%s).', $row['id'], $why, $newPath)];
			}
		}

		// Erst den Datensatz umstellen, dann das alte Blob loeschen
		if (!DBA::update('attach', [
			'filesize'      => $newSize,
			'edited'        => DateTimeFormat::utcNow(),
			'data'          => '',
			'backend-class' => $newBackend,
			'backend-ref'   => $newRef,
		], ['id' => (int) $row['id']])) {
			// Original bleibt unveraendert
			return ['error' => sprintf('DB-Update für Anlage #%d fehlgeschlagen.', $row['id'])];
		}

		// post-media Groesse anpassen
		$attachUrl = DI::baseUrl() . '/attach/' . (int) $row['id'];
		DBA::update('post-media', ['size' => $newSize], [
			"`url` = ? OR `url` LIKE ?",
			$attachUrl,
			$attachUrl . '/%',
		]);

		// Altes Blob loeschen oder zurueckstellen
		if ($conf['retention'] > 0) {
			$pending = mediacompress_list('pending_delete');
			$pending[] = [
				'class' => (string) ($row['backend-class'] ?? ''),
				'ref'   => (string) ($row['backend-ref'] ?? ''),
				'ts'    => time(),
			];
			DI::config()->set('mediacompress', 'pending_delete', $pending);
		} else {
			mediacompress_delete_blob($row);
		}

		// Statistik
		$stats = DI::config()->get('mediacompress', 'stats', []) ?: [];
		$stats['converted']    = (int) ($stats['converted'] ?? 0) + 1;
		$stats['bytes_before'] = (int) ($stats['bytes_before'] ?? 0) + (int) $row['filesize'];
		$stats['bytes_after']  = (int) ($stats['bytes_after'] ?? 0) + $newSize;
		DI::config()->set('mediacompress', 'stats', $stats);

		DI::logger()->info('mediacompress: converted', [
			'id' => $row['id'], 'before' => $row['filesize'], 'after' => $newSize,
		]);

		return ['ok' => true, 'before' => (int) $row['filesize'], 'after' => $newSize];
	} finally {
		@unlink($tmpIn);
		@unlink($tmpOut);
	}
}

/**
 * Löscht zurückgestellte Original-Blobs, deren Frist abgelaufen ist.
 */
function mediacompress_handle_pending_deletes(): void
{
	$retention = mediacompress_conf()['retention'];
	if ($retention <= 0) {
		return;
	}
	$pending = mediacompress_list('pending_delete');
	if (!$pending) {
		return;
	}
	$cutoff = time() - $retention * 86400;
	$keep = [];
	foreach ($pending as $entry) {
		if ((int) ($entry['ts'] ?? 0) <= $cutoff) {
			mediacompress_delete_blob(['backend-class' => $entry['class'] ?? '', 'backend-ref' => $entry['ref'] ?? '']);
		} else {
			$keep[] = $entry;
		}
	}
	DI::config()->set('mediacompress', 'pending_delete', $keep);
}

function mediacompress_delete_blob(array $row): void
{
	if (empty($row['backend-class']) || empty($row['backend-ref'])) {
		// Legacy-Eintrag ohne Backend-Referenz
		return;
	}
	try {
		DI::storageManager()
			->getWritableStorageByName($row['backend-class'])
			->delete($row['backend-ref']);
	} catch (\Throwable $e) {
		DI::logger()->debug('mediacompress: old blob could not be deleted', ['exception' => $e]);
	}
}

function mediacompress_ffmpeg_available(string $path): bool
{
	exec(sprintf('%s -version 2>&1', escapeshellarg($path)), $output, $ret);
	return $ret === 0;
}

function mediacompress_addon_admin(string &$o): void
{
	$conf  = mediacompress_conf();
	$stats = DI::config()->get('mediacompress', 'stats', []) ?: [];
	$done   = mediacompress_list('done');
	$failed = mediacompress_list('failed');

	$savedBytes = max(0, (int) ($stats['bytes_before'] ?? 0) - (int) ($stats['bytes_after'] ?? 0));
	$savedMB    = round($savedBytes / 1048576, 1);

	$statusHtml = '';
	if (!empty($_SESSION['mediacompress_status_success'])) {
		$statusHtml = '<div class="alert alert-success">' . htmlspecialchars($_SESSION['mediacompress_status_success']) . '</div>';
		unset($_SESSION['mediacompress_status_success']);
	} elseif (!empty($_SESSION['mediacompress_status_error'])) {
		$statusHtml = '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['mediacompress_status_error']) . '</div>';
		unset($_SESSION['mediacompress_status_error']);
	} elseif (!empty(DI::config()->get('mediacompress', 'last_error'))) {
		$statusHtml = '<div class="alert alert-warning">Letzter Fehler: ' . htmlspecialchars(DI::config()->get('mediacompress', 'last_error')) . '</div>';
	}

	$ffmpegOk = mediacompress_ffmpeg_available($conf['ffmpeg_path']);
	$ffmpegHtml = $ffmpegOk
		? '<span class="text-success">ffmpeg gefunden</span>'
		: '<span class="text-danger">ffmpeg NICHT gefunden - Pfad unten korrekt setzen!</span>';

	$lastCron = DI::keyValue()->get('last_cron');
	$lastRun  = (int) DI::config()->get('mediacompress', 'last_run', 0);

	$cronHtml = ($lastCron && (time() - (int) $lastCron) < 900)
		? '<span class="text-success">aktiv, letzter Lauf vor ' . (int) floor((time() - (int) $lastCron) / 60) . ' Min.</span>'
		: '<span class="text-danger">letzter Lauf ' . ($lastCron ? 'vor ' . (int) floor((time() - (int) $lastCron) / 60) . ' Min.' : 'unbekannt') . ' - Daemon prüfen</span>';

	$runHtml = $lastRun
		? 'vor ' . (int) floor((time() - $lastRun) / 60) . ' Min. (Intervall: ' . $conf['interval'] . ' Min.)'
		: 'noch kein automatischer Lauf';

	$t = Renderer::getMarkupTemplate('admin.tpl', 'addon/mediacompress/');
	$o = Renderer::replaceMacros($t, [
		'$status'    => $statusHtml,
		'$cron'      => $cronHtml,
		'$lastrun'   => $runHtml,
		'$ffmpeg'    => $ffmpegHtml,
		'$converted' => (int) ($stats['converted'] ?? 0),
		'$savedmb'   => $savedMB,
		'$done'      => count($done),
		'$failed'    => count($failed),
		'$pending'   => count(mediacompress_list('pending_delete')),

		'$enabled'       => ['mediacompress_enabled', DI::l10n()->t('Automatische Konvertierung aktiv'), $conf['enabled'], ''],
		'$interval'      => ['mediacompress_interval', DI::l10n()->t('Intervall (Minuten)'), $conf['interval'], ''],
		'$maxsize'       => ['mediacompress_maxsize', DI::l10n()->t('Max. Dateigröße (MB, 0 = unbegrenzt)'), $conf['maxsize'], ''],
		'$ffmpeg_path'   => ['mediacompress_ffmpeg_path', DI::l10n()->t('ffmpeg-Pfad'), $conf['ffmpeg_path'], ''],
		'$video_height'  => ['mediacompress_video_height', DI::l10n()->t('Video-Zielauflösung (px Höhe)'), $conf['video_height'], ''],
		'$crf'           => ['mediacompress_crf', DI::l10n()->t('x264 CRF (18=beste, 32=stärkste Kompression)'), $conf['crf'], ''],
		'$audio_bitrate' => ['mediacompress_audio_bitrate', DI::l10n()->t('Audio-Bitrate (kbit/s)'), $conf['audio_bitrate'], ''],
		'$timeout'       => ['mediacompress_timeout', DI::l10n()->t('ffmpeg-Timeout (Sekunden)'), $conf['timeout'], ''],
		'$retention'     => ['mediacompress_retention', DI::l10n()->t('Originale behalten (Tage, 0 = sofort löschen)'), $conf['retention'], ''],
		'$fileperm'      => ['mediacompress_fileperm', DI::l10n()->t('Dateirechte (oktal, Standard 0644; 0640 nur wenn Daemon und Webserver eine Gruppe teilen)'), $conf['fileperm'], ''],

		'$submit'  => DI::l10n()->t('Einstellungen speichern'),
		'$process' => DI::l10n()->t('Jetzt bis zu 5 Dateien verarbeiten'),
		'$retry'   => DI::l10n()->t('Fehlgeschlagene Dateien erneut versuchen'),
	]);
}

function mediacompress_addon_admin_post(): void
{
	if (!empty($_POST['mediacompress-submit'])) {
		DI::config()->set('mediacompress', 'enabled',       !empty($_POST['mediacompress_enabled']));
		DI::config()->set('mediacompress', 'interval',      max(1, (int) ($_POST['mediacompress_interval'] ?? 10)));
		DI::config()->set('mediacompress', 'maxsize',       max(0, (int) ($_POST['mediacompress_maxsize'] ?? 200)));
		DI::config()->set('mediacompress', 'video_height',  max(144, (int) ($_POST['mediacompress_video_height'] ?? 720)));
		DI::config()->set('mediacompress', 'crf',           min(40, max(18, (int) ($_POST['mediacompress_crf'] ?? 26))));
		DI::config()->set('mediacompress', 'audio_bitrate', min(192, max(32, (int) ($_POST['mediacompress_audio_bitrate'] ?? 64))));
		DI::config()->set('mediacompress', 'timeout',      max(60, (int) ($_POST['mediacompress_timeout'] ?? 600)));
		DI::config()->set('mediacompress', 'retention',     max(0, (int) ($_POST['mediacompress_retention'] ?? 0)));
		DI::config()->set('mediacompress', 'fileperm',      preg_match('/^0[0-7]{3}$/', trim((string) ($_POST['mediacompress_fileperm'] ?? ''))) ? trim((string) $_POST['mediacompress_fileperm']) : '0644');
		DI::config()->set('mediacompress', 'ffmpeg_path',   trim((string) ($_POST['mediacompress_ffmpeg_path'] ?? 'ffmpeg')) ?: 'ffmpeg');
		$_SESSION['mediacompress_status_success'] = 'Einstellungen gespeichert.';
	}

	if (!empty($_POST['mediacompress-retry'])) {
		$failed = count(mediacompress_list('failed'));
		DI::config()->set('mediacompress', 'failed', []);
		DI::config()->set('mediacompress', 'last_error', null);
		$_SESSION['mediacompress_status_success'] = sprintf('%d fehlgeschlagene Datei(en) für erneuten Versuch freigegeben.', $failed);
	}

	if (!empty($_POST['mediacompress-process'])) {
		if (!mediacompress_conf()['enabled']) {
			$_SESSION['mediacompress_status_error'] = 'Bitte zuerst die automatische Konvertierung aktivieren.';
			return;
		}
		$result = mediacompress_process(5);
		if (!empty($result['error'])) {
			$_SESSION['mediacompress_status_error'] = $result['error'];
		} else {
			$_SESSION['mediacompress_status_success'] = sprintf('%d Datei(en) konvertiert, %d übersprungen.', $result['converted'], $result['skipped']);
		}
	}
}
