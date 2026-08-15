# Friendica Wikipedia Link Addon

Ein leichtgewichtiges Friendica-Addon, das `[wiki]Begriff[/wiki]` in anklickbare Wikipedia-Links mit Icon umwandelt.

### Funktionen

* **BBCode-Transformation:** Wandelt `[wiki]Begriff[/wiki]` dynamisch beim Anzeigen in Wikipedia-Links um.
* **Editor-Autovervollständigung:** Erweitert die Eingabe von `[w` im Editor automatisch zu `[wiki]|[/wiki]`.

### Verwendung & Sprachkürzel

Du kannst den BBCode ohne oder mit Sprachkürzel verwenden:

* **Standard (ohne Kürzel):**  
  `[wiki]München[/wiki]` > verlinkt auf die **deutsche** Wikipedia (`de.wikipedia.org`)
* **Mit Sprachkürzel:**  
  `[wiki=en]Munich[/wiki]` > verlinkt auf die **englische** Wikipedia (`en.wikipedia.org`)

#### Unterstützte Sprachkürzel
Das Addon akzeptiert alle **2- und 3-stelligen ISO-Sprachkürzel** (Klein- oder Großschreibung):

* **2-stellige Standardkürzel:** `de` (Deutsch), `en` (Englisch), `fr` (Französisch), `es` (Spanisch), `it` (Italienisch), `nl` (Niederländisch) weitere.
* **3-stellige Regionalkürzel:** `bar` (Bairisch), `nds` (Plattdeutsch), `gsw` (Alemannisch) weitere.
* **Fallback:** Fehlt das Kürzel (`[wiki]Begriff[/wiki]`), wird automatisch `de` genutzt.

### Installation

1. Erstelle den Ordner `addon/wikipedia/` auf deinem Friendica-Server.
2. Lege darin die Datei `wikipedia.php` ab.
3. Aktiviere das Addon im Admin-Bereich unter **Administrator** >  **Addons** > **Wikipedia Link**.

---

MIT License

Copyright (c) 2024-2026 Friendica Project & Contributors

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
