# 📦 Файлы для Загрузки: Система Счетчиков

## 🎯 Модуль: Управление Счетчиками Аналитики

**Дата:** 2026-01-24  
**Версия:** v2.0 (Counters Module)  
**Приоритет:** 🟢 Новая функциональность

---

## 📁 Список файлов для загрузки

### Backend (5 файлов)

#### 1. app/Models/Counter.php
**Новый файл** - модель для работы с счетчиками

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/app/Models/Counter.php
```

#### 2. app/Http/Controllers/CounterController.php
**Новый файл** - контроллер для управления счетчиками

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/app/Http/Controllers/CounterController.php
```

#### 3. database/migrations/2026_01_24_150000_create_counters_table.php
**Новый файл** - миграция для создания таблицы

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/database/migrations/2026_01_24_150000_create_counters_table.php
```

#### 4. routes/web.php
**Обновлен** - добавлены маршруты для счетчиков

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/routes/web.php
```

---

### Frontend (5 файлов)

#### 5. resources/views/admin/counters/index.blade.php
**Новый файл** - список счетчиков

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/resources/views/admin/counters/index.blade.php
```

#### 6. resources/views/admin/counters/create.blade.php
**Новый файл** - форма создания счетчика

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/resources/views/admin/counters/create.blade.php
```

#### 7. resources/views/admin/counters/edit.blade.php
**Новый файл** - форма редактирования счетчика

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/resources/views/admin/counters/edit.blade.php
```

#### 8. resources/views/partials/sidebar.blade.php
**Обновлен** - удален VK виджет, добавлен блок счетчиков

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/resources/views/partials/sidebar.blade.php
```

#### 9. resources/views/layouts/admin.blade.php
**Обновлен** - добавлен пункт меню "Счетчики"

**Путь на сервере:**
```
/var/www/iq210692/data/www/notame.ru/resources/views/layouts/admin.blade.php
```

---

## 🗄️ База данных

### Создание таблицы (2 варианта)

#### Вариант 1: Через SSH (рекомендуется)
```bash
cd /var/www/iq210692/data/www/notame.ru
php artisan migrate
```

#### Вариант 2: Через phpMyAdmin (без SSH)

Выполните SQL запрос:

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

## 🚀 Порядок загрузки

### 1. Создайте директории (если нужно)

```bash
# Через SSH
mkdir -p /var/www/iq210692/data/www/notame.ru/resources/views/admin/counters
```

### 2. Загрузите backend файлы (через FTP/SFTP)

1. `app/Models/Counter.php`
2. `app/Http/Controllers/CounterController.php`
3. `database/migrations/2026_01_24_150000_create_counters_table.php`
4. `routes/web.php`

### 3. Загрузите frontend файлы

5. `resources/views/admin/counters/index.blade.php`
6. `resources/views/admin/counters/create.blade.php`
7. `resources/views/admin/counters/edit.blade.php`
8. `resources/views/partials/sidebar.blade.php`
9. `resources/views/layouts/admin.blade.php`

### 4. Создайте таблицу в БД

Используйте вариант 1 (SSH) или вариант 2 (phpMyAdmin)

---

## 🧪 Проверка после загрузки

### Проверка 1: Админка
1. Войдите в админку как суперадмин
2. В меню должен появиться пункт **"Счетчики"** (📊)
3. Нажмите на него
4. Должна открыться страница `/notaadmin/counters`

### Проверка 2: Создание счетчика
1. Нажмите **"Добавить счетчик"**
2. Заполните:
   - Название: "Яндекс Метрика"
   - Позиция: Сайдбар
   - Код: (вставьте тестовый HTML, например `<div>Test</div>`)
   - Активен: ✓
3. Сохраните
4. Должны вернуться на список с сообщением "Счетчик успешно создан!"

### Проверка 3: Отображение на сайте
1. Откройте главную страницу сайта
2. В правом сайдбаре должен быть блок **"Статистика"**
3. В нем должен отображаться ваш тестовый счетчик

### Проверка 4: VK виджет удален
1. В сайдбаре **НЕ должно быть** блока "Подписывайтесь на нас ВКонтакте"
2. В консоли браузера (F12) **не должно быть** загрузки VK API

---

## 🎨 Что изменилось

### ❌ Удалено:
- Виджет ВКонтакте (VK Widget)
- JavaScript инициализация VK API
- Стили для VK виджета

### ✅ Добавлено:
- Полноценная система управления счетчиками
- Админ-панель для добавления/редактирования/удаления
- Поддержка множества позиций (sidebar, footer, header)
- AJAX вкл/выкл счетчиков
- Сортировка счетчиков
- Справка по использованию

---

## 💡 Быстрый старт

После загрузки всех файлов:

1. **Создайте таблицу** (SQL из раздела "База данных")
2. **Войдите в админку** → **Счетчики**
3. **Добавьте Яндекс Метрику:**
   - Название: Яндекс Метрика
   - Позиция: Сайдбар
   - Код: (из metrika.yandex.ru)
   - Активен: ✓
4. **Проверьте на сайте** - в сайдбаре появится блок "Статистика"

---

## 🆘 Если что-то не работает

### Ошибка 500 в админке
**Причина:** Таблица `counters` не создана  
**Решение:** Выполните SQL запрос из раздела "База данных"

### Пункта меню "Счетчики" нет
**Причина:** Вы не суперадмин  
**Решение:** Войдите под суперадмин аккаунтом

### Счетчик не показывается на сайте
**Причина:** Счетчик неактивен или неправильная позиция  
**Решение:** 
1. Проверьте что переключатель "Активен" включен
2. Проверьте что позиция = "sidebar"

### VK виджет все еще показывается
**Причина:** Старый файл `sidebar.blade.php`  
**Решение:** Перезагрузите файл `resources/views/partials/sidebar.blade.php`

---

## 📚 Документация

**Полная документация:** `COUNTERS_MODULE.md`

---

## ✅ Чек-лист загрузки

- [ ] Загружены все 9 файлов
- [ ] Создана таблица `counters` в БД
- [ ] Проверка 1: Пункт меню "Счетчики" есть
- [ ] Проверка 2: Можно создать счетчик
- [ ] Проверка 3: Счетчик показывается в сайдбаре
- [ ] Проверка 4: VK виджет удален
- [ ] Очищен кеш браузера (Ctrl+Shift+R)

---

✅ **Готово к загрузке!**

**Файлов:** 9 (4 backend + 5 frontend)  
**Таблиц БД:** 1  
**Новых маршрутов:** 7
