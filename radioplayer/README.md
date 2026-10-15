# Friendica RadioPlayer Addon

Ein Web-Radio-Player für Friendica. Der **SPA-Modus** stellt sicher, dass der Sender beim Navigieren unterbrechungsfrei weiterspielt.
Bei einem Refresh der Seite wird der letzte Sender über den `localStorage` gespeichert und ausgegeben.

## Features

- **SPA-kompatibel:** Der Audiostream läuft bei Seitenwechseln innerhalb von Friendica ohne Unterbrechung weiter.
- **Letzter Sender:** Merkt sich den zuletzt gewählten Sender sowie die Lautstärke im `localStorage`.
- **Eigene Sender:** Nutzer können in ihren Einstellungen bis zu 20 eigene Stream-URLs hinterlegen.
- **Automatische Namensermittlung:** Der Sendername wird beim Speichern automatisch über die Stream-Metadaten (Icecast/Shoutcast) ausgelesen.

## Installation

1. Erstelle im Ordner `addon/` deiner Friendica-Installation ein neues Verzeichnis namens `radioplayer`.
2. Kopiere die Dateien in diesen Ordner (`addon/radioplayer/`).
3. Aktiviere das Addon in der Admin-Oberfläche unter **Admin -> Addons -> RadioPlayer**.

## Aktivieren durch Nutzende

1. Stelle sicher, dass der **SPA-Modus** in Friendica aktiviert ist.
2. Aktiviere das Addon unter **Einstellungen -> Addons -> RadioPlayer**.
3. Trage auf Wunsch deine eigenen Stream-URLs ein (genau eine URL pro Zeile, maximal 20 Sender).

## Konfiguration

Die Standard-Radio-Streams sind in `radioplayer.php` definiert und können dort bei Bedarf angepasst oder erweitert werden. Benutzerdefinierte Sender werden automatisch vorangestellt.
