#!/usr/bin/env bash
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
LOCAL_PORT="${SERVER_TUNNEL_LOCAL_PORT:-9080}"
REMOTE_HOST="${SERVER_TUNNEL_REMOTE_HOST:-127.0.0.1}"
REMOTE_PORT="${SERVER_TUNNEL_REMOTE_PORT:-80}"

if [ -z "${SERVER_SSH_PASSWORD:-}" ]; then
  echo "Fehler: SERVER_SSH_PASSWORD fehlt in .env (siehe .env.example)." >&2
  exit 1
fi

if ! command -v sshpass >/dev/null 2>&1; then
  echo "Fehler: sshpass ist nicht installiert." >&2
  echo "macOS: brew install hudochenkov/sshpass/sshpass" >&2
  exit 1
fi

export SSHPASS="$SERVER_SSH_PASSWORD"

echo "SSH-Tunnel aktiv → http://localhost:${LOCAL_PORT}"
echo "Beenden mit Strg+C"
exec sshpass -e ssh \
  -N -T \
  -o StrictHostKeyChecking=no \
  -o ServerAliveInterval=30 \
  -p "$SSH_PORT" \
  -L "${LOCAL_PORT}:${REMOTE_HOST}:${REMOTE_PORT}" \
  "${SSH_USER}@${HOST}"
