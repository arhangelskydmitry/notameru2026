-- Скрипт для очистки WordPress таблиц через TRUNCATE
-- База данных: notameru_rework

-- ВАЖНО: Выполнять все команды вместе!

-- Отключаем проверку внешних ключей
SET FOREIGN_KEY_CHECKS = 0;

-- Очищаем таблицы
TRUNCATE TABLE `post_seo`;
TRUNCATE TABLE `wp_postmeta`;
TRUNCATE TABLE `wp_posts`;
TRUNCATE TABLE `wp_post_views`;

-- Включаем обратно проверку внешних ключей
SET FOREIGN_KEY_CHECKS = 1;

-- Проверяем результат
SELECT 'Таблицы успешно очищены!' as status;

