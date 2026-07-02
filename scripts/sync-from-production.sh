#!/usr/bin/env bash
# Синхронизация web-кода с production-сервера в текущую рабочую копию.
# Использование: ./scripts/sync-from-production.sh
#
# Требует: sshpass, SSH-доступ notame@193.106.172.155
# Не трогает: clients/macos/, .env, vendor/, storage/logs

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAGING="${TMPDIR:-/tmp}/notame-prod-sync-$$"
REMOTE_PATH="/srv/domains/notame-preview.factorymedia.ru/current"

SSH_USER="${NOTAME_SSH_USER:-notame}"
SSH_HOST="${NOTAME_SSH_HOST:-193.106.172.155}"
SSH_PASS="${NOTAME_SSH_PASSWORD:-}"

if [[ -z "$SSH_PASS" && -f "$ROOT/docs/ACCESS.local.md" ]]; then
  SSH_PASS=$(grep -E '^\s*6o|NOTAME_SSH_PASSWORD|Пароль:' "$ROOT/docs/ACCESS.local.md" 2>/dev/null | head -1 | sed 's/.*: *//' || true)
fi

if [[ -z "$SSH_PASS" ]]; then
  echo "Задайте NOTAME_SSH_PASSWORD или заполните docs/ACCESS.local.md"
  exit 1
fi

mkdir -p "$STAGING"
trap 'rm -rf "$STAGING"' EXIT

echo "==> Скачивание с ${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}"
sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" \
  "cd ${REMOTE_PATH} && tar czf - \
    --exclude=.env --exclude=.env.* \
    --exclude=vendor --exclude=node_modules \
    --exclude=storage/logs --exclude=storage/framework/cache \
    --exclude=storage/framework/sessions --exclude=storage/framework/views \
    --exclude=storage/app --exclude=storage/backups \
    --exclude=public/imgnews --exclude=public/build --exclude=public/hot \
    --exclude=.git \
    ." | tar xzf - -C "$STAGING"

echo "==> Копирование в $ROOT (clients/macos не затрагивается)"
if command -v rsync >/dev/null 2>&1; then
  rsync -a "$STAGING/" "$ROOT/" --exclude 'clients/macos'
else
  (cd "$STAGING" && tar cf - .) | (cd "$ROOT" && tar xf -)
fi

# Удаляем монолитный контроллер, если на сервере уже Admin/*
if [[ -d "$ROOT/app/Http/Controllers/Admin" && -f "$ROOT/app/Http/Controllers/AdminPanelController.php" ]]; then
  rm -f "$ROOT/app/Http/Controllers/AdminPanelController.php"
  echo "==> Удалён устаревший AdminPanelController.php"
fi

echo "==> Готово. Проверьте: git status"
echo "    Затем: git add -A && git commit -m 'sync: production snapshot'"
