-- ============================================
-- Создание таблицы backups для модуля бекапов
-- Дата: 24 января 2026
-- Версия: v2.0
-- ============================================

-- Проверка и создание таблицы backups
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

-- ============================================
-- Готово! Таблица backups создана.
-- ============================================
