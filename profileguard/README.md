# ProfileGuard

## Zweck

ProfileGuard schränkt eine eng umrissene Art von Zugriffen ein: anonyme,
direkte Abrufe, die eine fremde Webseite im Browser ihrer Besucher an
ausgewählte Endpunkte von Friendicas Mastodon-kompatibler Client-API stellt.

ProfileGuard erkennt dabei das technische Muster des Zugriffs, nicht den Zweck
der anfragenden Webseite. Betroffen sein können deshalb Analysewerkzeuge, aber
ebenso externe Profil- oder Feed-Viewer, Widgets und Einbettungen, die auf
dieselbe Weise zugreifen.

ProfileGuard macht öffentliche Beiträge nicht privat und greift nicht in die
normale ActivityPub-Föderation ein. Es ist keine Sperrliste für bestimmte
Webseiten oder Dienste und kein allgemeiner Schutz gegen Scraping.

## Was ProfileGuard einschränkt

Hat ein lokaler Nutzer den Schutz aktiviert, beantwortet Friendica bestimmte
anonyme Browserzugriffe fremder Webseiten auf seine öffentlichen Beiträge und
Interaktionsdaten mit `401 Unauthorized` statt mit den angeforderten Daten:
seine Beitragsliste, seine einzelnen Beiträge, die Listen, wer sie geteilt oder
favorisiert hat, und die Abfrage seines lokalen Accountnamens.

## Wie die Erkennung funktioniert

Eine Anfrage wird nur abgewiesen, wenn **alle** folgenden Bedingungen zutreffen:

- Es ist ein tatsächliches HTTP-`GET`. `HEAD` und alle anderen Methoden gehen
  durch.
- Sie richtet sich an einen der geschützten Endpunkte (siehe unten).
- Sie trägt einen `Origin`-Header einer fremden Webseite und die
  Browser-Header `Sec-Fetch-Site: cross-site`, `Sec-Fetch-Mode: cors` und
  `Sec-Fetch-Dest: empty`.
- Sie ist nicht über die API angemeldet (weder OAuth-Token noch HTTP Basic
  Auth). Ein ungültiges Token zählt als nicht angemeldet.
- Die angefragte Ressource gehört dem eigenen lokalen Account eines Nutzers,
  der den Schutz aktiviert hat.

Fehlt eine Bedingung oder schlägt die Prüfung fehl, geht die Anfrage
unverändert durch. Domains, User-Agents, Referer oder IP-Adressen werden nicht
ausgewertet.

## Was außerdem betroffen sein kann

Weil ProfileGuard nur das Zugriffsmuster erkennt, trifft der Schutz jede fremde
Webseite, die anonym und direkt aus dem Browser auf die geschützten Endpunkte
zugreift, unabhängig davon, ob sie die Daten auswertet, anzeigt oder einbettet.

## Was ProfileGuard nicht einschränkt

- das normale Friendica-Webprofil und die Weboberfläche
- die normale Föderation: ActivityPub, WebFinger, Inbox, Outbox, Objects,
  Follower- und Following-Collections, NodeInfo, Feeds
- öffentliche Timelines und Hashtag-Timelines
- serverseitige Programme und andere Anfragen ohne die genannten
  Browser-Merkmale
- gültig angemeldete API-Aufrufe über OAuth oder Basic Auth
- alle nicht ausdrücklich geschützten API-Endpunkte, darunter
  `/api/v1/accounts/{id}`, `/api/v1/statuses/{id}/context`,
  `/api/v1/statuses/{id}/card`, `/api/v2/…` und die Friendica-eigenen
  API-Wege
- Daten, die bereits an andere Fediverse-Server föderiert wurden

ProfileGuard schützt also nicht Friendicas Mastodon-kompatible Client-API
insgesamt, sondern nur ausgewählte Endpunkte.

## Geschützte API-Endpunkte

- `/api/v1/accounts/{id}/statuses`, wenn `{id}` die öffentliche Kontakt-ID des
  geschützten Nutzers ist
