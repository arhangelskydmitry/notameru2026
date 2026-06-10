# 📋 Быстрая Инструкция - Загрузка v1.1 на Production

## 🎯 Файлы для Загрузки на https://notame.ru

### Backend (7 файлов):
```
✅ app/Models/Banner.php
✅ app/Helpers/BannerHelper.php
✅ app/Http/Controllers/BannerController.php
✅ app/Http/Controllers/AdminPanelController.php
✅ database/migrations/2026_01_24_001500_add_page_types_to_banners.php
```

### Frontend (4 файла):
```
✅ resources/views/partials/sidebar.blade.php
✅ resources/views/frontend/post.blade.php
✅ resources/views/admin/banners/create.blade.php
✅ resources/views/admin/banners/edit.blade.php
✅ resources/views/admin/post-edit.blade.php
```

---

## 🔧 После Загрузки Файлов

### Шаг 1: Выполните SQL в phpMyAdmin

База: `iq210692_notamerurework`

```sql
ALTER TABLE `banners` 
ADD COLUMN `show_on_home` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`,
ADD COLUMN `show_on_category` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_on_home`,
ADD COLUMN `show_on_post` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_on_category`,
ADD COLUMN `show_on_other` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_on_post`;
```

### Шаг 2: Очистите Кеш

**Вариант A:** Через phpMyAdmin выполните:
```sql
DELETE FROM cache;
```

**Вариант B:** Через файл (если есть доступ к созданию PHP):
Создайте файл `public/c.php`:
```php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Artisan::call('cache:clear');
Artisan::call('view:clear');
Artisan::call('config:clear');

echo "✅ Кеш очищен!";
unlink(__FILE__); // Удаляем сам скрипт
```

Откройте: `https://notame.ru/c.php`

---

## ✅ Проверка

После загрузки проверьте:

1. **Главная:** `https://notame.ru/` - работает ✅
2. **Категория:** `https://notame.ru/category/novosti` - баннер есть ✅
3. **Статья:** любая статья - баннер есть ✅
4. **Админка баннеров:** `https://notame.ru/notaadmin/banners` - есть чекбоксы для типов страниц ✅
5. **Редактор:** FileManager вставляет изображения ✅

---

## 🆘 Если Что-то Сломалось

### Быстрый Откат:

У вас есть архив v1.1 в папке `backups_v1.1/`:
1. Скачайте архив на локальный компьютер
2. Сохраните в безопасном месте (облако, внешний диск)
3. При необходимости восстановите по инструкции `backups_v1.1/README_RESTORE.md`

---

**Дата:** 24 января 2026  
**Версия:** 1.1.0 Stable  
**Время загрузки:** ~10 минут
