#!/usr/bin/env bash
# Einmalig auf dem Server ausführen (als User easytime):
#   bash scripts/setup-server-deploy-key.sh
#
# Danach den angezeigten Public Key in GitHub eintragen:
#   Repo → Settings → Deploy keys → Add deploy key

set -euo pipefail

KEY_PATH="${HOME}/.ssh/github_easytime_deploy"
SSH_CONFIG="${HOME}/.ssh/config"

mkdir -p "${HOME}/.ssh"
chmod 700 "${HOME}/.ssh"

if [ ! -f "$KEY_PATH" ]; then
  ssh-keygen -t ed25519 -f "$KEY_PATH" -N "" -C "easytime-server-deploy"
  echo "Neuer Deploy Key erzeugt: $KEY_PATH"
else
  echo "Deploy Key existiert bereits: $KEY_PATH"
fi

chmod 600 "$KEY_PATH"
chmod 644 "${KEY_PATH}.pub"

if ! grep -q 'Host github.com' "$SSH_CONFIG" 2>/dev/null; then
  cat >> "$SSH_CONFIG" <<'EOF'

Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/github_easytime_deploy
  IdentitiesOnly yes
EOF
  chmod 600 "$SSH_CONFIG"
  echo "SSH-Config für github.com ergänzt."
fi

REPO_DIR="${HOME}/EasyTime"
if [ -d "$REPO_DIR/.git" ]; then
  git -C "$REPO_DIR" remote set-url origin git@github.com:EasyTIME-by-SR-BP/EasyTime.git
  echo "Git-Remote auf SSH umgestellt."
fi

echo ""
echo "================================================================"
echo "Diesen Public Key in GitHub eintragen (Deploy keys, Read-only):"
echo "https://github.com/EasyTIME-by-SR-BP/EasyTime/settings/keys"
echo "================================================================"
echo ""
cat "${KEY_PATH}.pub"
echo ""
echo "Nach dem Eintragen testen:"
echo "  ssh -T git@github.com"
echo "  cd ~/EasyTime && git fetch origin main"
