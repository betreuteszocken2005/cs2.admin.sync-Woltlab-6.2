# CS2 Admin Sync (WoltLab Suite 6.2)

**Paket:** `com.ddbmaster.cs2.admin.sync`

Synchronisiert Benutzergruppen aus der **WoltLab Suite 6.2** automatisch mit den Admins von **CS2-SimpleAdmin** (CounterStrikeSharp). Wer im Forum in einer konfigurierten Gruppe ist, bekommt auf dem CS2-Server die dazugehörigen Rechte. Wer die Gruppe verliert, verliert beim nächsten Sync auch die Rechte.

Alles wird im ACP eingestellt, es muss kein Code angepasst werden.

## Funktionen

- Beliebige Forum-Gruppen auswählen und pro Gruppe festlegen:
  - welche **Rechte** (`@css/...`-Flags) sie bekommt,
  - die **Immunität**,
  - die **Priorität** (bei Mitgliedschaft in mehreren Gruppen gewinnt die höchste).
- **Cronjob** für den automatischen Sync (Standard: alle 10 Minuten, im ACP einstellbar).
- Button **"Speichern und jetzt synchronisieren"** für sofortige Änderungen.
- Steam-ID wird aus einem frei wählbaren Profilfeld gelesen. Unterstützt werden SteamID64, `STEAM_X:Y:Z` und `steamcommunity.com/profiles/...`-Links.
- Gesperrte Benutzer und Benutzer ohne gültige Steam-ID werden übersprungen.

## Voraussetzungen

- WoltLab Suite **6.2** (nicht 6.3 oder neuer)
- CS2-Server mit **CounterStrikeSharp** und **CS2-SimpleAdmin** (MySQL-Anbindung)
- PHP-Erweiterung `pdo_mysql`
- Ein Profilfeld im Forum, in dem die Steam-ID steht (WoltLab bringt keins mit)

## Installation

1. `com.ddbmaster.cs2.admin.sync.tar` bauen (`./build.sh`) oder aus den Releases laden.
2. ACP → **Pakete → Paket installieren** → Datei hochladen.
3. **Einstellungen → CS2 Admin-Sync:**
   - Datenbank-Zugang der SimpleAdmin-Datenbank (Host, Port, Name, Benutzer, Passwort)
   - `optionName` des Steam-ID-Profilfelds (Standard: `steamID`)
4. **Konfiguration → CS2 Admin-Sync → Gruppen & Rechte:** Gruppen aktivieren und Rechte wählen.
5. Auf dem Server nach dem Sync `css_reloadadmins` ausführen oder die Map wechseln, damit SimpleAdmin die Änderungen übernimmt.

## Funktionsweise

Bei jedem Sync passiert Folgendes:

1. Die konfigurierten Gruppen werden nach Priorität (höchste zuerst) abgearbeitet.
2. Aus jeder Gruppe werden die nicht gesperrten Mitglieder mit gültiger Steam-ID geladen. Wer schon über eine höhere Gruppe erfasst wurde, wird übersprungen.
3. In der SimpleAdmin-Datenbank werden in **einer Transaktion** die Tabellen `sa_admins_flags` und `sa_admins` geleert und mit der neuen Liste gefüllt.

Das Forum ist damit die **einzige Quelle**. Manuell per Ingame-Befehl angelegte Admins werden beim nächsten Sync überschrieben.

## Sicherheit

- **Leere Liste wird nie geschrieben.** Findet der Sync keinen Admin, bricht er ab, damit sich niemand aussperrt.
- Die Gruppen **Alle**, **Gäste** und **Benutzer** sind nicht auswählbar.
- Die Seite "Gruppen & Rechte" ist durch die eigene Gruppenberechtigung `admin.cs2admin.canManage` geschützt (Standard: nur Administratoren). Wer sie hat, kann sich selbst `@css/root` geben.
- Alle Datenbankzugriffe nutzen Prepared Statements, Steam-IDs werden per Regex validiert und Flags gegen eine feste Liste geprüft.
- Das DB-Passwort liegt wie alle WoltLab-Optionen **unverschlüsselt** in der Forum-Datenbank. Verwende einen eigenen MySQL-Benutzer, der nur Rechte auf die SimpleAdmin-Datenbank hat.
- Die Rechte werden nur anhand der Steam-ID vergeben. Gib `@css/root`, `@css/rcon`, `@css/cvar` und `@css/config` nur an Personen, denen du den Server voll anvertraust.

## Hinweise zu SimpleAdmin

Das Plugin erwartet diese Tabellen und Spalten (je nach SimpleAdmin-Version können sie abweichen):

| Tabelle | Spalten |
|---|---|
| `sa_admins` | `player_steam_id`, `player_name`, `immunity`, `ends`, `created` |
| `sa_admins_flags` | `admin_id`, `flag`, `server_id` |

Fehlt eine Tabelle oder Spalte, bricht der Sync mit einer Fehlermeldung ab, ohne etwas zu verändern. Rechte werden global vergeben (`server_id = NULL`).

## Status

Bisher nur auf Syntax geprüft, noch nicht in einer produktiven WoltLab-Installation getestet. Bitte zuerst auf einer Testinstanz ausprobieren. Fehler und Verbesserungen gerne als Issue melden.

## Projektstruktur

```
com.ddbmaster.cs2.admin.sync/
├── package.xml
├── option.xml              # Einstellungen (DB-Zugang, Steam-Profilfeld)
├── userGroupOption.xml     # Berechtigung admin.cs2admin.canManage
├── acpMenu.xml             # ACP-Menü
├── cronjob.xml             # automatischer Sync
├── acp/database/           # Tabelle wcf1_cs2admin_group
├── language/               # de, en
├── acptemplates/           # cs2AdminGroups.tpl
├── files/lib/
│   ├── acp/form/Cs2AdminGroupsForm.class.php
│   ├── system/cs2admin/Cs2AdminSyncer.class.php
│   └── system/cronjob/Cs2AdminSyncCronjob.class.php
└── build.sh                # baut die installierbare .tar
```

## Lizenz

Noch nicht festgelegt. Vor der Veröffentlichung eine `LICENSE`-Datei hinzufügen.
