# 📦 ОБНОВЛЕНО: Файлы для Загрузки + Миграция Яндекс Метрики

## 🎯 Модуль: Система Счетчиков + Миграция

**Дата:** 2026-01-24  
**Версия:** v2.0 (Counters Module + Migration)  
**Приоритет:** 🟢 Новая функциональность + Миграция данных

---

## 📁 Список файлов для загрузки (10 файлов)

### Backend (5 файлов)

#### 1. app/Models/Counter.php
**Новый файл** - модель для работы со счетчиками
```
/var/www/iq210692/data/www/notame.ru/app/Models/Counter.php
```

#### 2. app/Http/Controllers/CounterController.php
**Новый файл** - контроллер для управления счетчиками
```
/var/www/iq210692/data/www/notame.ru/app/Http/Controllers/CounterController.php
```

#### 3. database/migrations/2026_01_24_150000_create_counters_table.php
**Новый файл** - миграция для создания таблицы
```
/var/www/iq210692/data/www/notame.ru/database/migrations/2026_01_24_150000_create_counters_table.php
```

#### 4. database/sql/insert_yandex_metrika_counter.sql
**Новый файл** - SQL для импорта существующей Яндекс Метрики
```
/var/www/iq210692/data/www/notame.ru/database/sql/insert_yandex_metrika_counter.sql
```

#### 5. routes/web.php
**Обновлен** - добавлены маршруты для счетчиков
```
/var/www/iq210692/data/www/notame.ru/routes/web.php
```

---

### Frontend (5 файлов)

#### 6. resources/views/admin/counters/index.blade.php
**Новый файл** - список счетчиков
```
/var/www/iq210692/data/www/notame.ru/resources/views/admin/counters/index.blade.php
```

#### 7. resources/views/admin/counters/create.blade.php
**Новый файл** - форма создания счетчика
```
/var/www/iq210692/data/www/notame.ru/resources/views/admin/counters/create.blade.php
```

#### 8. resources/views/admin/counters/edit.blade.php
**Новый файл** - форма редактирования счетчика
```
/var/www/iq210692/data/www/notame.ru/resources/views/admin/counters/edit.blade.php
```

#### 9. resources/views/partials/sidebar.blade.php
**Обновлен** - удален VK виджет, добавлен блок счетчиков (sidebar)
```
/var/www/iq210692/data/www/notame.ru/resources/views/partials/sidebar.blade.php
```

#### 10. resources/views/frontend/layout.blade.php
**Обновлен** - удален жестко закодированный счетчик, добавлена динамическая загрузка (header + footer)
```
/var/www/iq210692/data/www/notame.ru/resources/views/frontend/layout.blade.php
```

#### 11. resources/views/layouts/admin.blade.php
**Обновлен** - добавлен пункт меню "Счетчики"
```
/var/www/iq210692/data/www/notame.ru/resources/views/layouts/admin.blade.php
```

---

## 🗄️ База данных (2 SQL скрипта)

### Скрипт 1: Создание таблицы

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

### Скрипт 2: Импорт Яндекс Метрики (ID: 93779125)

**Файл:** `database/sql/insert_yandex_metrika_counter.sql`

Вставляет существующий счетчик Яндекс Метрики в таблицу `counters`.

**Параметры:**
- ID счетчика: 93779125
- WebVisor: включен
- Clickmap: включен
- Track Links: включен
- Позиция: footer

---

## 🚀 Порядок установки (ВАЖНО!)

### 1. Создайте директорию для views

```bash
mkdir -p /var/www/iq210692/data/www/notame.ru/resources/views/admin/counters
```

### 2. Загрузите все 11 файлов (через FTP/SFTP)

**Backend (5):**
1. `app/Models/Counter.php`
2. `app/Http/Controllers/CounterController.php`
3. `database/migrations/2026_01_24_150000_create_counters_table.php`
4. `database/sql/insert_yandex_metrika_counter.sql`
5. `routes/web.php`

**Frontend (6):**
6. `resources/views/admin/counters/index.blade.php`
7. `resources/views/admin/counters/create.blade.php`
8. `resources/views/admin/counters/edit.blade.php`
9. `resources/views/partials/sidebar.blade.php`
10. `resources/views/frontend/layout.blade.php` ← **ВАЖНО! Удаляет жестко закодированный счетчик**
11. `resources/views/layouts/admin.blade.php`

### 3. Выполните SQL скрипты (через phpMyAdmin)

**Скрипт 1:** Создайте таблицу `counters` (SQL выше)

**Скрипт 2:** Импортируйте Яндекс Метрику
```bash
# Откройте файл database/sql/insert_yandex_metrika_counter.sql
# Скопируйте содержимое
# Вставьте в phpMyAdmin → SQL → Выполнить
```

---

## 🧪 Проверка после установки

### Проверка 1: Админка

