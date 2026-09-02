# Friendica RadioPlayer Addon

Ein Web-Radio-Player für Friendica. Der **SPA-Modus** stellt sicher, dass der Sender  beim Navigieren unterbrechungsfrei weiterspielt.
Bei einem Refresh der Seite wird der letzte Sender über den `localStorage` gespeichert und ausgegeben.

## Features

* **SPA-kompatibel:** Der Audiostream läuft bei Seitenwechseln innerhalb von Friendica ohne Unterbrechung weiter.
* **Letzter Sender:** Merkt sich den zuletzt gewählten Sender sowie die Lautstärke im `localStorage`.

## Installation

1. Erstelle im Ordner `addon/` deiner Friendica-Installation ein neues Verzeichnis namens `radioplayer`.
2. Kopiere die Datei in diesen Ordner (`addon/radioplayer/`).
3. Aktiviere das Addon in der Admin-Oberfläche unter **Admin -> Addons -> RadioPlayer**.

## Aktivieren durch Nutzende

1. Stelle sicher, dass der **SPA-Modus** aktiviert ist
2. Aktiviere das Addon unter **Einstellungen -> Addons -> RadioPlayer**.

## Konfiguration

Die Radio-Streams sind in `radioplayer.php` im Array `$streams` definiert und können dort bei Bedarf angepasst oder erweitert werden:

```php
$rawStreams = [
    ['name' => 'Radio Paradise (Main)', 'url' => 'https://stream.radioparadise.com/aac-320'],
    ['name' => 'WDR2', 'url' => 'https://wdr-wdr2-rheinland.icecast.wdr.de/wdr/wdr2/rheinland/mp3/128/stream.mp3'],
    ['name' => 'DLF Nova', 'url' => 'https://st03.dlf.de/dlf/03/128/mp3/stream.mp3'],
    ['name' => 'Deutschlandfunk', 'url' => 'https://st01.dlf.de/dlf/01/128/mp3/stream.mp3'],
    ['name' => 'Radio Paradise (Mellow Mix)', 'url' => 'https://stream.radioparadise.com/mellow-320'],
    ['name' => 'Radio Paradise (Rock Mix)', 'url' => 'https://stream.radioparadise.com/rock-320'],
    // ...  ]
];
```
