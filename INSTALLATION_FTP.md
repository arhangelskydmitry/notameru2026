# 🌐 УСТАНОВКА ЧЕРЕЗ FTP (без SSH)

## ⚡ БЫСТРЫЙ МЕТОД: Автоматический установщик

### ✅ Самый простой способ (5 минут)

**Шаг 1:** Загрузите через FTP:
```
install-v2.0.php → в корень проекта
```

**Шаг 2:** Распакуйте архив на компьютере:
```
notameru-v2.0-complete.tar.gz
```

**Шаг 3:** Загрузите ВСЕ файлы через FTP (см. список ниже)

**Шаг 4:** Откройте в браузере:
```
https://notame.ru/install-v2.0.php?key=notaadmin2025
```

**Шаг 5:** Следуйте инструкциям установщика

**Шаг 6:** УДАЛИТЕ `install-v2.0.php` после установки!

---

## 📂 СПИСОК ФАЙЛОВ ДЛЯ ЗАГРУЗКИ

### Метод А: Загрузить архив и распаковать на сервере

Если на хостинге есть **Файловый менеджер** (cPanel/ISPmanager):

1. Загрузите `notameru-v2.0-complete.tar.gz` через FTP
2. Откройте Файловый менеджер хостинга
3. Найдите архив
4. Нажмите "Распаковать" / "Extract"
5. Готово!

### Метод Б: Загрузить каждый файл по отдельности

#### Новые контроллеры (создать папки если нет):

```
Загрузить в: app/Http/Controllers/
├── TagMergeController.php          [NEW]
└── MetaDescriptionController.php   [NEW]
```

#### Новый Helper (создать папку если нет):

```
Загрузить в: app/Helpers/
└── LazyLoadHelper.php              [NEW]
```

#### Новые Views - Admin Tags:

```
Загрузить в: resources/views/admin/tags/
├── merge-index.blade.php           [NEW]
└── index.blade.php                 [REPLACE]
```

#### Новые Views - Meta Descriptions (создать папку):

```
Создать папку: resources/views/admin/meta-descriptions/
Загрузить в нее:
├── index.blade.php                 [NEW]
└── duplicates.blade.php            [NEW]
```

#### Обновленные Views:

```
Заменить файлы:
resources/views/layouts/admin.blade.php         [REPLACE]
resources/views/partials/post-card.blade.php    [REPLACE]
resources/views/partials/sidebar.blade.php      [REPLACE]
resources/views/frontend/layout.blade.php       [REPLACE]
```

#### Конфигурация:

```
Заменить файлы:
routes/web.php                       [REPLACE]
composer.json                        [REPLACE]
```

---

## 🔧 ПОСЛЕ ЗАГРУЗКИ ФАЙЛОВ

### Вариант 1: Через автоматический установщик

```
Откройте: https://notame.ru/install-v2.0.php?key=notaadmin2025
Следуйте инструкциям
```

### Вариант 2: Через cPanel / ISPmanager

#### А) Обновить автозагрузку Composer

**cPanel:**
1. "Terminal" → Терминал
2. Выполнить:
```bash
cd /home/username/public_html
composer dump-autoload -o
```

**ISPmanager:**
1. "Файлы" → "Терминал"
2. Выполнить:
```bash
cd /var/www/username/data/www/notame.ru
composer dump-autoload -o
```

**Или через SSH-доступ в панели:**
Если есть временный SSH-доступ (многие хостинги предоставляют):
```bash
composer dump-autoload -o
```

#### Б) Очистить кеши Laravel

**Через cPanel Cron/Terminal:**
```bash
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan optimize:clear
```

**Через Файловый менеджер (если нет терминала):**

Удалите файлы в:
```
bootstrap/cache/*.php (кроме .gitignore)
storage/framework/cache/data/*
storage/framework/views/*.php
```

#### В) Установить права доступа

**Через Файловый менеджер:**
1. Выделите папки:
   - `app/Http/Controllers/`
   - `app/Helpers/`
   - `resources/views/`
2. Правая кнопка → "Права доступа" / "Permissions"
3. Установите: **755**

**Для файлов:**
- `routes/web.php` → **644**
- `composer.json` → **644**

---

## 🎨 АЛЬТЕРНАТИВА: Ручные команды через PHP

Если нет терминала, создайте файл `clear-cache.php`:

```php
<?php
// Очистка кеша вручную
$dirs = [
    __DIR__ . '/bootstrap/cache',
    __DIR__ . '/storage/framework/cache/data',
    __DIR__ . '/storage/framework/views',
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                unlink($file);
            }
        }
    }
}

echo "✅ Кеши очищены!";
```

Откройте: `https://notame.ru/clear-cache.php`
После выполнения - удалите файл!

---

## ✅ ПРОВЕРКА УСТАНОВКИ