- `/api/v1/statuses/{id}`, `/api/v1/statuses/{id}/reblogged_by` und
  `/api/v1/statuses/{id}/favourited_by`, wenn der angefragte Beitrag von
  diesem Kontakt stammt. Ein Reblog gehört dem Account, der geteilt hat.
- `/api/v1/accounts/{name}`, wenn der Name den geschützten lokalen Nutzer
  bezeichnet

Zur Namensabfrage: Friendica kann `/api/v1/accounts/{name}` mit einem
gleichnamigen Remote-Kontakt beantworten. Bei aktiviertem Schutz wird eine
solche Browseranfrage nach dem eigenen lokalen Accountnamen abgewiesen, damit
sie nicht bei einem gleichnamigen Account einer anderen Instanz landet. Als
eigener Name gelten der Nick, `nick@diese-instanz` und `acct:nick@diese-instanz`,
ohne Unterscheidung von Groß- und Kleinschreibung und ohne Leerzeichen am Ende.
Genau ein lokaler Nutzer muss diesen Nick haben. Gelesen wird nur die Tabelle
`user`, es gibt keinen Kontakt-Lookup. Namen ungeschützter lokaler Nutzer und
von Remote-Accounts sind nicht betroffen. Ein gleichnamiger Remote-Kontakt
bleibt weiterhin abrufbar, etwa über seine numerische ID.

## Pro Nutzer

- Der Schutz wird von jedem lokalen Nutzer selbst unter
  *Einstellungen → Addons → Profilschutz* eingeschaltet.
- Er ist standardmäßig aus. Die Aktivierung des Addons durch den Admin schaltet
  ihn für niemanden ein.
- Aktiviert Nutzer A den Schutz, können nur Ressourcen von A betroffen sein.
  Zugriffe auf Nutzer B, der ihn nicht aktiviert hat, bleiben unverändert.
  Remote-Accounts bleiben ebenfalls unverändert.

## Grenzen des Schutzes

- Öffentliche Beiträge bleiben öffentlich und werden weiterhin föderiert.
- Andere Server können bereits empfangene öffentliche Daten weiterhin speichern,
  anzeigen und auswerten.
- Serverseitige Programme ohne die Browser-Merkmale werden nicht erfasst. Ein
  Programm, das diese Merkmale nachbildet, wird wie ein Browser behandelt.
- Browser, die keine Fetch-Metadata-Header senden, werden durchgelassen.
- Über die ID eines Reblogs durch einen anderen Account sind Daten des
  Originals erreichbar, weil der Reblog diesem Account gehört.
- Ungeschützte lokale Nutzer bleiben von Namenskollisionen betroffen.

## Logging

Abgewiesene Anfragen werden auf Stufe `debug` mit der lokalen Nutzer-ID, dem
API-Pfad und dem `Origin`-Header protokolliert. IP-Adressen, Cookies,
Auth-Header und Tokens werden nicht protokolliert.

## Keine Garantie

Dieser Schutz ist ein Versuch, keine Garantie und kein vollständiger Schutz vor
Tracking oder Analyse. Änderungen an Friendica, an den abfragenden Programmen
oder neue Tricks können dazu führen, dass er nicht oder nicht vollständig
greift. Die Einstellungsseite sagt das den Nutzern ebenso.

## Lizenz

AGPL-3.0-or-later

---

# English

## Purpose

ProfileGuard restricts a narrowly defined class of requests: anonymous direct
requests that another website sends from its visitors' browsers to selected
endpoints of Friendica's Mastodon-compatible client API.

It looks at the technical pattern of a request, not at the purpose of the
website behind it. The protection can therefore affect analytics tools as well
as external profile or feed viewers, widgets and embeds that request data the
same way.

ProfileGuard does not make public posts private and does not interfere with
normal ActivityPub federation. It is not a block list for particular websites or
services, and it is not a general anti-scraping measure.

## What ProfileGuard restricts

