#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CERT_DIR="${ROOT}/nginx/certs"
mkdir -p "$CERT_DIR"

if [ -f "${CERT_DIR}/selfsigned.crt" ] && [ -f "${CERT_DIR}/selfsigned.key" ]; then
  echo "Self-signed certificate already exists in nginx/certs/"
  exit 0
fi

openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
  -keyout "${CERT_DIR}/selfsigned.key" \
  -out "${CERT_DIR}/selfsigned.crt" \
  -subj "/CN=EasyTime/O=EasyTime/C=AT"

echo "Created self-signed certificate in nginx/certs/"