1. Войдите в админку как суперадмин
2. В меню должен быть пункт **"Счетчики"** (📊)
3. Откройте его
4. Должна быть запись: "Яндекс Метрика (ID: 93779125)"
5. Статус: ✅ Активен
6. Позиция: Footer

### Проверка 2: Сайт (Frontend)

1. Откройте главную страницу сайта
2. Откройте консоль браузера (F12)
3. Вкладка **Network**
4. Должна загружаться: `mc.yandex.ru/metrika/tag.js`
5. Вкладка **Console** - не должно быть ошибок

### Проверка 3: Яндекс Метрика

1. Откройте https://metrika.yandex.ru
2. Счетчик **93779125**
3. Должны быть онлайн посетители
4. WebVisor должен записывать сессии

### Проверка 4: VK виджет удален

1. В сайдбаре **НЕ должно быть** "Подписывайтесь на нас ВКонтакте"
2. В консоли **не должно быть** загрузки `vk.com/js/api/`

### Проверка 5: Нет дублирования

1. В исходном коде страницы (Ctrl+U)
2. Найдите "ym(93779125"
3. Должно быть **ТОЛЬКО ОДИН РАЗ**
4. Если два раза - значит старый код не удален

---

## ⚠️ КРИТИЧЕСКИ ВАЖНО!

### Файл `resources/views/frontend/layout.blade.php`

**Обязательно загрузите обновленную версию!**

**Было (строки 1499-1516) - жестко закодировано:**
```html
<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){...})(...);
    ym(93779125, 'init', {...});
</script>
<!-- /Yandex.Metrika counter -->
```

**Стало - динамическая загрузка:**
```blade
{{-- Счетчики аналитики (footer) --}}
@php
    $footerCounters = \App\Models\Counter::getActiveForPosition('footer');
@endphp
@foreach($footerCounters as $counter)
    {!! $counter->code !!}
@endforeach
```

**Если не обновить этот файл:**
- ❌ Счетчик будет дублироваться (2 раза)
- ❌ Нельзя будет управлять через админку
- ❌ Невозможно будет отключить

---

## 📊 Что изменилось

### ❌ Удалено:

1. **VK виджет** (из `sidebar.blade.php`)
2. **Жестко закодированная Яндекс Метрика** (из `layout.blade.php`)
3. **JavaScript инициализация VK API**

### ✅ Добавлено:

1. **Система управления счетчиками**
2. **Админ-панель** для управления
3. **Динамическая загрузка** счетчиков
4. **3 позиции**: header, footer, sidebar
5. **AJAX вкл/выкл** без перезагрузки
6. **Импорт существующей метрики** в БД

---

## 💡 После установки можно:

1. **Управлять Яндекс Метрикой** через админку
2. **Временно отключать** счетчик одним кликом
3. **Добавить Google Analytics**
4. **Добавить Google Tag Manager**
5. **Добавить любые другие счетчики**
6. **Менять код** без редактирования шаблонов
7. **Переключать позицию** (header/footer/sidebar)

---

## 🆘 Troubleshooting

### Ошибка: "Class 'App\Models\Counter' not found"

**Причина:** Файл `Counter.php` не загружен

**Решение:** Загрузите `app/Models/Counter.php`

### Счетчик дублируется (2 раза)

**Причина:** Старый код не удален из `layout.blade.php`

**Решение:** Загрузите обновленный `layout.blade.php`

### Метрика не работает

**Причина:** SQL скрипт не выполнен

**Решение:** Выполните `insert_yandex_metrika_counter.sql`

### Пункта "Счетчики" нет в меню

**Причина:** Файл `layouts/admin.blade.php` не обновлен

**Решение:** Загрузите обновленный файл

---

## ✅ Чек-лист установки

- [ ] Созданы директории
- [ ] Загружены все 11 файлов
- [ ] Выполнен SQL: создание таблицы `counters`
- [ ] Выполнен SQL: импорт Яндекс Метрики
- [ ] Проверка 1: Админка - пункт "Счетчики" есть
- [ ] Проверка 2: Админка - запись о Яндекс Метрике есть
- [ ] Проверка 3: Сайт - метрика загружается
- [ ] Проверка 4: VK виджет удален
- [ ] Проверка 5: Нет дублирования счетчика
- [ ] Кеш браузера очищен (Ctrl+Shift+R)

---

## 📚 Документация

1. **`COUNTERS_MODULE.md`** - Полная документация модуля
2. **`YANDEX_METRIKA_MIGRATION.md`** - Руководство по миграции
3. **`FILES_TO_UPLOAD_COUNTERS_UPDATED.md`** (этот файл) - Обновленный список файлов
4. **`COUNTERS_SUMMARY.md`** - Краткая сводка

---

✅ **Готово к загрузке!**

**Файлов:** 11 (5 backend + 6 frontend)  
**SQL скриптов:** 2  
**Счетчиков мигрировано:** 1 (Яндекс Метрика)
