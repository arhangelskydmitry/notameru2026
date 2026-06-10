#!/bin/bash

# Скрипт создания архива версии 1.1
# Дата: 24 января 2026

echo "🚀 Создание архива НотаМиру CMS v1.1"
echo "======================================"

# Переменные
VERSION="1.1.0"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups_v1.1"
ARCHIVE_NAME="notamerularavel_v${VERSION}_${DATE}"

# Создаем папку для бекапов
mkdir -p "$BACKUP_DIR"

echo ""
echo "📦 Шаг 1: Создание дампа базы данных..."
# Дамп БД (только таблицы Laravel, без WordPress)
php artisan db:table-list --format=json | grep -E "(banners|settings|posts|categories|tags|users|roles|permissions|menu_items|activity_log)" > /dev/null
mysqldump -u root -proot -h 127.0.0.1 -P 8889 notameru \
    --tables \
    activity_log \
    author_statistics \
    banner_stats \
    banner_views \
    banner_zones \
    banners \
    cache \
    cache_locks \
    categories \
    failed_jobs \
    job_batches \
    jobs \
    menu_items \
    migrations \
    notifications \
    password_reset_tokens \
    permissions \
    personal_access_tokens \
    post_category \
    post_seo \
    post_tag \
    post_views \
    posts \
    role_permissions \
    roles \
    sessions \
    settings \
    site_statistics \
    site_visitors \
    tags \
    user_roles \
    users \
    > "$BACKUP_DIR/${ARCHIVE_NAME}_laravel_db.sql"

echo "✅ Дамп БД создан: ${ARCHIVE_NAME}_laravel_db.sql"

echo ""
echo "📦 Шаг 2: Архивирование кода приложения..."
# Создаем tar.gz архив кода (без vendor, node_modules, кеша)
tar -czf "$BACKUP_DIR/${ARCHIVE_NAME}_code.tar.gz" \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/backups/*' \
    --exclude='storage/exports/*' \
    --exclude='public/storage' \
    --exclude='.git' \
    --exclude='.DS_Store' \
    --exclude='*.log' \
    app/ \
    bootstrap/ \
    config/ \
    database/ \
    public/ \
    resources/ \
    routes/ \
    storage/ \
    .env.example \
    artisan \
    composer.json \
    composer.lock \
    package.json \
    README.md \
    ROADMAP_2026.md \
    VERSION_1.1_RELEASE.md \
    SECURITY_AUDIT_REPORT.md \
    BANNER_PAGE_TYPES.md \
    SERVER_SETUP.md \
    docs/

echo "✅ Архив кода создан: ${ARCHIVE_NAME}_code.tar.gz"

echo ""
echo "📦 Шаг 3: Создание полного архива (БД + Код)..."
cd "$BACKUP_DIR"
tar -czf "${ARCHIVE_NAME}_FULL.tar.gz" \
    "${ARCHIVE_NAME}_laravel_db.sql" \
    "${ARCHIVE_NAME}_code.tar.gz"

cd ..

echo "✅ Полный архив создан: ${ARCHIVE_NAME}_FULL.tar.gz"

echo ""
echo "📊 Информация об архивах:"
ls -lh "$BACKUP_DIR"/${ARCHIVE_NAME}* | awk '{print $9 " - " $5}'

echo ""
echo "✅ Готово! Архивы находятся в папке: $BACKUP_DIR/"
echo ""
echo "📁 Структура архива:"
echo "   • ${ARCHIVE_NAME}_laravel_db.sql - Дамп базы данных (только Laravel таблицы)"
echo "   • ${ARCHIVE_NAME}_code.tar.gz - Код приложения (без vendor/node_modules)"
echo "   • ${ARCHIVE_NAME}_FULL.tar.gz - Полный архив (БД + Код)"
echo ""
echo "🔄 Для восстановления:"
echo "   1. Распакуйте ${ARCHIVE_NAME}_code.tar.gz"
echo "   2. Запустите: composer install && npm install"
echo "   3. Импортируйте ${ARCHIVE_NAME}_laravel_db.sql в MySQL"
echo "   4. Скопируйте .env.example в .env и настройте"
echo "   5. Запустите: php artisan key:generate"
echo ""
