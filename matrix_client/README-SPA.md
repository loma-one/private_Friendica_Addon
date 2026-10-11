# Matrix Client für Friendica

Das Addon bindet Hydrogen als lokal gehosteten Matrix-Client ein.

## Einbindung im SPA-Modus

- Das Stylesheet wird über den Friendica-Hook `head` geladen.
- Das JavaScript wird über den Hook `footer` geladen.
- Das Widget wird direkt unter `document.body` eingefügt und bleibt damit außerhalb des austauschbaren Seiteninhalts.
- Die Initialisierung prüft, ob das Widget bereits vorhanden ist. Ein `MutationObserver` fügt es bei Bedarf wieder ein.
- Der Hydrogen-Iframe wird bei normalen Inhaltswechseln nicht neu erstellt.
- Die Aktivierung wird in den persönlichen Addon-Einstellungen gespeichert.
- Auf Viewports bis einschließlich 767 Pixel Breite ist das Widget ausgeblendet.
- Der Widget-Bereich unterstützt Scrollen, wenn sein Inhalt mehr Platz benötigt.

## Installation

1. Den Ordner `matrix_client` in das Friendica-Verzeichnis `addon/` kopieren.
2. Das Addon aktivieren. Nach einer Code-Aktualisierung gegebenenfalls deaktivieren und erneut aktivieren, damit Friendica die Hooks neu registriert.
3. In den persönlichen Addon-Einstellungen „Matrix-Client Widget aktivieren“ einschalten.
4. Bei Bedarf Browser-Cache leeren und die Seite vollständig neu laden.

## Prüfung

Die PHP-Syntax kann mit `php -l matrix_client.php` geprüft werden. Nach der Installation sollten SPA-Navigation, Zurück-/Vorwärts-Navigation, Scrollen im Chat und die mobile Ausblendung in der laufenden Friendica-Instanz getestet werden.