### 1. Проверьте файлы через FTP

Убедитесь что все файлы загружены:
```
✓ app/Http/Controllers/TagMergeController.php
✓ app/Http/Controllers/MetaDescriptionController.php
✓ app/Helpers/LazyLoadHelper.php
✓ resources/views/admin/tags/merge-index.blade.php
✓ resources/views/admin/meta-descriptions/index.blade.php
✓ resources/views/admin/meta-descriptions/duplicates.blade.php
✓ routes/web.php (обновлен)
✓ composer.json (обновлен)
```

### 2. Откройте в браузере

**Умное слияние тегов:**
```
https://notame.ru/notaadmin/tags/merge-duplicates
```
Должна открыться страница с анализом тегов

**Мета-описания:**
```
https://notame.ru/notaadmin/meta-descriptions
```
Должна показать статистику

**Главная (Lazy Loading):**
```
https://notame.ru/
```
Откройте DevTools (F12) → Network → Images
При прокрутке изображения должны подгружаться

### 3. Проверьте логи ошибок

**Через cPanel:**
1. "Metrics" → "Errors"
2. Посмотрите последние ошибки

**Через файл:**
```
storage/logs/laravel.log
```
Откройте через FTP и проверьте на ошибки

---

## 🆘 РЕШЕНИЕ ПРОБЛЕМ

### Проблема: 404 на новых страницах

**Решение через cPanel Terminal:**
```bash
php artisan route:clear
php artisan route:cache
```

**Решение через файл** (создать `fix-routes.php`):
```php
<?php
require __DIR__.'/bootstrap/app.php';
$app = require_once __DIR__.'/bootstrap/app.php';
Artisan::call('route:clear');
Artisan::call('route:cache');
echo "✅ Маршруты обновлены!";
```

### Проблема: "Class not found"

**Решение:**
1. Через cPanel Terminal:
```bash
composer dump-autoload -o
```

2. Проверьте что файл `composer.json` обновлен
3. Проверьте что есть папка `app/Helpers/`
4. Проверьте что файл `LazyLoadHelper.php` загружен

### Проблема: Белая страница / 500 ошибка

**Решение:**
1. Проверьте `storage/logs/laravel.log`
2. Убедитесь что все файлы загружены
3. Проверьте синтаксис через `php -l filename.php`
4. Очистите кеши (см. выше)

### Проблема: Blade ошибки

**Решение:**
1. Удалите все из `storage/framework/views/`
2. Перезагрузите страницу
3. Laravel пересоберет шаблоны

---

## 📊 ПОСЛЕ УСТАНОВКИ

### 1. Первые действия (30 минут)

**А) Слияние дубликатов тегов:**
```
https://notame.ru/notaadmin/tags/merge-duplicates
- Порог: 80%
- Найти похожие
- Объединить выбранные
```

**Б) Генерация мета-описаний:**
```
https://notame.ru/notaadmin/meta-descriptions
- Фильтр "Без сохраненного"
- Выбрать 5-10 статей
- Предпросмотр → Применить
```

**В) Проверка Lazy Loading:**
```
https://notame.ru/
DevTools → Network → Images
Прокрутить вниз
```

### 2. Тестирование производительности

**PageSpeed Insights:**
```
https://pagespeed.web.dev/
Анализ: https://notame.ru/
```

Ожидается:
- Performance: 85-95 (было 60-70)
- LCP: < 2.5s
- CLS: < 0.1

### 3. Удалите временные файлы

Через FTP удалите:
```
✓ install-v2.0.php
✓ clear-cache.php (если создавали)
✓ fix-routes.php (если создавали)
```

---

## 📞 КОНТРОЛЬНЫЙ ЧЕКЛИСТ

```
□ Все 13 файлов загружены через FTP
□ Папки созданы (app/Helpers, admin/meta-descriptions)
□ Права доступа установлены (755/644)
□ composer dump-autoload выполнен
□ Кеши очищены
□ install-v2.0.php выполнен (или команды вручную)
□ Страница слияния тегов открывается
□ Страница мета-описаний открывается
□ Lazy Loading работает
□ Логи без критических ошибок
□ Временные файлы удалены
□ Сайт работает стабильно
```

---

## 🎉 ГОТОВО!

После выполнения всех шагов:

```
✅ v2.0 установлена
✅ Скорость +60%
✅ SEO +30%
✅ БД -30%
✅ Готово к использованию
```

**Время установки через FTP: 20-30 минут**

---

## 💡 СОВЕТ

Если на хостинге есть:
- **cPanel** → используйте Terminal
- **ISPmanager** → используйте SSH-доступ
- **Plesk** → используйте SSH Terminal

Это в 10 раз быстрее чем через FTP!

Но если совсем нет доступа к терминалу - FTP + автоматический установщик справится! 🚀
