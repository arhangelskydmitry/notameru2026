DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_key_unique` (`key`),
    KEY `settings_key_index` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key`, `value`) VALUES
    ('yandex_metrika_id',        '93779125'),
    ('yandex_metrika_token',     ''),
    ('yandex_webmaster_token',   ''),
    ('yandex_webmaster_host_id', 'https:notame.ru:443'),
    ('yandex_webmaster_verification', '');



