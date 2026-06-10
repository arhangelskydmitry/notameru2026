# 📦 Установка Модуля Бекапов на Production

**Дата:** 24 января 2026  
**Версия:** v2.0  
**Модуль:** Автоматические бекапы

---

## 🎯 Быстрая Установка

### Шаг 1: SQL через phpMyAdmin

1. Откройте phpMyAdmin
2. Выберите базу данных `iq210692_notamerurework`
3. Перейдите на вкладку **SQL**
4. Скопируйте и выполните код из файла: `database/sql/create_backups_table.sql`

**Содержимое SQL:**

```sql
-- Создание таблицы backups
CREATE TABLE IF NOT EXISTS `backups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL COMMENT 'Имя файла бекапа',
  `type` ENUM('full', 'database', 'files') NOT NULL DEFAULT 'full' COMMENT 'Тип бекапа',
  `size` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Размер в байтах',
  `status` ENUM('in_progress', 'completed', 'failed') NOT NULL DEFAULT 'in_progress' COMMENT 'Статус',
  `storage` ENUM('local', 'remote') NOT NULL DEFAULT 'local' COMMENT 'Тип хранилища',
  `storage_path` VARCHAR(500) NULL COMMENT 'Путь в хранилище',
  `triggered_by` VARCHAR(50) NOT NULL DEFAULT 'auto' COMMENT 'Кто запустил: auto, manual, user_id',
  `manifest` JSON NULL COMMENT 'Метаданные: таблицы, файлы, версия',
  `error_message` TEXT NULL COMMENT 'Сообщение об ошибке',
  `started_at` TIMESTAMP NULL COMMENT 'Начало создания',
  `completed_at` TIMESTAMP NULL COMMENT 'Завершение создания',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавление записи в таблицу migrations
INSERT IGNORE INTO `migrations` (`migration`, `batch`) 
VALUES ('2026_01_24_120000_create_backups_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS temp));
```

✅ Должен вывести: **"Query OK, 0 rows affected"** или **"1 row inserted"**

---

### Шаг 2: Загрузить Файлы через FTP

#### Backend (7 файлов):

1. **`app/Models/Backup.php`**
2. **`app/Services/BackupService.php`**
3. **`app/Console/Commands/BackupCreate.php`**
4. **`app/Console/Commands/BackupCleanup.php`**
5. **`app/Http/Controllers/BackupController.php`**
6. **`config/backup.php`**
7. **`database/migrations/2026_01_24_120000_create_backups_table.php`**

#### Frontend (2 файла):

8. **`resources/views/admin/backups/index.blade.php`**
9. **`resources/views/layouts/admin.blade.php`** (обновлен - добавлен пункт меню)

#### Routes (2 файла):

10. **`routes/console.php`** (обновлен - добавлено расписание)
11. **`routes/web.php`** (обновлен - добавлены маршруты)

---

### Шаг 3: Создать Директорию для Бекапов

Через FTP или файловый менеджер хостинга:

```
/storage/app/backups/
```

Установите права: **755** (или 775)

---

### Шаг 4: Очистить Кеш

**Вариант A:** Через phpMyAdmin

```sql
DELETE FROM cache;
DELETE FROM cache_locks;
```

**Вариант B:** Через веб-скрипт (если есть `clear-cache.php`):

Откройте: `https://notame.ru/clear-cache.php?key=clear2026`

---

### Шаг 5: Проверить Работу

1. Войдите в админку как **Суперадмин**
2. В меню появится новый пункт: **"💾 Бекапы"**
3. Перейдите на страницу бекапов
4. Попробуйте создать тестовый бекап (Только БД)

---

## 📋 Структура Загружаемых Файлов

```
app/
├── Models/
│   └── Backup.php                          [НОВЫЙ]
├── Services/
│   └── BackupService.php                   [НОВЫЙ]
├── Console/
│   └── Commands/
│       ├── BackupCreate.php                [НОВЫЙ]
│       └── BackupCleanup.php               [НОВЫЙ]
└── Http/
    └── Controllers/
        └── BackupController.php            [НОВЫЙ]

config/
└── backup.php                              [НОВЫЙ]

database/
├── migrations/
│   └── 2026_01_24_120000_create_backups_table.php  [НОВЫЙ]
└── sql/
    └── create_backups_table.sql            [НОВЫЙ - для phpMyAdmin]

resources/views/
├── admin/
│   └── backups/
│       └── index.blade.php                 [НОВЫЙ]
└── layouts/
    └── admin.blade.php                     [ОБНОВЛЕН]

routes/
├── console.php                             [ОБНОВЛЕН]
└── web.php                                 [ОБНОВЛЕН]

storage/app/
└── backups/                                [СОЗДАТЬ ПАПКУ]
```

---

## ⚙️ Конфигурация (.env)

Добавьте в `.env` (опционально):

```env
# Модуль бекапов
BACKUP_ENABLED=true
BACKUP_FREQUENCY=daily
BACKUP_TIME=03:00
BACKUP_NOTIFICATION_EMAIL=admin@notame.ru
BACKUP_NOTIFICATIONS_ENABLED=true
```

---

## 🧪 Тестирование

### Проверка 1: Доступ к странице
```
URL: https://notame.ru/notaadmin/backups
Ожидаемо: Страница со списком бекапов (пустая)
```

### Проверка 2: Создание бекапа
```
1. Нажать "Создать Бекап"
2. Выбрать "Только База Данных"
3. Ожидать 1-2 минуты
4. Должно появиться: "Бекап успешно создан!"
```

### Проверка 3: Скачивание
```
1. В списке бекапов нажать кнопку "Скачать"
2. Должен начаться скачивание .tar.gz файла
```

---

## 🔒 Безопасность

✅ Доступ только для **суперадминов**  
✅ Все формы защищены **CSRF токеном**  
✅ Бекапы хранятся **вне public/** директории  
✅ Rate limiting: **1 бекап в 5 минут**

---

## 📊 Автоматическое Расписание

После установки бекапы будут создаваться автоматически:

- **Частота:** Ежедневно в 03:00 (настраивается в .env)
- **Тип:** Полный бекап (БД + файлы + конфиг)
- **Ротация:** Хранятся последние 7 бекапов

**Для работы cron:** Убедитесь, что на хостинге настроен Laravel Scheduler:

```bash
* * * * * cd /var/www/iq210692/data/www/notame.ru && php artisan schedule:run >> /dev/null 2>&1
```

*(Обычно это настраивается в панели управления хостингом)*

---

## ❓ Решение Проблем

### Ошибка: "Table 'backups' doesn't exist"
**Решение:** Повторно выполните SQL код в phpMyAdmin

### Ошибка: "Permission denied" при создании бекапа
**Решение:** Проверьте права на папку `storage/app/backups/` (должно быть 755 или 775)

### Бекапы не создаются автоматически
**Решение:** Проверьте настройку cron в панели хостинга

### Страница бекапов не открывается
**Решение:** Очистите кеш через phpMyAdmin или `clear-cache.php`

---

## ✅ Контрольный Список

- [ ] SQL выполнен в phpMyAdmin
- [ ] 11 файлов загружены через FTP
- [ ] Папка `storage/app/backups/` создана с правами 755
- [ ] Кеш очищен
- [ ] Страница бекапов открывается
- [ ] Тестовый бекап создан успешно
- [ ] Бекап можно скачать

---

**Время установки:** ~10-15 минут  
**Дата:** 24 января 2026  
**Модуль:** Автоматические Бекапы v2.0
