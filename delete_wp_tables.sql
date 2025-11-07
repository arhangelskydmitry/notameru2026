-- Скрипт для удаления WordPress таблиц
-- База данных: notameru_rework

-- ВАЖНО: Выполнять команды по порядку!

-- Шаг 1: Отключаем проверку внешних ключей
SET FOREIGN_KEY_CHECKS = 0;

-- Шаг 2: Удаляем таблицу post_seo (содержит FK на wp_posts)
DROP TABLE IF EXISTS `post_seo`;

-- Шаг 3: Удаляем WordPress таблицы
DROP TABLE IF EXISTS `wp_postmeta`;
DROP TABLE IF EXISTS `wp_posts`;
DROP TABLE IF EXISTS `wp_post_views`;

-- Шаг 4: Включаем обратно проверку внешних ключей
SET FOREIGN_KEY_CHECKS = 1;

-- Готово!
SELECT 'Таблицы успешно удалены!' as status;

