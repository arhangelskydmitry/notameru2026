#!/bin/bash

# Скрипт для массового обновления путей к изображениям через SQL

echo "🔧 Массовое обновление путей к изображениям в БД"
echo "================================================"
echo ""

# SQL команды для замены путей
mysql -h 127.0.0.1 -P 8889 -u root -proot notameru <<EOF

-- Обновляем пути с .jpg на .webp
UPDATE wp_posts 
SET post_content = REPLACE(post_content, 'https://notame.ru/wp-content/uploads/', '/imgnews/')
WHERE post_type='post' 
AND post_status='publish' 
AND post_content LIKE '%https://notame.ru/wp-content/uploads/%';

-- Также обновляем http://
UPDATE wp_posts 
SET post_content = REPLACE(post_content, 'http://notame.ru/wp-content/uploads/', '/imgnews/')
WHERE post_type='post' 
AND post_status='publish' 
AND post_content LIKE '%http://notame.ru/wp-content/uploads/%';

-- Заменяем расширения на .webp
UPDATE wp_posts 
SET post_content = REPLACE(post_content, '.jpg', '.webp')
WHERE post_type='post' 
AND post_status='publish' 
AND post_content LIKE '%/imgnews/%';

UPDATE wp_posts 
SET post_content = REPLACE(post_content, '.jpeg', '.webp')
WHERE post_type='post' 
AND post_status='publish' 
AND post_content LIKE '%/imgnews/%';

UPDATE wp_posts 
SET post_content = REPLACE(post_content, '.png', '.webp')
WHERE post_type='post' 
AND post_status='publish' 
AND post_content LIKE '%/imgnews/%';

UPDATE wp_posts 
SET post_content = REPLACE(post_content, '.gif', '.webp')
WHERE post_type='post' 
AND post_status='publish' 
AND post_content LIKE '%/imgnews/%';

-- Обновляем excerpt
UPDATE wp_posts 
SET post_excerpt = REPLACE(post_excerpt, 'https://notame.ru/wp-content/uploads/', '/imgnews/')
WHERE post_type='post' 
AND post_status='publish' 
AND post_excerpt LIKE '%https://notame.ru/wp-content/uploads/%';

-- Проверяем результат
SELECT COUNT(*) as remaining FROM wp_posts 
WHERE post_type='post' 
AND post_status='publish' 
AND post_content LIKE '%notame.ru/wp-content/uploads%';

EOF

echo ""
echo "✅ Массовое обновление завершено!"
















