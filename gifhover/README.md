# GIF Hover Play (gifhover)

Eingebettete animierte GIFs in Beiträgen werden angehalten. Angezeigt wird der
erste Frame als Standbild mit einem kleinen „GIF“-Badge; das GIF läuft nur,
solange der Mauszeiger über dem Bild steht. Auf Touch-Geräten spielen GIFs
wie gewohnt ab.

## Funktionsweise

Der `prepare_body_final`-Hook markiert GIF-Bilder im Beitrags-HTML mit der
Klasse `gifhover-freeze`. Erkannt werden direkte `.gif`-URLs sowie Bilder über
`/photo/link/{id}` (Tabelle `post-link`) und `/photo/media/{id}` bzw.
`/photo/preview/{size}/{id}` (Tabelle `post-media`), jeweils per gebündelter
MIME-Typ-Abfrage.

Das JavaScript zeigt statt des Bildes eine Canvas-Kopie des ersten Frames und
blendet das animierte Original bei Hover ein. Nachgeladene Beiträge
(Live-Update, Infinite Scroll) werden mitbearbeitet.

## Installation

```bash
cd /var/www/html/friend/addon
unzip gifhover-1.4.zip
cd /var/www/html/friend
php -l addon/gifhover/gifhover.php
bin/console.php addon enable gifhover
```

## Dateien

- `gifhover.php` — Hook-Wiring und GIF-Erkennung
- `gifhover.css` — Styles für Wrapper, Canvas und Badge
- `gifhover.js` — Freeze/Play-Logik (keine externen Abhängigkeiten)

## Grenzen

- Das GIF startet bei Hover nicht neu, sondern läuft weiter.
- Externe GIF-URLs ohne `.gif`-Endung, die nicht über `/photo/…` laufen,
  werden nicht erkannt.
- PNG-Bilder werden nur eingefroren, wenn sie animiert sind (APNG); die
  Prüfung erfolgt im Browser über den `acTL`-Chunk der Datei.
- Custom-Emojis (Misskey/Sharkey, `alt=":shortcode:"`) werden nicht
  angefasst und laufen wie vom Core vorgesehen in Textgröße.
- Friendica versioniert Addon-Stylesheets nicht individuell; nach einem
  Update den Browser-Cache leeren.

Lizenz: AGPL-3.0-or-later
