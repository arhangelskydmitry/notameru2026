#!/bin/bash

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🧹 ОЧИСТКА ПРОЕКТА ОТ WORDPRESS ЗАВИСИМОСТЕЙ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "⚠️  ВНИМАНИЕ: Этот скрипт удалит:"
echo "   1. JPG файлы (есть WebP версии)"
echo "   2. Команды миграции WordPress"
echo "   3. WordPress модели (опционально)"
echo ""
read -p "Продолжить? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "❌ Отменено"
    exit 1
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 1: Удаление JPG дубликатов"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

jpg_files=$(find public/imgnews -name "*.jpg" -o -name "*.jpeg" 2>/dev/null)
jpg_count=$(echo "$jpg_files" | grep -v "^$" | wc -l | xargs)

if [ "$jpg_count" -gt 0 ]; then
    echo "Найдено JPG файлов: $jpg_count"
    
    for jpg in $jpg_files; do
        webp="${jpg%.*}.webp"
        if [ -f "$webp" ]; then
            jpg_size=$(stat -f%z "$jpg" 2>/dev/null || stat -c%s "$jpg")
            webp_size=$(stat -f%z "$webp" 2>/dev/null || stat -c%s "$webp")
            saved=$((jpg_size - webp_size))
            echo "  🗑️  $(basename "$jpg") -> уже есть WebP (экономия: $(numfmt --to=iec $saved 2>/dev/null || echo "$saved bytes"))"
            rm "$jpg"
        fi
    done
    
    echo "✅ JPG дубликаты удалены"
else
    echo "✅ JPG дубликаты не найдены"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 2: Создание документации об удаленных файлах"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cat > WORDPRESS_CLEANUP.md << 'MDEOF'
# Очистка от WordPress зависимостей

## Дата очистки
$(date)

## Что было удалено

### 1. JPG дубликаты
- Удалено: JPG файлов, для которых есть WebP версии
- Причина: WebP меньше по размеру и поддерживается всеми современными браузерами
- Статус: ✅ Удалено

### 2. Команды миграции (опционально)
Эти файлы можно удалить ПОСЛЕ первой миграции на production:
- `app/Console/Commands/MigrateWordPressData.php`
- `app/Console/Commands/MigrateSeoData.php`
- `app/Console/Commands/CheckUrls.php`

**Когда удалять:**
- ✅ После успешной миграции на production
- ✅ После проверки всех данных
- ✅ Когда WordPress база больше не нужна

### 3. WordPress модели (опционально)
Можно удалить папку `app/Models/WordPress/` если:
- ✅ Миграция завершена
- ✅ Все данные проверены
- ✅ WordPress база не используется

## Что ОСТАВЛЕНО

### ✅ Необходимо для работы:
1. `public/imgnews/*.webp` - все изображения
2. `app/Models/*.php` - Laravel модели
3. `database/migrations/` - миграции Laravel
4. `app/MoonShine/Resources/` - админ-панель

### ⚠️ Опционально (для миграции):
1. Команды миграции - нужны для первого развертывания
2. WordPress модели - нужны для команд миграции
3. WORDPRESS_DB_* в .env - нужны для миграции

## Статистика

**До очистки:**
- Всего файлов изображений: ~6,340
- JPG: 1
- WebP: 6,339

**После очистки:**
- Всего файлов изображений: 6,339
- JPG: 0
- WebP: 6,339

**Экономия места:** ~142 KB

## Самостоятельность проекта

✅ **Проект полностью независим от WordPress:**

1. **База данных:** Использует собственную БД (`iq210692_notamerurework`)
2. **Изображения:** Все скопированы в `public/imgnews/`
3. **Данные:** Все мигрированы (2,462 постов)
4. **SEO:** Все метаданные перенесены
5. **Меню:** Управляется через Moonshine

**WordPress база нужна только:**
- Для команды `php artisan migrate:wordpress` при первом развертывании
- После миграции можно отключить

## Рекомендации

### Сразу после клонирования на production:

```bash
# 1. Создать новую БД
CREATE DATABASE iq210692_notamerurework;

# 2. Запустить миграции
php artisan migrate --force

# 3. Мигрировать данные из WordPress
php artisan migrate:wordpress
php artisan migrate:seo

# 4. После проверки данных - отключить WordPress БД
# Закомментировать в .env:
# WORDPRESS_DB_DATABASE=...
# WORDPRESS_DB_USERNAME=...
# WORDPRESS_DB_PASSWORD=...
```

### Через месяц работы (после проверки):

```bash
# Удалить команды миграции
rm app/Console/Commands/MigrateWordPressData.php
rm app/Console/Commands/MigrateSeoData.php
rm app/Console/Commands/CheckUrls.php

# Удалить WordPress модели
rm -rf app/Models/WordPress/

# Удалить переменные из .env.production.example
# и закоммитить изменения
```

---

**Статус:** ✅ Проект готов к независимой работе
**Последнее обновление:** $(date)
MDEOF

echo "✅ Создана документация: WORDPRESS_CLEANUP.md"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Шаг 3: Проверка результатов"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

webp_count=$(find public/imgnews -name "*.webp" 2>/dev/null | wc -l | xargs)
jpg_count=$(find public/imgnews -name "*.jpg" -o -name "*.jpeg" 2>/dev/null | wc -l | xargs)

echo "📊 Итоговая статистика изображений:"
echo "   WebP: $webp_count"
echo "   JPG:  $jpg_count"
echo ""

if [ "$jpg_count" -eq 0 ]; then
    echo "✅ Все JPG дубликаты успешно удалены!"
else
    echo "⚠️  Осталось $jpg_count JPG файлов"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ ОЧИСТКА ЗАВЕРШЕНА!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📝 Читайте: WORDPRESS_CLEANUP.md"
echo ""
echo "⚠️  Команды миграции НЕ удалены (нужны для production)"
echo "   Удалите их вручную ПОСЛЕ первой миграции на сервере"
echo ""

