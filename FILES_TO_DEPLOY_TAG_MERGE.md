# 📦 ФАЙЛЫ ДЛЯ ЗАГРУЗКИ - Умное слияние тегов

## 🆕 НОВЫЕ ФАЙЛЫ (создать на сервере)

### 1. Контроллер
```
app/Http/Controllers/TagMergeController.php
```
**Размер:** ~15 KB  
**Описание:** Основная логика поиска и слияния похожих тегов

### 2. View (Blade шаблон)
```
resources/views/admin/tags/merge-index.blade.php
```
**Размер:** ~12 KB  
**Описание:** Интерфейс для управления слиянием тегов

### 3. Документация
```
TAG_MERGE_GUIDE.md
INSTALLATION_TAG_MERGE.md
```
**Описание:** Руководства пользователя и установки

---

## 📝 ОБНОВЛЕННЫЕ ФАЙЛЫ (заменить на сервере)

### 1. Маршруты
```
routes/web.php
```
**Изменения:** Добавлены 4 новых маршрута в группу `admin.tags.*`
```php
Строки 167-170:
Route::get('/merge-duplicates', ...)->name('merge-index');
Route::post('/merge-preview', ...)->name('merge-preview');
Route::post('/merge-execute', ...)->name('merge-execute');
Route::post('/merge-bulk', ...)->name('merge-bulk');
```

### 2. Меню админки
```
resources/views/admin/tags/index.blade.php
```
**Изменения:** Добавлена кнопка "Умное слияние"
```html
Строки 73-76:
<a href="{{ route('admin.tags.merge-index') }}" class="btn btn-danger">
    <i class="fas fa-code-branch"></i> Умное слияние
</a>
```

---

## 📋 БЫСТРАЯ ЗАГРУЗКА

### Вариант 1: Через архив

```bash
# На локальной машине
cd /Users/mac/SITES_NEW/notamerularavel

# Создать архив
tar -czf tag-merge-v1.0.tar.gz \
  app/Http/Controllers/TagMergeController.php \
  resources/views/admin/tags/merge-index.blade.php \
  resources/views/admin/tags/index.blade.php \
  routes/web.php \
  TAG_MERGE_GUIDE.md \
  INSTALLATION_TAG_MERGE.md

# Загрузить на сервер
scp tag-merge-v1.0.tar.gz user@notame.ru:/home/user/

# На сервере
cd /path/to/notamerularavel
tar -xzf ~/tag-merge-v1.0.tar.gz
```

### Вариант 2: По отдельности (SFTP/FileZilla)

**Загрузить в таком порядке:**

1. **Новые файлы:**
   - `app/Http/Controllers/TagMergeController.php`
   - `resources/views/admin/tags/merge-index.blade.php`
   - `TAG_MERGE_GUIDE.md`
   - `INSTALLATION_TAG_MERGE.md`

2. **Обновленные файлы (создать бэкап!):**
   - `routes/web.php` → `routes/web.php.backup`
   - `resources/views/admin/tags/index.blade.php` → `.backup`

3. **Загрузить новые версии:**
   - `routes/web.php`
   - `resources/views/admin/tags/index.blade.php`

---

## ⚡ КОМАНДЫ ПОСЛЕ ЗАГРУЗКИ

```bash
# 1. Очистить все кеши
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear

# 2. Обновить автозагрузку
composer dump-autoload

# 3. Проверить маршруты
php artisan route:list | grep merge

# 4. Установить права
chmod 644 app/Http/Controllers/TagMergeController.php
chmod 644 resources/views/admin/tags/merge-index.blade.php
chmod 644 resources/views/admin/tags/index.blade.php
chmod 644 routes/web.php
```

---

## ✅ ПРОВЕРКА УСТАНОВКИ

### 1. Маршруты зарегистрированы
```bash
php artisan route:list | grep merge

# Должно показать 6 маршрутов:
# ✓ admin.tags.merge (старый - ручное слияние)
# ✓ admin.tags.merge.execute (старый)
# ✓ admin.tags.merge-index (новый)
# ✓ admin.tags.merge-preview (новый)
# ✓ admin.tags.merge-execute (новый)
# ✓ admin.tags.merge-bulk (новый)
```

### 2. Контроллер доступен
```bash
php -l app/Http/Controllers/TagMergeController.php

# Должно показать:
# No syntax errors detected in app/Http/Controllers/TagMergeController.php
```

### 3. View компилируется
```bash
# Очистить скомпилированные views
php artisan view:clear

# Открыть страницу в браузере
# https://notame.ru/notaadmin/tags/merge-duplicates
```

### 4. Кнопка появилась
```bash
# Проверить что кнопка добавлена
grep "merge-index" resources/views/admin/tags/index.blade.php

# Должно показать:
# <a href="{{ route('admin.tags.merge-index') }}" ...
```

---

## 🎯 ЧТО ДЕЛАТЬ ДАЛЬШЕ

### Сразу после установки:

1. **Войти в админку:**
   ```
   https://notame.ru/notaadmin/login
   ```

2. **Перейти в "Теги":**
   ```
   https://notame.ru/notaadmin/tags
   ```

3. **Нажать "Умное слияние"** (красная кнопка)

4. **Запустить анализ:**
   - Порог: 80%
   - Минимум статей: 0
   - Нажать "Найти похожие"

5. **Протестировать на малой группе:**
   - Выбрать группу с 2-3 тегами
   - Нажать "Предпросмотр"
   - Проверить результат
   - Выполнить слияние

### Регулярное использование:

- **Раз в месяц:** Проверять дубликаты (порог 80%)
- **После импорта:** Очищать новые теги (порог 85%)
- **Перед SEO:** Массовая очистка (порог 75%)

---

## 📊 СТАТИСТИКА ФАЙЛОВ

```
Новые файлы:        2
Обновленные файлы:  2
Документация:       2
─────────────────────
Всего файлов:       6

Строк кода:         ~650
JavaScript:         ~300 строк
PHP:                ~350 строк
```

---

## 🔒 БЕЗОПАСНОСТЬ

### Права доступа:
```
✓ Только editor и superadmin
✓ Middleware: 'editor'
✓ CSRF защита
✓ Транзакции БД
✓ Логирование всех действий
```

### Бэкапы перед использованием:
```bash
# Бэкап всей БД
mysqldump -u user -p notameru_db > backup_before_merge.sql

# Или только таблиц тегов
mysqldump -u user -p notameru_db \
  wp_terms \
  wp_term_taxonomy \
  wp_term_relationships \
  > tags_backup.sql
```

---

## 🎉 СТАТУС

```
✅ Разработка завершена
✅ Локальное тестирование пройдено
✅ Документация создана
✅ Файлы готовы к загрузке
⏳ Ожидает установки на production
```

---

## 📞 ПОДДЕРЖКА

**Проблемы при установке?**

1. Проверьте логи:
   ```bash
   tail -50 storage/logs/laravel.log
   ```

2. Проверьте кеш:
   ```bash
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. Проверьте права:
   ```bash
   ls -la app/Http/Controllers/TagMergeController.php
   ```

---

**Готово к установке!** 🚀

Следующий этап: **Автоматические мета-описания** (6 часов)
