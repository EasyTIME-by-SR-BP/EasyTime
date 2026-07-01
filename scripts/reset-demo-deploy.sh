#!/usr/bin/env bash
# Demo-Daten auf dem Produktions-Server zurücksetzen (2 Admins, 10 Mitarbeiter).
#
#   bash scripts/reset-demo-deploy.sh
#
# Lokal (SQLite):
#   pnpm db:seed

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

if [ -f "$ROOT/.env" ]; then
  set -a
  # shellcheck disable=SC1091
  source "$ROOT/.env"
  set +a
fi

HOST="${SERVER_HOST:-85.255.151.186}"
SSH_PORT="${SERVER_SSH_PORT:-22333}"
SSH_USER="${SERVER_SSH_USER:-easytime}"
REPO_DIR="${SERVER_REPO_DIR:-/home/easytime/EasyTime}"

if [ -z "${SERVER_SSH_PASSWORD:-}" ]; then
  echo "Fehler: SERVER_SSH_PASSWORD fehlt in .env" >&2
  exit 1
fi

if ! command -v sshpass >/dev/null 2>&1; then
  echo "Fehler: sshpass fehlt (macOS: brew install hudochenkov/sshpass/sshpass)" >&2
  exit 1
fi

export SSHPASS="$SERVER_SSH_PASSWORD"

echo "Setze Demo-Daten auf dem Server zurück (${HOST}) …"

sshpass -e ssh \
  -o StrictHostKeyChecking=no \
  -p "$SSH_PORT" \
  "${SSH_USER}@${HOST}" \
  "cd '${REPO_DIR}' && sg docker -c 'docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T web php scripts/seed-mariadb.php'"

echo ""
echo "Server: Demo-Daten geladen (A001, A002 + M001–M010, Passwort: easytime)"
