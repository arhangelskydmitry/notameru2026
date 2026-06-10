#!/bin/bash
#
# Скрипт для локального восстановления из бекапа
# Использование: ./restore-local.sh backup_full_2026-01-24_03-00-00.tar.gz [mode]
#
# Режимы:
#   preview  - показать содержимое (по умолчанию)
#   database - восстановить БД
#   files    - восстановить файлы  
#   full     - полное восстановление
#

BACKUP_FILE="$1"
MODE="${2:-preview}"
BACKUP_PATH="storage/app/backups"
TEMP_PATH="storage/app/temp_restore"

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}🔄 Восстановление из Бекапа${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Проверка параметров
if [ -z "$BACKUP_FILE" ]; then
    echo -e "${RED}❌ Ошибка: Не указан файл бекапа${NC}"
    echo ""
    echo "Использование:"
    echo "  ./restore-local.sh FILENAME [mode]"
    echo ""
    echo "Пример:"
    echo "  ./restore-local.sh backup_full_2026-01-24_03-00-00.tar.gz preview"
    echo ""
    echo "Доступные бекапы:"
    ls -lh "$BACKUP_PATH"/*.tar.gz 2>/dev/null | awk '{print "  - " $9}'
    exit 1
fi

# Проверка существования бекапа
BACKUP_FULL_PATH="$BACKUP_PATH/$BACKUP_FILE"
if [ ! -f "$BACKUP_FULL_PATH" ]; then
    echo -e "${RED}❌ Бекап не найден: $BACKUP_FILE${NC}"
    echo ""
    echo "Доступные бекапы:"
    ls -lh "$BACKUP_PATH"/*.tar.gz 2>/dev/null | awk '{print "  - " $9}'
    exit 1
fi

echo -e "${GREEN}✅ Бекап найден: $BACKUP_FILE${NC}"
BACKUP_SIZE=$(du -h "$BACKUP_FULL_PATH" | cut -f1)
echo -e "   Размер: $BACKUP_SIZE"
echo ""

# Распаковка
echo -e "${YELLOW}📦 Распаковка бекапа...${NC}"
rm -rf "$TEMP_PATH"
mkdir -p "$TEMP_PATH"

tar -xzf "$BACKUP_FULL_PATH" -C "$TEMP_PATH"
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Ошибка распаковки${NC}"
    exit 1
fi

# Используем TEMP_PATH напрямую (архив распаковывается без вложенной папки)
EXTRACTED_DIR="$TEMP_PATH"

echo -e "${GREEN}✅ Бекап распакован${NC}"
echo ""

# Функция: Предпросмотр
preview_backup() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}📋 Содержимое Бекапа${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    if [ -f "$EXTRACTED_DIR/manifest.json" ]; then
        cat "$EXTRACTED_DIR/manifest.json" | python3 -m json.tool 2>/dev/null || cat "$EXTRACTED_DIR/manifest.json"
    else
        echo "manifest.json не найден"
    fi
    
    echo ""
    echo -e "${YELLOW}📂 Структура:${NC}"
    ls -lh "$EXTRACTED_DIR"
    
    echo ""
    echo -e "${YELLOW}⚠️  Что будет перезаписано:${NC}"
    [ -d "$EXTRACTED_DIR/database" ] && echo "  - База данных (ВСЕ таблицы)"
    [ -d "$EXTRACTED_DIR/files" ] && echo "  - Файлы (изображения)"
    [ -d "$EXTRACTED_DIR/config" ] && echo "  - Конфигурация (.env)"
    
    echo ""
    echo -e "${GREEN}✅ Готовы восстановить?${NC}"
    echo "Запустите:"
    echo "  ./restore-local.sh $BACKUP_FILE database  # Только БД"
    echo "  ./restore-local.sh $BACKUP_FILE files     # Только файлы"
    echo "  ./restore-local.sh $BACKUP_FILE full      # Всё"
}

# Функция: Восстановление БД
restore_database() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}🗄️  Восстановление Базы Данных${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    # Ищем SQL файл (в корне TEMP_PATH)
    SQL_FILE=""
    if [ -f "$EXTRACTED_DIR/database.sql.gz" ]; then
        echo -e "${YELLOW}📦 Разархивация database.sql.gz...${NC}"
        gunzip "$EXTRACTED_DIR/database.sql.gz"
        SQL_FILE="$EXTRACTED_DIR/database.sql"
    elif [ -f "$EXTRACTED_DIR/database.sql" ]; then
        SQL_FILE="$EXTRACTED_DIR/database.sql"
    elif [ -f "$EXTRACTED_DIR/database/database.sql.gz" ]; then
        echo -e "${YELLOW}📦 Разархивация database/database.sql.gz...${NC}"
        gunzip "$EXTRACTED_DIR/database/database.sql.gz"
        SQL_FILE="$EXTRACTED_DIR/database/database.sql"
    elif [ -f "$EXTRACTED_DIR/database/database.sql" ]; then
        SQL_FILE="$EXTRACTED_DIR/database/database.sql"
    fi
    
    if [ -z "$SQL_FILE" ] || [ ! -f "$SQL_FILE" ]; then
        echo -e "${RED}❌ SQL файл не найден${NC}"
        return 1
    fi
    
    echo -e "${YELLOW}📥 Импорт БД (может занять несколько минут)...${NC}"
    
    # Получаем данные из .env
    DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
    DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    DB_SOCKET=$(grep DB_SOCKET .env | cut -d '=' -f2)
    
    # Формируем команду
    if [ -n "$DB_SOCKET" ] && [ -f "$DB_SOCKET" ]; then
        MYSQL_CMD="mysql --socket=$DB_SOCKET -u $DB_USERNAME"
    else
        MYSQL_CMD="mysql -h $DB_HOST -u $DB_USERNAME"
    fi
    
    [ -n "$DB_PASSWORD" ] && MYSQL_CMD="$MYSQL_CMD -p$DB_PASSWORD"
    
    $MYSQL_CMD $DB_DATABASE < "$SQL_FILE"
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ База данных восстановлена успешно!${NC}"
        echo ""
        echo -e "${YELLOW}💡 Рекомендация: Очистите кеш${NC}"
        echo "   php artisan cache:clear"
        return 0
    else
        echo -e "${RED}❌ Ошибка импорта БД${NC}"
        return 1
    fi
}

# Функция: Восстановление файлов
restore_files() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}📁 Восстановление Файлов${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    FILES_DIR="$EXTRACTED_DIR/files/public/images"
    if [ ! -d "$FILES_DIR" ]; then
        echo -e "${RED}❌ Файлы не найдены в бекапе${NC}"
        return 1
    fi
    
    TARGET_DIR="public/images"
    
    echo -e "${YELLOW}📂 Копирование файлов...${NC}"
    cp -R "$FILES_DIR"/* "$TARGET_DIR/"
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Файлы восстановлены успешно!${NC}"
        
        echo ""
        echo -e "${YELLOW}🔧 Установка прав...${NC}"
        chmod -R 755 "$TARGET_DIR"
        echo -e "${GREEN}✅ Права установлены${NC}"
        
        return 0
    else
        echo -e "${RED}❌ Ошибка копирования файлов${NC}"
        return 1
    fi
}

# Выполнение в зависимости от режима
case "$MODE" in
    preview)
        preview_backup
        ;;
        
    database)
        restore_database
        ;;
        
    files)
        restore_files
        ;;
        
    full)
        echo -e "${BLUE}========================================${NC}"
        echo -e "${BLUE}🔄 Полное Восстановление${NC}"
        echo -e "${BLUE}========================================${NC}"
        echo ""
        
        restore_database
        DB_RESULT=$?
        
        echo ""
        
        restore_files
        FILES_RESULT=$?
        
        echo ""
        echo -e "${BLUE}========================================${NC}"
        
        if [ $DB_RESULT -eq 0 ] && [ $FILES_RESULT -eq 0 ]; then
            echo -e "${GREEN}✅ Восстановление завершено успешно!${NC}"
            echo ""
            echo -e "${YELLOW}📋 Следующие шаги:${NC}"
            echo "  1. Очистите кеш: php artisan cache:clear"
            echo "  2. Проверьте сайт: http://localhost:8004/"
            echo "  3. Проверьте админку: http://localhost:8004/notaadmin/"
        else
            echo -e "${RED}❌ Восстановление завершено с ошибками${NC}"
        fi
        ;;
        
    *)
        echo -e "${RED}❌ Неизвестный режим: $MODE${NC}"
        echo ""
        echo "Доступные режимы:"
        echo "  preview  - показать содержимое"
        echo "  database - восстановить БД"
        echo "  files    - восстановить файлы"
        echo "  full     - полное восстановление"
        exit 1
        ;;
esac

# Очистка
echo ""
echo -e "${YELLOW}🧹 Очистка временных файлов...${NC}"
rm -rf "$TEMP_PATH"
echo -e "${GREEN}✅ Готово${NC}"
echo ""
