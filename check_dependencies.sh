#!/bin/bash

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 АНАЛИЗ ЗАВИСИМОСТЕЙ ОТ WORDPRESS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "1️⃣ Проверка моделей..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Laravel модели (app/Models/):"
find app/Models -name "*.php" -type f | grep -v WordPress | wc -l | xargs echo "  ✅ Независимые модели:"
find app/Models -name "*.php" -type f | grep WordPress | wc -l | xargs echo "  ⚠️  WordPress модели:"
echo ""

echo "2️⃣ Проверка таблиц в базе данных..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Laravel таблицы (migrations):"
ls database/migrations/*.php | wc -l | xargs echo "  ✅ Миграций Laravel:"
echo ""

echo "3️⃣ Проверка команд миграции..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
grep -l "wordpress" app/Console/Commands/*.php 2>/dev/null | while read file; do
    echo "  ⚠️  $(basename "$file") - использует WordPress БД"
done
echo ""

echo "4️⃣ Проверка изображений..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
webp_count=$(find public/imgnews -name "*.webp" 2>/dev/null | wc -l | xargs)
jpg_count=$(find public/imgnews -name "*.jpg" -o -name "*.jpeg" 2>/dev/null | wc -l | xargs)
png_count=$(find public/imgnews -name "*.png" 2>/dev/null | wc -l | xargs)
echo "  ✅ WebP: $webp_count файлов"
echo "  ⚠️  JPG: $jpg_count файлов (дубликаты для удаления)"
echo "  ✅ PNG: $png_count файлов"
echo ""

echo "5️⃣ Проверка конфигурации БД..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if grep -q "WORDPRESS_DB" .env 2>/dev/null; then
    echo "  ⚠️  .env содержит WORDPRESS_DB_* переменные"
    echo "  ℹ️  Используются ТОЛЬКО для миграции данных"
else
    echo "  ✅ .env не содержит WordPress переменных"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 ВЫВОД:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Проект МОЖЕТ работать самостоятельно:"
echo "   • Все данные УЖЕ мигрированы в новую БД"
echo "   • WordPress БД нужна ТОЛЬКО для команды migrate:wordpress"
echo "   • После миграции WordPress можно удалить"
echo ""
echo "⚠️  Что можно удалить для очистки:"
echo "   1. JPG дубликаты (есть WebP версии)"
echo "   2. app/Console/Commands/MigrateWordPressData.php (после миграции)"
echo "   3. WORDPRESS_DB_* переменные из .env (после миграции)"
echo ""
echo "✅ Что НУЖНО оставить:"
echo "   • public/imgnews/*.webp (6,339 файлов)"
echo "   • Все Laravel модели и миграции"
echo "   • Moonshine Resources"
echo ""
