# 🚀 Восстановление на Локальном Сервере (MAMP)

**Скрипт:** `restore-local.sh`  
**Где:** `/Users/mac/SITES_NEW/notamerularavel/`

---

## ⚡ Быстрое Использование

### 1️⃣ Посмотреть доступные бекапы

```bash
ls -lh storage/app/backups/*.tar.gz
```

### 2️⃣ Предпросмотр (безопасно)

```bash
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz preview
```

### 3️⃣ Восстановить БД

```bash
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz database
```

### 4️⃣ Восстановить Файлы

```bash
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz files
```

### 5️⃣ Полное Восстановление

```bash
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz full
```

---

## 📋 Режимы Работы

| Режим | Что делает | Безопасно |
|-------|------------|-----------|
| `preview` | Показывает содержимое бекапа | ✅ Да |
| `database` | Восстанавливает только БД | ⚠️ Перезапишет БД |
| `files` | Восстанавливает только файлы | ⚠️ Перезапишет файлы |
| `full` | Восстанавливает всё (БД + файлы) | ⚠️ Перезапишет всё |

---

## ✅ Что Показывает Preview

```json
{
    "version": "2.0",
    "type": "database",
    "created_at": "2026-01-24T21:55:46+03:00",
    "laravel_version": "12.37.0",
    "php_version": "8.3.12",
    "contents": {
        "database": {
            "file": "database.sql.gz",
            "size": 36171164,
            "tables_count": 45
        }
    }
}
```

**Показывает:**
- Тип бекапа
- Дату создания
- Версию Laravel/PHP
- Размер и количество таблиц
- Что будет перезаписано

---

## ⚠️ ВАЖНО

### Перед Восстановлением:

1. **Создайте бекап текущего состояния:**
   ```bash
   php artisan backup:create --type=database --force
   ```

2. **Убедитесь что выбрали правильный бекап:**
   ```bash
   ./restore-local.sh FILENAME preview
   ```

### После Восстановления:

1. **Очистите кеш:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

2. **Проверьте сайт:**
   ```
   http://localhost:8004/
   http://localhost:8004/notaadmin/
   ```

---

## 🔧 Требования

- ✅ MAMP или другой локальный сервер
- ✅ MySQL/MariaDB
- ✅ Команды: `tar`, `gunzip`, `mysql`, `cp`
- ✅ Права на запись в `storage/` и `public/images/`

---

## 💡 Примеры Использования

### Пример 1: Проверить что в бекапе

```bash
cd /Users/mac/SITES_NEW/notamerularavel

# Посмотреть список бекапов
ls -lh storage/app/backups/

# Выбрать бекап
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz preview
```

### Пример 2: Восстановить после ошибки

```bash
# 1. Создать бекап текущего состояния
php artisan backup:create --force

# 2. Предпросмотр восстановления
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz preview

# 3. Восстановить БД
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz database

# 4. Очистить кеш
php artisan cache:clear

# 5. Проверить
open http://localhost:8004/
```

### Пример 3: Восстановить удаленные изображения

```bash
# Восстановить только файлы (не трогая БД)
./restore-local.sh backup_full_2026-01-24_03-00-00.tar.gz files
```

---

## 🆘 Решение Проблем

### Ошибка: "Бекап не найден"

**Проверьте путь:**
```bash
ls storage/app/backups/
```

**Используйте полное имя файла:**
```bash
./restore-local.sh backup_database_2026-01-24_21-55-41.tar.gz preview
```

---

### Ошибка: "Permission denied"

**Сделайте скрипт исполняемым:**
```bash
chmod +x restore-local.sh
```

---

### Ошибка: "mysql: command not found"

**Добавьте MAMP в PATH:**
```bash
export PATH="/Applications/MAMP/Library/bin:$PATH"
```

**Или используйте полный путь:**
Отредактируйте `restore-local.sh`, замените `mysql` на:
```bash
/Applications/MAMP/Library/bin/mysql
```

---

### Ошибка импорта БД

**Проверьте настройки в `.env`:**
```bash
cat .env | grep DB_
```

**Убедитесь что:**
- DB_HOST правильный
- DB_DATABASE существует
- DB_USERNAME и DB_PASSWORD корректны
- DB_SOCKET указан (для MAMP)

---

## 📊 Логи

Все действия логируются в:
```
storage/logs/restore.log
```

**Посмотреть логи:**
```bash
tail -f storage/logs/restore.log
```

---

## 🎯 Итог

### Для Production (через Web):
Используйте `restore-backup.php` (загрузите на сервер)

### Для Локального (MAMP):
Используйте `restore-local.sh` ✅ **Работает прямо сейчас!**

---

**Протестировано:** ✅ Работает на MAMP  
**Время восстановления:** 2-5 минут  
**Безопасность:** Preview режим безопасен
