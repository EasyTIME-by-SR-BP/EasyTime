#!/usr/bin/env bash
# Legacy-Import für Deploy (Stammdaten ohne Urlaub/Termine).
#
#   bash scripts/import-legacy-deploy.sh /pfad/zur/prod.sql
#
# Produktion (auf dem Server):
#   COMPOSE_PROD=1 bash scripts/import-legacy-deploy.sh import/prod.sql

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SOURCE="${1:-}"
if [[ -z "$SOURCE" || ! -f "$SOURCE" ]]; then
  echo "Usage: bash scripts/import-legacy-deploy.sh /pfad/zur/prod.sql" >&2
  exit 1
fi

compose() {
  if [[ "${COMPOSE_PROD:-}" == "1" ]]; then
    sg docker -c "docker compose -f docker-compose.yml -f docker-compose.prod.yml $*"
  else
    docker compose "$@"
  fi
}

echo "==> 1/5 Legacy-Dump nach SQLite konvertieren (ohne Urlaubsdaten)"
python3 database/convert_legacy_deploy.py --source "$SOURCE" --output database/database.sqlite

echo "==> 2/5 SQLite finalisieren (Passwörter, Demo-Admin, …)"
unset DB_DRIVER
php scripts/finalize-legacy-import.php

echo "==> 3/5 Docker-Stack starten (falls noch nicht aktiv)"
compose up -d --build

echo "==> 4/5 SQLite → MariaDB migrieren (Profil: stammdaten)"
compose --profile migrate run --rm -e MIGRATE_PROFILE=stammdaten migrate

echo "==> 5/5 Finalisierung in MariaDB"
compose run --rm web php scripts/finalize-legacy-import.php

echo ""
echo "Import abgeschlossen (ohne Urlaub/Termine)."
echo "Demo-Admin: A000 / admin@easytime.local — Passwort: easytime"
