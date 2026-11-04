# MediaCompress

Friendica-Addon zum serverweiten Verkleinern von Video- und MP3-Anlagen (ab Friendica 2026.08). Die Dateien werden mit ffmpeg neu kodiert (Videos auf eine Zielauflösung mit H.264/CRF, MP3 auf eine Zielbitrate). Das Original wird nur ersetzt, wenn die neue Datei **kleiner und valide** ist.

Wichtig zu wissen: Das Addon betrifft **alle Accounts der Instanz** – nicht nur einzelne Nutzer. Bestehende Beitrags-Links (`/attach/{id}`) bleiben intakt, da der Datensatz in-place aktualisiert wird.

## Voraussetzungen

- **ffmpeg** auf dem Server (Pfad konfigurierbar, Standard `ffmpeg` im PATH)
- **ffprobe** empfohlen (liegt ffmpeg-Paketen normalerweise bei). Fehlt ffprobe, arbeitet das Addon ohne Ausgabe-Validierung weiter (Warnung im Log)
- MariaDB: `max_allowed_packet` mindestens so groß wie das Addon-Limit `maxsize` (prüfbar mit `SELECT @@max_allowed_packet;`)
- PHP-Arbeitsspeicher: Faustregel `maxsize` ≈ ein Viertel des `memory_limit`, da die Datei beim Konvertieren vollständig im Speicher liegt
- Aktiver Daemon (`bin/console daemon`) – das Addon wird ausschließlich über den `cron`-Hook aktiv. Der Daemon sollte unter dem gleichen Benutzer laufen wie der Webserver (z. B. via systemd-Unit mit `User=www-data`)

## Installation

Dateistruktur:

```
addon/mediacompress/
├── mediacompress.php
├── templates/
│   └── admin.tpl
└── README.md
```

1. Alle Dateien wie oben dargestellt nach `addon/mediacompress/` kopieren
2. Admin → **Addons** → MediaCompress aktivieren
3. Admin-Seite des Addons öffnen: „Automatische Konvertierung aktiv“ setzen, Einstellungen speichern
4. **Bei Daemon-Betrieb:** Daemon einmal neu starten, damit der laufende Prozess die Hooks des Addons lädt. **Wichtig:** Als derselben Benutzer starten wie der laufende Daemon bzw. über die systemd-Unit (`systemctl restart friendica-daemon`) – niemals aus einer root-Shell, wenn der Daemon als `www-data` läuft (sonst schreiben die Worker root-owned Dateien, die der Webserver nicht lesen kann)

## Wie der Automatikbetrieb arbeitet

```
Daemon erzwingt Cron (alle system/cron_interval Minuten, Standard 5)
  → Cron::run() → Hook::fork('cron')
    → ForkHook-Task in der Worker-Queue
      → mediacompress_cron() (asynchron, einen Moment später)
        → Intervallprüfung gegen last_run (Standard 10 Min.)
          → fällige Originale löschen, dann genau EINE Datei konvertieren
```

Die Konvertierung läuft damit im Worker-Kontext, nicht im Web-Request. Der manuelle Button umgeht diese Kette und arbeitet direkt im Admin-Request – beide Wege sind über ein Lock gegeneinander abgesichert.

## Einstellungen


| Option                           | Standard | Bedeutung                                                                  |
| -------------------------------- | -------- | -------------------------------------------------------------------------- |
| Automatische Konvertierung aktiv | aus      | Hauptschalter für den cron-Betrieb                                         |
| Intervall (Minuten)              | 10       | Abstand zwischen zwei Verarbeitungs-Läufen                                 |
| Max. Dateigröße (MB)             | 200      | Größere Dateien werden übersprungen; 0 = unbegrenzt                        |
| Video-Zielauflösung (px Höhe)    | 720      | Wird nur verkleinert, nie hochskaliert                                     |
| x264 CRF                         | 26       | 18 = beste, 32 = stärkste Kompression                                      |
| Audio-Bitrate (kbit/s)           | 64       | Zielbitrate für MP3-Neukodierung                                           |
| ffmpeg-Timeout (Sekunden)        | 600      | Härteschutz gegen Endlos-Kodierung                                         |
| Originale behalten (Tage)        | 0        | Karenzzeit vor dem Löschen des Originals                                   |
| Dateirechte (oktal)              | 0644     | Dateirechte neuer Blobs beim Filesystem-Storage (siehe Sicherheitshinweis) |


## Admin-Seite

Die Seite wird über das Smarty-Template `templates/admin.tpl` gerendert (`Renderer::getMarkupTemplate`) und nutzt die Core-Feld-Templates (`field_checkbox.tpl`, `field_input.tpl`) – die Felder erben automatisch das Theme-Styling.

- **Cron (Friendica):** grün „aktiv, letzter Lauf vor X Min." oder rot mit Hinweis „Daemon prüfen", wenn der Core-Cron (Wert `last_cron` in der `key-value`-Tabelle) älter als 15 Minuten ist
- **Letzter Addon-Lauf:** „vor X Min." oder „noch kein automatischer Lauf" – der Wert entspricht `last_run` und muss im Intervall-Takt wandern
- **Status:** ffmpeg gefunden / nicht gefunden
- **Statistik:** konvertierte Dateien, gesparte MB, erledigt/fehlgeschlagen, vorgemerkte Originale
- **Buttons:**
  - *Einstellungen speichern*
  - *Jetzt bis zu 5 Dateien verarbeiten* – einmaliger Sofort-Lauf (blockiert den Admin-Request für die Dauer der Konvertierung)
  - *Fehlgeschlagene Dateien erneut versuchen* – leert die Fehlerliste, z. B. nach dem Beheben einer Fehlerursache

## Diagnose

