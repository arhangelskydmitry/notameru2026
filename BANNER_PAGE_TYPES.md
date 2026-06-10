# Настройка Отображения Баннеров на Разных Типах Страниц

**Дата:** 24 января 2026  
**Проблема:** Баннер `sidebar-top` показывался не на всех страницах. Требовалась возможность гибкой настройки отображения баннеров на разных типах страниц (главная, категории, статьи, прочие).

---

## 🎯 Что Было Добавлено

### Новые Настройки для Баннеров

Теперь каждый баннер имеет 4 чекбокса для управления отображением:

1. **Главная страница** (`show_on_home`) - показывать на `/`
2. **Страницы категорий** (`show_on_category`) - показывать на `/category/*`
3. **Страницы статей** (`show_on_post`) - показывать на страницах отдельных статей
4. **Остальные страницы** (`show_on_other`) - показывать на всех остальных страницах

По умолчанию все 4 чекбокса **включены** при создании нового баннера.

---

## 📦 Внесенные Изменения

### 1. Миграция БД

**Файл:** `database/migrations/2026_01_24_001500_add_page_types_to_banners.php`

Добавлены 4 новых поля типа `boolean` в таблицу `banners`:

```php
$table->boolean('show_on_home')->default(true);
$table->boolean('show_on_category')->default(true);
$table->boolean('show_on_post')->default(true);
$table->boolean('show_on_other')->default(true);
```

**Запустить миграцию:**
```bash
php artisan migrate
```

### 2. Модель Banner

**Файл:** `app/Models/Banner.php`

#### Добавлены в `$fillable`:
```php
'show_on_home',
'show_on_category',
'show_on_post',
'show_on_other',
```

#### Добавлены в `$casts`:
```php
'show_on_home' => 'boolean',
'show_on_category' => 'boolean',
'show_on_post' => 'boolean',
'show_on_other' => 'boolean',
```

#### Новый метод `canShowOnCurrentPage()`:
```php
/**
 * Проверка, можно ли показывать на текущем типе страницы
 */
public function canShowOnCurrentPage(): bool
{
    $routeName = request()->route() ? request()->route()->getName() : '';
    
    // Определяем тип страницы
    if ($routeName === 'home' || request()->is('/')) {
        return $this->show_on_home;
    }
    
    if ($routeName === 'category' || request()->is('category/*')) {
        return $this->show_on_category;
    }
    
    if ($routeName === 'post' || preg_match('#^[a-z0-9\-]+$#i', request()->path())) {
        return $this->show_on_post;
    }
    
    return $this->show_on_other;
}
```

#### Новый scope `forCurrentPage()`:
```php
/**
 * Scope: Для текущего типа страницы
 */
public function scopeForCurrentPage($query)
{
    $routeName = request()->route() ? request()->route()->getName() : '';
    
    if ($routeName === 'home' || request()->is('/')) {
        return $query->where('show_on_home', true);
    }
    
    if ($routeName === 'category' || request()->is('category/*')) {
        return $query->where('show_on_category', true);
    }
    
    if ($routeName === 'post' || preg_match('#^[a-z0-9\-]+$#i', request()->path())) {
        return $query->where('show_on_post', true);
    }
    
    return $query->where('show_on_other', true);
}
```

### 3. BannerHelper

**Файл:** `app/Helpers/BannerHelper.php`

Обновлен метод `show()` - теперь добавлен scope `forCurrentPage()`:

```php
public static function show(string $zone, bool $track = true): string
{
    // Получаем активные баннеры для зоны с учетом типа страницы
    $banners = Banner::active()
        ->inZone($zone)
        ->forCurrentPage() // ← Новая строка!
        ->byPriority()
        ->get();
    
    // ...
}
```

### 4. BannerController

**Файл:** `app/Http/Controllers/BannerController.php`

#### Метод `store()`:
Добавлено сохранение чекбоксов:
```php
$validated['show_on_home'] = $request->has('show_on_home');
$validated['show_on_category'] = $request->has('show_on_category');
$validated['show_on_post'] = $request->has('show_on_post');
$validated['show_on_other'] = $request->has('show_on_other');
```

#### Метод `update()`:
Аналогично для обновления.

### 5. Формы в Админке

**Файлы:** 
- `resources/views/admin/banners/create.blade.php`
- `resources/views/admin/banners/edit.blade.php`

Добавлен новый блок с чекбоксами после поля "Статус":

```html
<div class="mb-4">
    <label class="form-label">
        <i class="fas fa-sitemap me-1"></i>Отображать на страницах
    </label>
    <div class="card border-light bg-light">
        <div class="card-body">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="show_on_home"
                       name="show_on_home" value="1" checked>
                <label class="form-check-label" for="show_on_home">
                    <i class="fas fa-home me-1 text-primary"></i><strong>Главная страница</strong>
                </label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="show_on_category"
                       name="show_on_category" value="1" checked>
                <label class="form-check-label" for="show_on_category">
                    <i class="fas fa-folder me-1 text-warning"></i><strong>Страницы категорий</strong>
                </label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="show_on_post"
                       name="show_on_post" value="1" checked>
                <label class="form-check-label" for="show_on_post">
                    <i class="fas fa-file-alt me-1 text-success"></i><strong>Страницы статей</strong>
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="show_on_other"
                       name="show_on_other" value="1" checked>
                <label class="form-check-label" for="show_on_other">
                    <i class="fas fa-globe me-1 text-info"></i><strong>Остальные страницы</strong>
                </label>
            </div>
            <small class="text-muted mt-2 d-block">
                <i class="fas fa-info-circle me-1"></i>Выберите, на каких типах страниц будет отображаться баннер
            </small>
        </div>
    </div>
</div>
```

