# Legacy-Datenbank in den Deploy importieren

Diese Anleitung beschreibt, wie die **echte Produktions-Datenbank** (MySQL-Dump aus dem alten ahaappup-System) in einen **deployten EasyTime-Stack** (Docker + MariaDB) übernommen wird.

## Was importiert wird

| Übernommen | Nicht übernommen |
|------------|------------------|
| Mitarbeiter (Stammdaten, Rollen) | Urlaubsanträge / -Historie |
| Standorte & Zuordnungen | Urlaubskommentare, Urlaubssperren |
| Führerscheinklassen (`klassen`) | Zeiterfassung (`taetigkeit`, `zuschlag`) |
| Dokumente, Vorlagen | Monatsberichte, Events |
| Eintritt / Abmeldung / Änderungsmeldungen | Überstunden-Überträge |

Nach dem Import:

- **Alle aktiven Mitarbeiter** haben dasselbe Initialpasswort: `easytime` (bcrypt-Hash)
- Beim **ersten Login** muss das Passwort geändert werden
- Danach startet das **Tutorial** automatisch (pro Browser, localStorage)
- Ein **Demo-Admin** (`A000` / `admin@easytime.local`) ist immer vorhanden und kann anderen die Admin-Rolle geben
- Führerschein- und Abteilungs-Pools werden aus Legacy-Daten befüllt (Position → Abteilung, `klassen` → Führerscheinklassen)

## Voraussetzungen

- Docker Desktop auf dem Zielrechner (lokal oder Server)
- MySQL-Dump der Legacy-DB (`.sql`) — **nicht ins Git committen**
- Python 3 und PHP CLI lokal (für Schritt 1–2), oder alles auf dem Server

## Zwei Import-Profile

| Skript | Urlaub/Termine | Einsatz |
|--------|----------------|---------|
| `import-legacy-deploy.sh` | **Nein** | Erster Go-Live, leerer Kalender |
| `import-legacy-deploy-full.sh` | **Ja** | Komplette Historie inkl. Urlaub |

Beide setzen Demo-Passwort (`easytime`), Passwort-Änderung beim Login und Demo-Admin `A000`.

### Vollimport (mit Urlaub/Terminen)

```bash
bash scripts/import-legacy-deploy-full.sh /pfad/zur/prod.sql
```

Produktion auf dem Server:

```bash
COMPOSE_PROD=1 bash scripts/import-legacy-deploy-full.sh import/prod.sql
```

Manuell (Einzel-Schritte):

```bash
python3 database/convert_legacy_deploy_full.py --source prod.sql
env -u DB_DRIVER php scripts/finalize-legacy-import.php
docker compose --profile migrate run --rm -e MIGRATE_PROFILE=full migrate
docker compose run --rm web php scripts/finalize-legacy-import.php
```

---

## Schnellweg (Stammdaten ohne Urlaub)

```bash
# 1. Dump auf den Server kopieren (Beispiel)
scp prod-export.sql user@server:/opt/easytime/import/prod.sql

# 2. Im Projektordner auf dem Server
cd /opt/easytime
cp .env.example .env   # falls noch nicht vorhanden — Passwörter anpassen

bash scripts/import-legacy-deploy.sh /opt/easytime/import/prod.sql
```

Das Skript führt automatisch aus:

1. `convert_legacy_deploy.py` — Dump → `database/database.sqlite`
2. `finalize-legacy-import.php` — Passwörter, Demo-Admin (SQLite)
3. `docker compose up -d`
4. `docker compose --profile migrate run --rm migrate` — SQLite → MariaDB
5. `finalize-legacy-import.php` in MariaDB — Pools, Zuordnungen

## Schritt für Schritt (manuell)

### 1. Legacy-Dump konvertieren

```bash
python3 database/convert_legacy_deploy.py \
  --source /pfad/zur/prod.sql \
  --output database/database.sqlite
```

Optional mit Test-Dump aus dem Repo:

```bash
python3 database/convert_legacy_deploy.py
# nutzt standardmäßig ahaappup (1).sql
```

### 2. SQLite finalisieren (vor Migration)

```bash
# DB_DRIVER nicht setzen → SQLite
php scripts/finalize-legacy-import.php
```

### 3. Docker-Stack starten

Lokal:

```bash
docker compose up -d --build
```

Produktion:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### 4. In MariaDB migrieren

```bash
docker compose --profile migrate run --rm migrate
```

Die Datei `database/database.sqlite` wird dabei als Volume gemountet.

### 5. MariaDB finalisieren

```bash
docker compose run --rm web php scripts/finalize-legacy-import.php
```

## Erster Login nach Import

| Feld | Wert |
|------|------|
| Demo-Admin Personal-ID | `A000` |
| Demo-Admin E-Mail | `admin@easytime.local` (oder `LEGACY_DEMO_ADMIN_EMAIL` in `.env`) |
| Passwort (alle) | `easytime` |

Ablauf:

1. Mit Demo-Admin einloggen → Passwort ändern
2. Tutorial startet danach automatisch
3. Unter **Team** anderen Mitarbeitern die Rolle **Administrator** zuweisen

## Umgebungsvariablen

In `.env` optional:

```env
LEGACY_DEMO_ADMIN_EMAIL=admin@ihre-firma.at
```

## Bestehende MariaDB ersetzen

**Achtung:** Migration überschreibt alle Tabelleninhalte in den importierten Bereichen.

```bash
# Volume löschen (alle DB-Daten weg!)
docker compose down
docker volume rm easytime_db_data

# Neu starten + importieren
docker compose up -d --build
bash scripts/import-legacy-deploy.sh /pfad/zur/prod.sql
```

## Hinweise

- **Urlaubsanspruch:** Legacy speichert teils Stunden; EasyTime zeigt Tage — Werte nach Import stichprobenartig prüfen.
- **Inaktive Mitarbeiter** (`status != 0`) erhalten kein `must_change_password`-Flag.
- Legacy-Admins mit `berechtigung = Administrator` bleiben Admins, haben aber ebenfalls Initialpasswort `easytime`.
- Der Dump darf **nicht** direkt in MariaDB importiert werden (Spaltenumbenennungen, z. B. `genemigt` → `genehmigt`).

## Fehlerbehebung

| Problem | Lösung |
|---------|--------|
| `SQLite file not found` | Zuerst `convert_legacy_deploy.py` ausführen |
| `Quelldatei nicht gefunden` | `--source` mit absolutem Pfad angeben |
| Leere Führerschein/Abteilung-Filter | `finalize-legacy-import.php` erneut in MariaDB ausführen |
| Tutorial startet nicht | Passwort-Änderung muss abgeschlossen sein; Browser-Cache/localStorage leeren für erneuten Start |
