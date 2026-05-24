# Fediverse Zeitkapsel

Die **Fediverse Zeitkapsel** ist ein Friendica-Addon, das dir Sicherheit in Bezug auf deine digitale Präsenz gibt. Es fungiert als automatisiertes Tool für dein „digitales Erbe“ und stellt sicher, dass deine vorab verfassten Nachrichten oder letzten Gedanken sicher an deine vertrauenswürdigen Kontakte übermittelt werden, falls du deinen Account nicht mehr selbst verwalten kannst.

---

## Ziel des Addons

Das Hauptziel dieses Addons besteht darin, eine automatisierte, sichere und zuverlässige Methode bereitzustellen, um eine Nachricht an ausgewählte Empfänger zu übermitteln, falls dein Account über einen längeren Zeitraum inaktiv bleibt.

Ob aus Gründen der persönlichen Sicherheit, zur Nachlassplanung oder einfach, um sicherzustellen, dass wichtige Informationen die richtigen Personen erreichen, wenn du dich nicht mehr einloggen kannst – die **Fediverse Zeitkapsel** automatisiert diesen Prozess basierend auf deinen persönlichen Einstellungen.

---

## Hauptfunktionen

- **Anpassbare Inaktivitätsdauer**: Du legst fest, wie lange (3, 6, 12 oder 24 Monate) du inaktiv sein kannst, bevor das System den Benachrichtigungsprozess startet.

- **Zweistufige Sicherheit**:
  1. Nach Ablauf der festgelegten Frist erhältst du eine automatisierte Warn-E-Mail.
  2. Du hast eine **14-tägige Karenzzeit**, um dich einzuloggen und deine Aktivität zu bestätigen.
  3. Falls du dich während dieser Karenzzeit nicht einloggst, wird deine Nachricht ausgelöst.

- **Flexible Zustellung**: Deine Nachricht kann per E-Mail an eine oder mehrere vertrauenswürdige Adressen gesendet werden.

- **Fediverse-Integration**: Optional kannst du wählen, ob deine Nachricht automatisch auf deinem Friendica-Profil veröffentlicht wird. Du hast die volle Kontrolle über die Sichtbarkeit (Öffentlich, Folgen, Gegenseitige Freunde) oder kannst die öffentliche Veröffentlichung deaktivieren und dich auf die private E-Mail-Zustellung beschränken.

- **Benutzerfreundliche Konfiguration**: Einfache Einstellungen direkt in der Konfiguration deines Friendica-Accounts.

---

## Funktionsweise

1. **Konfigurieren**: Lege deine Inaktivitätsschwelle, die E-Mail-Adresse(n) der vertrauenswürdigen Empfänger und deine Nachricht in den Addon-Einstellungen fest.
2. **Überwachen**: Das System überprüft regelmäßig dein letztes Anmeldedatum.
3. **Warnen**: Falls die Schwelle überschritten wird, erhältst du eine Warn-E-Mail.
4. **Aktion**: Falls die Karenzzeit ohne Aktivität abläuft, wird deine Nachricht an die Empfänger gesendet und/oder im Fediverse gemäß deinen Datenschutzpräferenzen veröffentlicht.

---

## Installation

1. Platziere das Verzeichnis `timecapsule` in deinem Friendica-Ordner `addon/`.
2. Aktiviere das Addon in deinem **Admin-Panel** unter „Addons“.
3. Benutzer finden die Konfigurationsoptionen in ihrem persönlichen Menü unter **„Einstellungen“** → **„Fediverse Zeitkapsel“**.

---

## Entwicklung

Entwickelt von **Matthias Ebers**.
Dieses Projekt befindet sich aktuell in der **[BETA-Phase]**.
