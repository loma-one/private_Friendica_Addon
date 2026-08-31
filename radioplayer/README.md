# Friendica RadioPlayer Addon

Ein persister Web-Radio-Player für Friendica. Der SPA-Modus stellt sicher, dass der Sender  beim Navigieren unterbrechungsfrei weiterspielt.
Bei einem Refresh der Seite wird der letzte Sender über den `localStorage` gespeichert und ausgegeben.

## Features

* **SPA-kompatibel:** Der Audiostream läuft bei Seitenwechseln innerhalb von Friendica ohne Unterbrechung weiter.
* **Persistenter Zustand:** Merkt sich den zuletzt gewählten Sender sowie die Lautstärke im `localStorage`.

## Installation

1. Erstelle im Ordner `addon/` deiner Friendica-Installation ein neues Verzeichnis namens `radioplayer`.
2. Kopiere die Datei `radioplayer.php` in diesen Ordner (`addon/radioplayer/radioplayer.php`).
3. Aktiviere das Addon in der Admin-Oberfläche unter **Admin -> Addons -> RadioPlayer**.

## Konfiguration

Die Radio-Streams sind in `radioplayer.php` im Array `$streams` definiert und können dort bei Bedarf angepasst oder erweitert werden:

```php
$streams = [
    ['name' => 'Radio Paradise (Main)', 'url' => '[https://stream.radioparadise.com/aac-320](https://stream.radioparadise.com/aac-320)'],
    ['name' => 'WDR2', 'url' => '[https://wdr-wdr2-rheinland.icecast.wdr.de/wdr/wdr2/rheinland/mp3/128/stream.mp3](https://wdr-wdr2-rheinland.icecast.wdr.de/wdr/wdr2/rheinland/mp3/128/stream.mp3)'],
    // ...
];
