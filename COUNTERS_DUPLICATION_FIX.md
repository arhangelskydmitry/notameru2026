# 🔧 Исправление: Дублирование Счетчиков

## ❌ Проблема

После создания системы счетчиков возникла ошибка:

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'notameru.counters' doesn't exist
```

После исправления обнаружилось **дублирование** счетчика Яндекс Метрики.

---

## 🔍 Причины

### Проблема #1: Таблица не создана локально

**Причина:** Миграция не была выполнена на локальном сервере

**Решение:**
```bash
php artisan migrate --path=database/migrations/2026_01_24_150000_create_counters_table.php
```

### Проблема #2: Дублирование счетчика

**Причина:** В `sidebar.blade.php` (строка 179) использовался неправильный запрос:

```php
// НЕПРАВИЛЬНО - показывает ВСЕ активные счетчики
$counters = \App\Models\Counter::where('is_active', true)->orderBy('sort_order')->get();
```

Это приводило к тому что счетчик с позицией `footer` показывался и в sidebar тоже.

**Результат:**
- Счетчик показывался в сайдбаре ❌
- Счетчик показывался в футере ❌
- **Итого: 2 раза** (дублирование)

---

## ✅ Решение

### Исправление #1: Выполнена миграция

```bash
php artisan migrate --path=database/migrations/2026_01_24_150000_create_counters_table.php
```

**Результат:**
```
INFO  Running migrations.
2026_01_24_150000_create_counters_table ....................... 46.74ms DONE
```

### Исправление #2: Добавлен счетчик через Eloquent

```bash
php artisan tinker --execute="..."
```

**Результат:**
```
Counter created with ID: 1
```

### Исправление #3: Исправлен фильтр в sidebar.blade.php

**Файл:** `resources/views/partials/sidebar.blade.php` (строка 179)

**Было:**
```php
$counters = \App\Models\Counter::where('is_active', true)->orderBy('sort_order')->get();
```

**Стало:**
```php
$counters = \App\Models\Counter::getActiveForPosition('sidebar');
```

**Теперь:**
- Счетчики в sidebar показываются только если позиция = `sidebar`
- Счетчики в footer показываются только если позиция = `footer`
- Счетчики в header показываются только если позиция = `header`
- **Нет дублирования!** ✅

### Исправление #4: Исправлен SQL файл

**Файл:** `database/sql/insert_yandex_metrika_counter.sql`

**Проблема:** Двойные одинарные кавычки `''` в SQL

**Было:**
```sql
document,''script'',''https://mc.yandex.ru/metrika/tag.js'', ''ym''
```

**Стало:**
```sql
document,\'script\',\'https://mc.yandex.ru/metrika/tag.js\', \'ym\'
```

---

## 📦 Обновленные файлы

**Нужно загрузить на production:**

1. `resources/views/partials/sidebar.blade.php` ← **ИСПРАВЛЕН** (фильтр по позиции)
2. `database/sql/insert_yandex_metrika_counter.sql` ← **ИСПРАВЛЕН** (кавычки в SQL)

**Остальные файлы без изменений:**
- `resources/views/frontend/layout.blade.php`
- `app/Models/Counter.php`
- `app/Http/Controllers/CounterController.php`
- `routes/web.php`
- И т.д.

---

## 🧪 Проверка

### Локально (уже проверено):

```bash
curl -s http://localhost:8004 | grep -c "Yandex.Metrika counter"
# Результат: 2 (открывающий + закрывающий комментарии)
# Это ПРАВИЛЬНО - счетчик один раз!
```

### На production:

1. Загрузите обновленные файлы
2. Выполните миграцию (создайте таблицу)
3. Выполните SQL импорт (добавьте счетчик)
4. Откройте главную страницу
5. F12 → Console → не должно быть ошибок
6. F12 → Network → `mc.yandex.ru/metrika/tag.js` должна загрузиться **ОДИН РАЗ**
7. В исходном коде (Ctrl+U) найдите `ym(93779125` - должно быть **ОДИН РАЗ**

---

## 💡 Как работает фильтрация по позициям

### Модель Counter (getActiveForPosition)

```php
public static function getActiveForPosition(string $position = 'sidebar')
{
    return static::where('is_active', true)
        ->where('position', $position)  // ← Фильтр по позиции!
        ->orderBy('sort_order')
        ->get();
}
```

### Использование в шаблонах

**Sidebar (правая колонка):**
```php
$counters = \App\Models\Counter::getActiveForPosition('sidebar');
// Показывает только счетчики с position = 'sidebar'
```

**Footer (низ страницы):**
```php
$footerCounters = \App\Models\Counter::getActiveForPosition('footer');
// Показывает только счетчики с position = 'footer'
```

**Header (в <head>):**
```php
$headerCounters = \App\Models\Counter::getActiveForPosition('header');
// Показывает только счетчики с position = 'header'
```

---

## 📊 Итого

### ❌ Было:
- Ошибка: таблица не существует
- Счетчик дублировался (показывался 2 раза)
- Неправильный SQL (двойные кавычки)

### ✅ Стало:
- Таблица создана
- Счетчик показывается **ОДИН РАЗ** (только в footer)
- SQL исправлен
- Фильтр по позициям работает корректно

---

## 🆘 Если на production все еще дублируется

**Проверьте:**

1. Файл `sidebar.blade.php` обновлен (строка 179 использует `getActiveForPosition('sidebar')`)
2. В БД счетчик имеет `position = 'footer'` (не `sidebar`)
3. Кеш очищен (Ctrl+Shift+R)

**Если счетчик все еще в sidebar:**

Это означает что в БД позиция = `sidebar`. Измените через админку:
1. Админка → Счетчики
2. Редактировать "Яндекс Метрика"
3. Позиция: **Footer**
4. Сохранить

---

✅ **Проблема исправлена!** Счетчик теперь показывается только один раз в правильной позиции.

**Обновленные файлы:**
- `resources/views/partials/sidebar.blade.php` (фильтр по позиции)
- `database/sql/insert_yandex_metrika_counter.sql` (исправлены кавычки)
