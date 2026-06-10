# 🚀 Быстрая Установка Модуля Бекапов

## Шаг 1: SQL в phpMyAdmin

Файл: `database/sql/create_backups_table.sql`

```sql
CREATE TABLE IF NOT EXISTS `backups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `type` ENUM('full', 'database', 'files') NOT NULL DEFAULT 'full',
  `size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('in_progress', 'completed', 'failed') NOT NULL DEFAULT 'in_progress',
  `storage` ENUM('local', 'remote') NOT NULL DEFAULT 'local',
  `storage_path` VARCHAR(500) NULL,
  `triggered_by` VARCHAR(50) NOT NULL DEFAULT 'auto',
  `manifest` JSON NULL,
  `error_message` TEXT NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `migrations` (`migration`, `batch`) 
VALUES ('2026_01_24_120000_create_backups_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS temp));
```

## Шаг 2: Загрузить 11 файлов через FTP

### Backend:
1. `app/Models/Backup.php`
2. `app/Services/BackupService.php`
3. `app/Console/Commands/BackupCreate.php`
4. `app/Console/Commands/BackupCleanup.php`
5. `app/Http/Controllers/BackupController.php`
6. `config/backup.php`
7. `database/migrations/2026_01_24_120000_create_backups_table.php`

### Frontend:
8. `resources/views/admin/backups/index.blade.php`
9. `resources/views/layouts/admin.blade.php`

### Routes:
10. `routes/console.php`
11. `routes/web.php`

## Шаг 3: Создать папку

```
/storage/app/backups/
Права: 755
```

## Шаг 4: Очистить кеш

**phpMyAdmin:**
```sql
DELETE FROM cache;
```

**Или веб-скрипт:**
```
https://notame.ru/clear-cache.php?key=clear2026
```

## Шаг 5: Проверка

1. Войти как суперадмин
2. Открыть: `https://notame.ru/notaadmin/backups`
3. Создать тестовый бекап (Только БД)

---

✅ Готово! Модуль работает.
