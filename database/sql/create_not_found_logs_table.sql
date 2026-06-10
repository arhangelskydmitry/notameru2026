-- ============================================================================
-- Создание таблицы для логирования 404 ошибок
-- НотаМиру CMS v2.0
-- Дата: 2026-01-25
-- ============================================================================

CREATE TABLE IF NOT EXISTS `not_found_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referer` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GET',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `not_found_logs_url_index` (`url`(255)),
  KEY `not_found_logs_created_at_index` (`created_at`),
  KEY `not_found_logs_url_created_at_index` (`url`(255), `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Готово!
-- ============================================================================
