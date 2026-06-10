# 📊 Система Управления Счетчиками Аналитики

## ✅ Реализовано

Создана полноценная система для управления счетчиками аналитики (Яндекс Метрика, Google Analytics и т.д.)

---

## 🎯 Что было сделано

### 1. Удален блок VK виджета
- ✅ Убран виджет "ПОДПИСЫВАЙТЕСЬ НА НАС ВКОНТАКТЕ"
- ✅ Удален JavaScript код инициализации VK API
- ✅ Очищены стили для VK виджета

### 2. Создана база данных для счетчиков
**Файл:** `database/migrations/2026_01_24_150000_create_counters_table.php`

**Структура таблицы `counters`:**
- `id` - ID счетчика
- `name` - Название (для админки)
- `code` - HTML код счетчика
- `sort_order` - Порядок сортировки
- `is_active` - Активен ли
- `position` - Позиция (sidebar/footer/header)
- `created_at`, `updated_at` - Метки времени

### 3. Создана модель Counter
**Файл:** `app/Models/Counter.php`

**Возможности:**
- Метод `getActiveForPosition()` - получение активных счетчиков для позиции
- Cast для `is_active` и `sort_order`
- Fillable поля для mass assignment

### 4. Создан контроллер CounterController
**Файл:** `app/Http/Controllers/CounterController.php`

**Методы:**
- `index()` - список счетчиков
- `create()` - форма создания
- `store()` - сохранение
- `edit()` - форма редактирования
- `update()` - обновление
- `destroy()` - удаление
- `toggleActive()` - быстрое вкл/выкл (AJAX)

### 5. Созданы маршруты
**Файл:** `routes/web.php`

**Группа маршрутов:**
```php
Route::middleware('superadmin')->prefix('counters')->name('admin.counters.')->group(function () {
    Route::get('/', [CounterController::class, 'index'])->name('index');
    Route::get('/create', [CounterController::class, 'create'])->name('create');
    Route::post('/', [CounterController::class, 'store'])->name('store');
    Route::get('/{counter}/edit', [CounterController::class, 'edit'])->name('edit');
    Route::put('/{counter}', [CounterController::class, 'update'])->name('update');
    Route::delete('/{counter}', [CounterController::class, 'destroy'])->name('destroy');
    Route::post('/{counter}/toggle', [CounterController::class, 'toggleActive'])->name('toggle');
});
```

**Доступ:** Только для суперадмина

### 6. Созданы views для админки
**Файлы:**
- `resources/views/admin/counters/index.blade.php` - список счетчиков
- `resources/views/admin/counters/create.blade.php` - форма создания
- `resources/views/admin/counters/edit.blade.php` - форма редактирования

**Возможности интерфейса:**
- Список всех счетчиков с информацией
- Быстрое вкл/выкл через toggle (AJAX)
- Редактирование и удаление
- Справка по использованию
- Примеры кодов (Яндекс Метрика, Google Analytics)

### 7. Обновлен sidebar
**Файл:** `resources/views/partials/sidebar.blade.php`

**Изменения:**
- ❌ Удален: VK виджет
- ✅ Добавлен: Блок счетчиков

**Код:**
```blade
@php
    $counters = \App\Models\Counter::where('is_active', true)->orderBy('sort_order')->get();
@endphp

@if($counters->isNotEmpty())
<div class="sidebar-widget counters-widget">
    <h3 class="widget-title">Статистика</h3>
    <div class="widget-content">
        @foreach($counters as $counter)
            <div class="counter-item" style="margin-bottom: 15px;">
                {!! $counter->code !!}
            </div>
        @endforeach
    </div>
</div>
@endif
```

### 8. Добавлен пункт меню
**Файл:** `resources/views/layouts/admin.blade.php`

**Пункт меню:**
```html
<li class="{{ request()->is('notaadmin/counters*') ? 'active' : '' }}">
    <a href="{{ route('admin.counters.index') }}">
        <i class="fas fa-chart-line"></i> Счетчики
    </a>
</li>
```

---

## 📦 Установка на Production

### Шаг 1: Загрузить файлы

**Backend:**
```
app/Models/Counter.php
app/Http/Controllers/CounterController.php
database/migrations/2026_01_24_150000_create_counters_table.php
```

**Views:**
```
resources/views/admin/counters/index.blade.php
resources/views/admin/counters/create.blade.php
resources/views/admin/counters/edit.blade.php
resources/views/partials/sidebar.blade.php
resources/views/layouts/admin.blade.php
```

