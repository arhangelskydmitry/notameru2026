#!/bin/bash

# Скрипт для запуска Laravel сервера с установкой всех переменных окружения

cd "$(dirname "$0")"

# Читаем .env и экспортируем переменные
if [ -f .env ]; then
    export $(cat .env | grep -v "^#" | xargs)
fi

echo "==================================="
echo "Laravel Server Starter"
echo "==================================="
echo "APP_KEY: ${APP_KEY:0:20}..."
echo "DB_DATABASE: $DB_DATABASE"
echo "==================================="
echo ""
echo "Starting server on http://127.0.0.1:8001"
echo "Press Ctrl+C to stop"
echo ""

php artisan serve --host=127.0.0.1 --port=8001






