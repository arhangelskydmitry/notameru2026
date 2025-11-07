-- РЕШЕНИЕ: Удаляем внешний ключ, потом TRUNCATE
-- Выполняйте каждую команду ОТДЕЛЬНО по порядку!

-- Шаг 1: Удаляем внешний ключ
ALTER TABLE `post_seo` DROP FOREIGN KEY `post_seo_post_id_foreign`;

-- Шаг 2: Теперь можно делать TRUNCATE
TRUNCATE TABLE `post_seo`;
TRUNCATE TABLE `wp_postmeta`;
TRUNCATE TABLE `wp_posts`;
TRUNCATE TABLE `wp_post_views`;

-- Готово! Внешний ключ удалён, таблицы очищены.

