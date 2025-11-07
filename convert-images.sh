#!/bin/bash

# Скрипт для конвертации изображений в фоновом режиме

cd /Users/mac/Sites/notamerularavel

echo "🔄 Запуск конвертации изображений в WebP..."
echo "Это может занять несколько часов для 4178 изображений (~4GB)"
echo ""

# Запускаем конвертацию
nohup php artisan images:convert-to-webp > storage/logs/image-conversion.log 2>&1 &

PID=$!
echo "✅ Процесс запущен в фоне (PID: $PID)"
echo "📝 Логи: storage/logs/image-conversion.log"
echo ""
echo "Для отслеживания прогресса:"
echo "  tail -f storage/logs/image-conversion.log"
echo ""
echo "Для остановки процесса:"
echo "  kill $PID"




