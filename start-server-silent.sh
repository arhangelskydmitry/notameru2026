#!/bin/bash

# Скрипт для запуска Laravel сервера без предупреждений "Broken pipe"

cd "$(dirname "$0")"

echo "🚀 Запуск Laravel сервера..."
echo "📍 URL: http://127.0.0.1:8001"
echo "⏹️  Остановка: Ctrl+C"
echo ""

# Запускаем сервер с кастомным php.ini
php -c php-server.ini artisan serve --host=127.0.0.1 --port=8001

