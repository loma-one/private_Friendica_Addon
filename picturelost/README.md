# PictureLost Addon für Friendica

Das **PictureLost** Addon durchsucht die Friendica-Datenbank nach verwaisten Bildern (Dateileichen), die zwar in der Galerie des Nutzers existieren, aber in keinem Beitrag (`post-content`) mehr aktiv verwendet werden. 

Es bietet eine schlanke Oberfläche, um diese Bilder schnell zu identifizieren und direkt über die Standard-Galerie restlos zu löschen.

## Features

-  Jeder Nutzer sieht ausschließlich seine eigenen Bilder.
-  Die Abfrage läuft direkt datenbankoptimiert über Indizes (keine PHP-Timeouts).
-  Zeigt quadratische Vorschaubilder (Thumbnails) direkt in der Übersicht.
-  Ein Klick auf den roten Dateinamen öffnet die Friendica-Löschseite des Bildes in einem neuen Tab.

## Installation & Aktivierung

1. Kopiere den gesamten Ordner `picturelost` in das Addon-Verzeichnis deiner Friendica-Instanz:
   ```text
   /var/www/html/friendica/addon/picturelost/
