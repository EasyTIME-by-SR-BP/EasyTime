# EasyTime – Docker

Projektordner: `EasyTime` · Container: `easytime-web`, `easytime-db` · DB: `easytime`

## Voraussetzungen

- Docker Desktop (lokal installiert)
- Optional: Legacy-Daten aus `ahaappup (1).sql`

## Schnellstart

```bash
cp .env.example .env
docker compose up -d --build
```

App: **http://localhost:8080**

## Daten aus Legacy-SQL importieren (Deploy)

**Produktions-Dump:** siehe [docs/DEPLOY-LEGACY-IMPORT.md](docs/DEPLOY-LEGACY-IMPORT.md)

Kurzversion mit Test-Dump:

```bash
python3 database/convert_legacy_deploy.py
php scripts/finalize-legacy-import.php
docker compose up -d --build
docker compose --profile migrate run --rm migrate
docker compose run --rm web php scripts/finalize-legacy-import.php
```

Oder alles in einem Schritt:

```bash
bash scripts/import-legacy-deploy.sh /pfad/zur/prod.sql
# Mit Urlaub/Terminen:
bash scripts/import-legacy-deploy-full.sh /pfad/zur/prod.sql
```

Der alte `convert_import.py` importiert weiterhin **alle** Tabellen inkl. Urlaub (nur für Tests).

## Produktion auf Server

```bash
cp .env.example .env
# Passwörter in .env anpassen
bash scripts/generate-self-signed-cert.sh

docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

- **HTTP:** `http://<SERVER-IP>` (Port 80)
- **HTTPS:** `https://<SERVER-IP>:445` (selbstsigniertes Zertifikat — Browser-Warnung bestätigen)

Datenbank bleibt im Volume `db_data`.

## Deployment (wie Sesamo)

Zuerst `git push`, dann einen Release-Befehl:

```bash
pnpm release:[branch]:[target]
```

| Befehl | Ziel |
|--------|------|
| `pnpm release:main:full` | Web + DB + Nginx |
| `pnpm release:main:web` | Nur PHP-App |
| `pnpm release:main:nginx` | Nur Nginx |
| `pnpm release:main:db` | Nur MariaDB |

GitHub Secrets: `SERVER_IP`, `SERVER_USER`, `SERVER_SSH_PORT`, `SSH_PRIVATE_KEY`, `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD`

Optionale GitHub **Variables** / **Secrets** für Domain & Mail (siehe README.md) — fehlen sie, schreibt der Deploy leere bzw. sichere Default-Werte (`MAIL_ENABLED=false`).

## Services

| Service | Beschreibung |
|---------|--------------|
| `web` | PHP 8.2 + Apache, Port `APP_PORT` (Standard 8080) |
| `db` | MariaDB 11, Schema aus `database/mariadb/` |
| `migrate` | Einmal-Job: `database.sqlite` → MariaDB (Profil `migrate`) |
| `mailpit` | Lokaler SMTP-Test (Profil `mail`), UI auf Port 8025 |

## E-Mail lokal testen (Mailpit)

```bash
docker compose --profile mail up -d
```

In `.env` setzen:

```env
MAIL_ENABLED=true
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_ENCRYPTION=none
MAIL_SMTP_AUTH=false
APP_URL=http://localhost:8080
```

Mailpit-UI: **http://localhost:8025**

## Umgebungsvariablen

Siehe [`.env.example`](.env.example). Wichtig:

- `DB_HOST=db` (Service-Name in Docker)
- `DB_DRIVER=mysql`

Ohne Docker (nur lokal): `DB_DRIVER` weglassen → SQLite unter `database/database.sqlite`.
