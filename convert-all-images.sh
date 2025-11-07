#!/bin/bash

# Скрипт для полной конвертации всех изображений

cd /Users/mac/Sites/notamerularavel

echo "🚀 Запуск полной конвертации изображений"
echo "========================================"
echo ""

ITERATION=1
MAX_ITERATIONS=50  # Максимум 50 итераций (защита от бесконечного цикла)

while [ $ITERATION -le $MAX_ITERATIONS ]; do
    echo "📦 Итерация $ITERATION"
    echo "-------------------"
    
    # Запускаем конвертацию
    php artisan images:convert-remaining --limit=200
    
    # Проверяем код возврата
    if [ $? -ne 0 ]; then
        echo "❌ Ошибка при выполнении команды"
        exit 1
    fi
    
    # Проверяем, остались ли посты с notame.ru
    REMAINING=$(mysql -h 127.0.0.1 -P 8889 -u root -proot notameru -e "SELECT COUNT(*) FROM wp_posts WHERE post_type='post' AND post_status='publish' AND (post_content LIKE '%notame.ru%' OR post_excerpt LIKE '%notame.ru%');" -s -N 2>/dev/null)
    
    echo ""
    echo "📊 Осталось постов: $REMAINING"
    echo ""
    
    # Если остались посты, продолжаем
    if [ "$REMAINING" -gt "0" ]; then
        echo "⏳ Пауза 2 секунды перед следующей итерацией..."
        sleep 2
        ITERATION=$((ITERATION + 1))
    else
        echo ""
        echo "🎉 Все изображения успешно сконвертированы!"
        echo ""
        
        # Финальная статистика
        TOTAL_IMAGES=$(ls /Users/mac/Sites/notamerularavel/public/imgnews/ | wc -l)
        TOTAL_SIZE=$(du -sh /Users/mac/Sites/notamerularavel/public/imgnews/ | awk '{print $1}')
        
        echo "📊 Финальная статистика:"
        echo "  - Всего изображений: $TOTAL_IMAGES"
        echo "  - Общий размер: $TOTAL_SIZE"
        echo ""
        
        exit 0
    fi
done

echo "⚠️ Достигнут лимит итераций ($MAX_ITERATIONS)"
echo "Возможно, остались изображения для конвертации"
echo "Запустите скрипт снова или проверьте логи"