When a local user enables the protection, Friendica answers certain anonymous
browser requests from other websites for that user's public posts and
interaction data with `401 Unauthorized` instead of the requested data: the
user's list of posts, individual posts, the lists of who shared or favourited
them, and the lookup of the user's local account name.

## How requests are recognised

A request is refused only if **all** of the following apply:

- It is an actual HTTP `GET`. `HEAD` and all other methods pass.
- It addresses one of the protected endpoints (see below).
- It carries an `Origin` header of another website and the browser headers
  `Sec-Fetch-Site: cross-site`, `Sec-Fetch-Mode: cors` and
  `Sec-Fetch-Dest: empty`.
- It is not authenticated via the API (neither an OAuth token nor HTTP basic
  auth). An invalid token counts as not authenticated.
- The requested resource belongs to the local account of a user who enabled
  the protection.

If any criterion is missing or the check fails, the request passes unchanged.
Domains, user agents, referrers and IP addresses are not evaluated.

## What else can be affected

Because ProfileGuard only recognises the request pattern, the protection applies
to any other website that requests the protected endpoints anonymously and
directly from the browser, whether it analyses, displays or embeds the data.

## What ProfileGuard does not restrict

- the normal Friendica profile page and web interface
- normal federation: ActivityPub, WebFinger, inbox, outbox, objects, followers
  and following collections, NodeInfo, feeds
- public timelines and hashtag timelines
- server-side programs and other requests without the browser characteristics
  listed above
- validly authenticated API requests via OAuth or basic auth
- all API endpoints that are not explicitly protected, including
  `/api/v1/accounts/{id}`, `/api/v1/statuses/{id}/context`,
  `/api/v1/statuses/{id}/card`, `/api/v2/…` and Friendica's own API routes
- data that has already been federated to other Fediverse servers

So ProfileGuard does not protect Friendica's Mastodon-compatible client API as
a whole, only selected endpoints.

## Protected API endpoints

- `/api/v1/accounts/{id}/statuses`, if `{id}` is the public contact id of the
  protected user
- `/api/v1/statuses/{id}`, `/api/v1/statuses/{id}/reblogged_by` and
  `/api/v1/statuses/{id}/favourited_by`, if the requested post was written by
  that contact. A reblog belongs to the account that reblogged.
- `/api/v1/accounts/{name}`, if the name denotes the protected local user

About the name lookup: Friendica can answer `/api/v1/accounts/{name}` with a
remote contact that happens to have the same nickname. With protection enabled,
such a browser request for the user's own local account name is refused, so it
cannot end up at an account of the same name on another instance. The user's
own name is the nickname, `nick@this-instance` or `acct:nick@this-instance`,
case-insensitive and with trailing spaces ignored. Exactly one local user must
have that nickname. Only the `user` table is read; no contact lookup is made.
Names of unprotected local users and of remote accounts are not affected. A
remote contact with the same nickname stays retrievable, for example by its
numeric id.

## Per user

- Each local user enables the protection themselves under
  *Settings → Addons → Profile Guard*.
- It is off by default. Enabling the addon as an administrator turns it on for
  nobody.
- If user A enables the protection, only resources of A can be affected.
  Requests concerning user B, who did not enable it, stay unchanged, and so do
  remote accounts.

## Limits

- Public posts stay public and are still federated.
- Other servers can continue to store, display and evaluate public data they
  have already received.
- Server-side programs without the browser characteristics are not covered. A
  program that imitates these characteristics is treated like a browser.
- Browsers that do not send Fetch Metadata headers pass.
- Data of an original post can be reached via the id of a reblog by another
  account, because the reblog belongs to that account.
- Unprotected local users remain exposed to nickname collisions.

## Logging

Refused requests are logged at debug level with the local user id, the API
command and the `Origin` header. IP addresses, cookies, auth headers and tokens
are not logged.

## No guarantee

This protection is an attempt, not a guarantee, and no complete protection
against tracking or analysis. Changes to Friendica, to the requesting programs
or new tricks may cause it to not take effect, or not fully. The settings page
tells users the same.

## License

AGPL-3.0-or-later
