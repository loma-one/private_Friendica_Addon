# QuickPhoto Addon für Friendica

QuickPhoto ist ein Addon für Friendica, das die Arbeit mit Bildern im Editor vereinfacht. Es ersetzt die langen, unübersichtlichen BBCode-Strukturen automatisch durch eine kompakte Kurzschreibweise, ohne die Funktionalität oder Kompatibilität zu beeinträchtigen.

### Features

 * Automatische Vereinfachung: Verwandelt "Monster-BBCodes" wie [url=...][img=...]...[/img][/url] sofort in das handliche Format [img]dateiname|beschreibung[/img].

 * Intelligente Rekonstruktion: Vor dem Absenden oder in der Vorschau wird der Kurzcode blitzschnell wieder in den originalen, validen Friendica-BBCode umgewandelt.

 * Echtzeit-Verarbeitung: Reagiert sofort auf Drag & Drop, Copy & Paste sowie auf das Einfügen von Bildern über Editor-Buttons.

 * Fokus-Sicherheit: Dank Cursor-Verwaltung bleibt der Fokus beim Tippen auch während der automatischen Umwandlung stabil.

 * Maximale Kompatibilität: Unterstützt sowohl den Standard-Jot-Editor als auch das Compose-Modul und Antwort-Felder.

 * Lokaler Cache: Bilddaten werden sicher im localStorage des Browsers zwischengespeichert und nach 12 Stunden automatisch bereinigt.

### Funktionsweise

Das Addon arbeitet hybrid:

 * Frontend: Ein JavaScript-Wächter scannt die Textareas und vereinfacht komplexe Bild-Verlinkungen für eine bessere Lesbarkeit während des Schreibens.

 * Schnittstelle: Es klinkt sich tief in die jQuery-Funktionen von Friendica ein, um sicherzustellen, dass Vorschau- und Speicherfunktionen immer die korrekten Original-Daten erhalten.

 * Events: Durch das Abfangen von Submit- und Vorschau-Klicks wird sichergestellt, dass niemals Kurzcodes an den Server gesendet werden, die dieser nicht interpretieren könnte.

### Installation

 * Erstelle im Verzeichnis addon/ deiner Friendica-Installation einen Ordner namens quickphoto.

 * Lege die Datei quickphoto.php in diesen Ordner.

 * Lege die Datei quickphoto.js ebenfalls in diesen Ordner.

 * Aktiviere das Addon im Administrationsbereich von Friendica unter Addons.
 
 ---

MIT License

Copyright (c) 2024-2026 Friendica Project & Contributors

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