**Routes:**
```
routes/web.php
```

### Шаг 2: Запустить миграцию

**Через SSH:**
```bash
php artisan migrate
```

**Без SSH (через phpMyAdmin):**
```sql
CREATE TABLE `counters` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Название счетчика (для админки)',
  `code` text NOT NULL COMMENT 'HTML код счетчика',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Порядок сортировки',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Активен ли счетчик',
  `position` varchar(255) NOT NULL DEFAULT 'sidebar' COMMENT 'Позиция: sidebar, footer, header',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `counters_is_active_sort_order_index` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🧪 Проверка

### После загрузки на production:

1. **Войдите в админку** как суперадмин
2. **Найдите пункт меню** "Счетчики" (иконка 📊)
3. **Нажмите "Добавить счетчик"**
4. **Заполните форму:**
   - Название: Яндекс Метрика
   - Позиция: Сайдбар
   - Порядок: 0
   - Вставьте код счетчика
   - Активен: ✓
5. **Сохраните**
6. **Откройте главную страницу** сайта
7. **Проверьте правый сайдбар** - должен появиться блок "Статистика"
8. **Откройте консоль браузера** (F12)
9. **Проверьте что счетчик загрузился** (должны быть запросы к метрике/аналитике)

---

## 💡 Как использовать

### Добавление Яндекс Метрики

1. Перейдите на https://metrika.yandex.ru
2. Выберите свой счетчик
3. **Настройки** → **Код счетчика**
4. Скопируйте весь код
5. В админке: **Счетчики** → **Добавить счетчик**
6. Название: "Яндекс Метрика"
7. Вставьте скопированный код
8. Позиция: Сайдбар (или Footer/Header)
9. Сохраните

### Добавление Google Analytics

1. Перейдите на https://analytics.google.com
2. **Администратор** → **Информация об отслеживании**
3. **Код отслеживания**
4. Скопируйте Global Site Tag (gtag.js)
5. В админке добавьте новый счетчик
6. Вставьте код
7. Сохраните

### Управление счетчиками

- **Включить/Выключить:** Переключатель справа (AJAX, без перезагрузки)
- **Редактировать:** Кнопка ✏️
- **Удалить:** Кнопка 🗑️
- **Порядок:** Меньшее число = выше в списке

---

## 🎨 Позиции счетчиков

### Sidebar (Сайдбар)
- Показывается в правой колонке
- Блок "Статистика"
- Подходит для видимых счетчиков (например, счетчик посетителей)

### Footer (Футер)
- Показывается внизу страницы
- Подходит для невидимых счетчиков (Яндекс Метрика, Google Analytics)

### Header (Хедер)
- Показывается вверху страницы
- Подходит для скриптов, которые нужно загрузить первыми

---

## 🔧 Технические детали

### Модель Counter

```php
// Получить активные счетчики для сайдбара
$counters = Counter::getActiveForPosition('sidebar');

// Получить все активные счетчики
$counters = Counter::where('is_active', true)->orderBy('sort_order')->get();
```

### Вывод в шаблоне

```blade
@php
    $counters = \App\Models\Counter::getActiveForPosition('sidebar');
@endphp

@foreach($counters as $counter)
    {!! $counter->code !!}
@endforeach
```

### AJAX Toggle

Счетчики можно быстро вкл/выкл без перезагрузки страницы. Используется AJAX запрос к `/notaadmin/counters/{id}/toggle`.

---

## 🆘 Если что-то не работает

### Счетчик не показывается на сайте

**Проверьте:**
1. Счетчик активен (переключатель включен)
2. Позиция указана правильно
3. Код счетчика вставлен полностью
4. Очистите кеш браузера (Ctrl+Shift+R)

### Ошибка при сохранении

**Возможные причины:**
1. Не заполнены обязательные поля (название, код, позиция)
2. Невалидный HTML код
3. Проблемы с правами доступа

### Счетчик не загружается

**Проверьте в консоли браузера (F12):**
1. Есть ли ошибки JavaScript
2. Загружаются ли скрипты Метрики/Analytics
3. Нет ли блокировки AdBlock

---

## ✅ Итого

✅ VK виджет удален  
✅ Система счетчиков создана  
✅ Админ-панель для управления  
✅ Поддержка multiple позиций  
✅ AJAX вкл/выкл  
✅ Полная документация  

**Доступ:** Только суперадмин  
**URL админки:** `/notaadmin/counters`  

---

**Файлы для загрузки на production - смотрите в `FILES_TO_UPLOAD_COUNTERS.md`**