---

## 🚀 Как Использовать

### 1. Запустить Миграцию

На **локальном сервере:**
```bash
cd /Users/mac/SITES_NEW/notamerularavel
php artisan migrate
```

На **production сервере:**
```bash
cd /path/to/notamerularavel
php artisan migrate
```

### 2. Настроить Существующие Баннеры

После миграции все существующие баннеры будут иметь все 4 чекбокса включенными (значение по умолчанию `true`).

Чтобы настроить баннер:
1. Зайдите в админку: `/notaadmin/banners`
2. Нажмите "Редактировать" на нужном баннере
3. Прокрутите до раздела "Отображать на страницах"
4. Снимите галочки с тех типов страниц, где **НЕ нужно** показывать баннер
5. Сохраните изменения

### 3. Создать Новый Баннер с Настройками

1. Зайдите в `/notaadmin/banners`
2. Нажмите "Создать баннер"
3. Заполните все обязательные поля
4. В разделе "Отображать на страницах" выберите нужные типы страниц
5. Сохраните

---

## 📋 Примеры Использования

### Пример 1: Баннер Только на Главной

**Настройки:**
- ✅ Главная страница
- ❌ Страницы категорий
- ❌ Страницы статей
- ❌ Остальные страницы

**Результат:** Баннер будет показываться только на `https://notame.ru/`

### Пример 2: Баннер на Главной и Категориях

**Настройки:**
- ✅ Главная страница
- ✅ Страницы категорий
- ❌ Страницы статей
- ❌ Остальные страницы

**Результат:** Баннер будет показываться на главной и на всех страницах категорий типа `https://notame.ru/category/novosti`

### Пример 3: Баннер Только на Статьях

**Настройки:**
- ❌ Главная страница
- ❌ Страницы категорий
- ✅ Страницы статей
- ❌ Остальные страницы

**Результат:** Баннер будет показываться только на страницах статей типа `https://notame.ru/luchshie-albumy-2024`

### Пример 4: Универсальный Баннер (по умолчанию)

**Настройки:**
- ✅ Главная страница
- ✅ Страницы категорий
- ✅ Страницы статей
- ✅ Остальные страницы

**Результат:** Баннер будет показываться везде (как было раньше)

---

## 🔍 Как Это Работает

### Определение Типа Страницы

Система использует два способа определения:

1. **По имени маршрута:**
   ```php
   $routeName = request()->route()->getName();
   ```
   - `home` → Главная страница
   - `category` → Страница категории
   - `post` → Страница статьи

2. **По URL-паттерну:**
   ```php
   request()->is('/')          // Главная
   request()->is('category/*') // Категории
   preg_match('#^[a-z0-9\-]+$#i', request()->path()) // Статьи (slug)
   ```

### Фильтрация Баннеров

При вызове `@banner('sidebar-top')`:

1. Система получает название текущего маршрута
2. Определяет тип страницы (home, category, post, other)
3. Фильтрует баннеры с помощью scope `forCurrentPage()`
4. Возвращает только те баннеры, у которых соответствующий чекбокс включен

### Пример SQL-запроса

**На главной странице:**
```sql
SELECT * FROM banners 
WHERE zone = 'sidebar-top' 
  AND status = 'active'
  AND show_on_home = 1
  ...
```

**На странице категории:**
```sql
SELECT * FROM banners 
WHERE zone = 'sidebar-top' 
  AND status = 'active'
  AND show_on_category = 1
  ...
```

---

## 📦 Файлы для Загрузки на Сервер

### Список измененных файлов:

```
database/migrations/2026_01_24_001500_add_page_types_to_banners.php (НОВЫЙ)
app/Models/Banner.php
app/Helpers/BannerHelper.php
app/Http/Controllers/BannerController.php
resources/views/admin/banners/create.blade.php
resources/views/admin/banners/edit.blade.php
```

### Команды на сервере:

```bash
# 1. Загрузить файлы через FTP/SFTP

# 2. Запустить миграцию
php artisan migrate

# 3. Очистить кеш
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

---

## 🧪 Проверка После Установки

### Шаги Тестирования:

1. **Создайте тестовый баннер:**
   - Зайдите в `/notaadmin/banners`
   - Создайте баннер для зоны `sidebar-top`
   - Выберите только "Главная страница"
   - Сохраните

2. **Проверьте отображение:**
   - ✅ Откройте главную - баннер должен показываться
   - ❌ Откройте категорию - баннера не должно быть
   - ❌ Откройте статью - баннера не должно быть

3. **Измените настройки:**
   - Включите "Страницы статей"
   - Сохраните
   - ✅ Теперь баннер должен показываться на статьях

---

## ✅ Результат

После установки:
- ✅ Гибкая настройка отображения баннеров
- ✅ 4 независимых типа страниц
- ✅ Удобный интерфейс с иконками
- ✅ Значения по умолчанию для новых баннеров
- ✅ Обратная совместимость (существующие баннеры показываются везде)
- ✅ Автоматическая фильтрация при вызове `@banner()`

---

**Статус:** ✅ Завершено  
**Дата завершения:** 24 января 2026  
**Протестировано:** Локально  
**Готово к деплою:** Да (требуется запуск миграции на сервере)
