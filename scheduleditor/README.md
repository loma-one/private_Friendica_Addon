# Scheduled Editor (Friendica Addon)

Bearbeiten, Löschen und Neuerstellen geplanter, noch nicht veröffentlichter
Beiträge: Titel, Zusammenfassung, Text und Veröffentlichungszeitpunkt. Das
Addon ergänzt die Core-Funktion `/profile/{nickname}/schedule` (dort ist nur
Löschen möglich) um eine eigene Bearbeitungsansicht und fügt einen Eintrag ins
Nutzermenü ein.

**Kompatibilität:** > Friendica 2026.08-rc, PHP ≥ 8.2, frio-Theme empfohlen.

## Installation

1. Verzeichnis `scheduleditor/` nach `addon/scheduleditor/` kopieren.
2. Addon im Admin-Panel oder per `bin/console addon enable scheduleditor`
   aktivieren.
3. Aufruf: `https://<node>/scheduleditor`

## Funktionsweise

„Bearbeiten = Laden → Löschen → neu Einplanen", ausschließlich über öffentliche
Core-APIs (`Friendica\Model\Post\Delayed`):

1. **Laden:** `Post\Delayed::getParametersForid($id)` liefert das komplette Item
   (Body, Titel, ACL-Felder, Taglist, Anhänge) aus dem Worker-Queue-Eintrag.
2. **Speichern:** Nach Validierung (Body nicht leer, Zielzeit in der Zukunft)
   wird der alte Eintrag per `Post\Delayed::deleteById($id)` entfernt und derselbe
   Beitrag per `Post\Delayed::add()` mit gleichem URI und neuem Zeitpunkt neu
   eingeplant.

Die Reihenfolge „erst validieren, dann löschen, dann neu anlegen" stellt sicher,
dass bei Fehlern der ursprüngliche Eintrag erhalten bleibt.

### Zeitzonen

Das Datumsfeld (datetime-local) wird in der Zeitzone des eingeloggten Nutzers
interpretiert (`User::getById` → `DateTimeFormat::convert($raw, 'UTC', $userTz)`)
und nicht in der Server-Zeitzone. Auch die angezeigten Zeitpunkte werden in die
Nutzer-Zeitzone umgerechnet.

### Privatsphäre

ACL-Einstellungen (`allow_cid` etc.) werden unverändert durchgereicht und nur
angezeigt (Schloss-Symbol), nicht editiert.

## Aufbau

```text
scheduleditor/
├── scheduleditor.php              # Modul: Liste, Bearbeitung, Neuerstellung (Content/Post)
└── templates/scheduleditor.tpl    # Smarty-Template (Liste + Compose-Formular)
```

## Bekannte Grenzen

- ACL nicht editierbar (Durchreichung; Anzeige des Schloss-Symbols).
- Anhänge/Medien werden durchgereicht, aber nicht im Formular editierbar.
- Bearbeiten ist nur vor der Veröffentlichung möglich; danach greift die
  normale Post-Bearbeitung von Friendica.
