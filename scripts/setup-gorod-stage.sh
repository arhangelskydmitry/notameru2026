#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

ENV_FILE="${GOROD_STAGE_ENV_FILE:-.env.gorod-stage}"
ENV_NAME="${ENV_FILE#.env.}"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "${ENV_FILE} is missing. Copy .env.gorod-stage.example to ${ENV_FILE} and adjust values first."
  exit 1
fi

set -a
source "${ENV_FILE}"
set +a

mysql --protocol=TCP -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan key:generate --env="${ENV_NAME}" --force
php artisan migrate --env="${ENV_NAME}" --force
php artisan gorod:link-media --env="${ENV_NAME}" "${GOROD_UPLOADS_PATH:-/Users/mac/SITES_NEW/gorod-magazine.ru/snapshot/files/wp-content/uploads}"
php artisan gorod:bootstrap-stage-admin --env="${ENV_NAME}" --login="${GOROD_STAGE_ADMIN_LOGIN:-kira}" --password="${GOROD_STAGE_ADMIN_PASSWORD:-LocalPass!2026}"

echo "Gorod stage setup complete."
echo "Open: ${APP_URL}/notaadmin"
