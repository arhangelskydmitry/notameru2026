#!/bin/bash

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 БЫСТРАЯ УСТАНОВКА NOTAME.RU НА СЕРВЕРЕ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Этот скрипт настроит Laravel приложение на сервере"
echo ""

# Проверка окружения
if [ "$APP_ENV" != "production" ]; then
    echo "⚠️  ВНИМАНИЕ: Этот скрипт только для production сервера!"
    echo ""
    read -p "Вы на production сервере? (yes/no): " confirm
    if [ "$confirm" != "yes" ]; then
        echo "❌ Установка отменена"
        exit 1
    fi
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 1: Настройка окружения"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ ! -f .env ]; then
    echo "📝 Копирование .env.production.example в .env"
    cp .env.production.example .env
    echo "✅ .env создан"
else
    echo "⚠️  .env уже существует, пропускаем"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 2: Установка зависимостей"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

composer install --no-dev --optimize-autoloader
php artisan key:generate

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 3: Миграция базы данных"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

read -p "Запустить миграцию БД? (yes/no): " run_migration
if [ "$run_migration" = "yes" ]; then
    echo "🔄 Создание таблиц..."
    php artisan migrate --force
    
    echo ""
    echo "🔄 Миграция данных из WordPress (это займет ~5-10 минут)..."
    php artisan migrate:wordpress
    
    echo ""
    echo "🔄 Миграция SEO данных..."
    php artisan migrate:seo
    
    echo "✅ Миграция завершена!"
else
    echo "⚠️  Миграция пропущена"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 4: Настройка прав доступа"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

chmod -R 755 storage bootstrap/cache public/imgnews
chown -R www-data:www-data storage bootstrap/cache public/imgnews 2>/dev/null || echo "⚠️  Не удалось изменить владельца (нужны права sudo)"

echo "✅ Права настроены"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 5: Оптимизация"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "✅ Кэш создан"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 6: Создание администратора"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

read -p "Создать администратора Moonshine? (yes/no): " create_admin
if [ "$create_admin" = "yes" ]; then
    php artisan moonshine:user
else
    echo "⚠️  Создание администратора пропущено"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅✅✅ УСТАНОВКА ЗАВЕРШЕНА!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🌐 Frontend: https://notame.pro"
echo "🔐 Админка: https://notame.pro/admin"
echo "📡 API: https://notame.pro/api/posts"
echo ""
echo "📊 Статистика:"
echo "   - Посты: 2,462"
echo "   - Категории: 134"
echo "   - Теги: 65"
echo "   - Изображения: 5,180+"
echo ""
echo "📝 Полная документация: DEPLOYMENT.md"
echo ""