- **Läuft der Automatikbetrieb?** Auf der Admin-Seite: „Cron (Friendica)" grün und „Letzter Addon-Lauf" mit aktuellem Wert. Oder per Console: `bin/console config mediacompress` → `last_run` muss vorhanden sein und im Intervall-Takt wandern
- **Läuft der Cron überhaupt?** `SELECT * FROM` key-value `WHERE k LIKE 'last_cron%';` – der Zeitstempel sollte Minuten alt sein (Umrechnung: `date -d @<Wert>`)
- **Log:** `grep mediacompress friendica.log` – jede Konvertierung erzeugt eine `converted`-Zeile (mit `before`/`after`); `grep ForkHook friendica.log` zeigt die Hook-Kette
- **Letzter Fehler:** wird auf der Admin-Seite angezeigt und unter `last_error` gespeichert

Hinweis: Ein Befehl `bin/console cron` existiert in 2026.08 nicht. Ein einmaliger manueller Worker-Lauf (falls erwünscht und kein Daemon aktiv) erfolgt über `bin/console worker`.

## Betriebsverhalten

- **Eine Datei pro Lauf:** Der cron-Hook konvertiert genau **eine** Datei (größte zuerst, Altbestand inklusive). Der Server wird dadurch nie mit parallel laufenden ffmpeg-Prozessen belastet.
- **Lock:** `mediacompress_worker` verhindert parallele Läufe – auch zwischen Cron und den manuellen Buttons.
- **Nie teuer als nötig:** ffmpeg läuft mit `nice -n 19` und `timeout`.
- **Ersetzungsregeln:** Kleiner UND valide (ffprobe-Prüfung der Zieldauer) UND kleiner als das Original – sonst bleibt das Original unberührt und die Datei gilt als erledigt.
- **Löschfolge (bewusst crash-sicher):** erst neues Blob schreiben → Rechte setzen und Lesbarkeit prüfen → Datensatz umstellen → **dann erst** Original löschen bzw. vormerken. Im Worst Case entsteht Speichermüll, niemals Datenverlust.
- **post-media** bekommt die neue Dateigröße mit, damit Anzeige und Player konsistent bleiben.

## Verhalten bei Fehlern

- Konvertierung schlägt fehl (ffmpeg-Abbruch, ungültige Zieldatei, Storage-Fehler) → Datei bleibt unverändert, wird mit Fehlermeldung auf die `failed`-Liste gesetzt und **nicht** erneut versucht
- Rechte-Fehler (Filesystem-Storage): Kann die neue Datei nach dem chmod nicht gelesen werden, wird sie gelöscht und die Konvertierung abgebrochen – das Original bleibt unangetastet, es entsteht **kein toter Attachment-Link**. Die Meldung nennt konkret die Ursache („Datei nicht gefunden“ / „chmod fehlgeschlagen“ / „nicht lesbar“) samt betroffenem Pfad und erscheint als `last_error` auf der Admin-Seite
- Über dem Größenlimit → wird übersprungen und **blockiert den Lauf nicht**: Der Cron geht zur nächsten konvertierbaren Datei weiter. Die Datei bleibt dauerhaft Kandidat (erhöht man das Limit, wird sie automatisch wieder bearbeitet)
- Kleinere Dateien als 256 KB → dauerhaft übersprungen, nicht lohnend
- Ein erneuter Konvertierungsversuch einer bereits komprimierten Datei (z. B. nach Config-Verlust) endet erwartungsgemäß mit „kein Gewinn“ – die Datei wird danach endgültig erledigt, es entsteht kein Generationsverlust

## Sicherheitshinweis (Filesystem-Storage)

Der Zugriffsschutz für Anhänge liegt bei Friendica in der Applikation, nicht im Dateisystem. Der Core legt neue Dateien mit `0660` an – läuft der Worker unter dem gleichen Benutzer wie der Webserver (empfohlen), funktioniert das. Läuft der Worker unter einem anderen Benutzer (z. B. versehentlich als root gestarteter Daemon), setzt das Addon neue Blobs nach dem Schreiben auf die konfigurierbaren Rechte (Standard `0644`), damit der Webserver sie ausliefern kann. Auf Servern mit unbekannten lokalen Shell-Accounts ist `0640` vorzuziehen – das funktioniert, wenn Daemon und Webserver einer gemeinsamen Gruppe angehören. Scheitert das Setzen der Rechte, bricht die Konvertierung ab, bevor das Original ersetzt wird.

## Bekannte Grenzen (Known Limitations)

- **Verwaltung der erledigten/fehlgeschlagenen Dateien:** Die IDs werden als serialisierte Arrays in der Friendica-Konfiguration gespeichert. Für Instanzen mit einigen hundert bis wenigen tausend Videos ist das völlig unkritisch; bei sehr großen, dauerhaft wachsenden Instanzen wächst das Array mit jedem Eintrag (sichtbar am `bin/console config mediacompress`-Output). Ein eigenes Statusfeld in einer Datenbanktabelle wäre die langfristige Ablösung – bewusst als künftige Option zurückgestellt, um keine Core-Tabellen zu erweitern.
- **Unwiderruflichkeit:** Nach Ablauf der Karenzzeit wird das Original gelöscht. Die Karenzzeit (`Originale behalten`) und ein Storage-Backup vor der ersten Aktivierung sind die Sicherheitsschienen – danach gibt es keinen Weg zurück zum Original.

## Empfehlung für die ersten Wochen

- `Originale behalten` auf 5–7 Tage stellen, solange die ersten Konvertierungen noch nicht in der Praxis erprobt sind
- Ein Server-Backup der `attach`-Blobs vor der ersten Aktivierung ist grundsätzlich angeraten
- Nach 1–2 Wochen stabiler Statistik ohne Fehler kann auf `0` gestellt werden
