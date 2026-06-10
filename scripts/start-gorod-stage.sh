#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

ENV_FILE="${GOROD_STAGE_ENV_FILE:-.env.gorod-stage}"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "${ENV_FILE} is missing. Copy .env.gorod-stage.example to ${ENV_FILE} first."
  exit 1
fi

set -a
source "${ENV_FILE}"
set +a

php artisan serve --host=127.0.0.1 --port=8010
