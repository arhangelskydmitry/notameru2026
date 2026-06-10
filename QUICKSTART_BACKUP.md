# 🚀 Быстрый Старт - Установка Модуля Бекапов

**Время:** 10 минут | **Сложность:** ⭐⭐ Средняя | **Статус:** ✅ Production Ready

---

## 📥 Что Загрузить на Production

### 4 исправленных файла (ОБЯЗАТЕЛЬНО):
```
⭐ app/Services/BackupService.php (КРИТИЧНО - shared hosting fix)
✅ app/Http/Controllers/BackupController.php
✅ app/Console/Commands/BackupCreate.php
✅ resources/views/layouts/admin.blade.php
```

### 8 файлов без изменений:
```
app/Models/Backup.php
app/Console/Commands/BackupCleanup.php
config/backup.php
database/migrations/2026_01_24_120000_create_backups_table.php
resources/views/admin/backups/index.blade.php
routes/console.php
routes/web.php
```

---

## ⚡ 5 Шагов Установки

### 1️⃣ SQL (1 мин)
phpMyAdmin → SQL → Выполнить код из `database/sql/create_backups_table.sql`

### 2️⃣ FTP (5 мин)
Загрузить 12 файлов

### 3️⃣ Папка (1 мин)
Создать `/storage/app/backups/` с правами 755

### 4️⃣ Кеш (1 мин)
phpMyAdmin → SQL:
```sql
DELETE FROM cache;
DELETE FROM cache_locks;
```

### 5️⃣ Тест (2 мин)
- Открыть: `https://notame.ru/notaadmin/backups`
- Создать тестовый бекап (Только БД)
- Проверить скачивание

---

## 🆘 Если Ошибки

| Ошибка | Решение |
|--------|---------|
| `Table 'backups' doesn't exist` | Повторить SQL из шага 1 |
| `Permission denied` | Права 755 на `/storage/app/backups/` |
| `Can't find variable: Swal` | Загрузить исправленный `admin.blade.php` |
| `Call to undefined method` | Загрузить исправленный `BackupController.php` |
| `open_basedir restriction` | Загрузить исправленный `BackupService.php` |
| `Access denied; need RELOAD` ⭐ | Загрузить **последнюю версию** `BackupService.php` |

---

## 📖 Документация

**Главный документ:** `INSTALLATION_CHECKLIST.md`  
**Все исправления:** `BACKUP_MODULE_FINAL_STATUS.md`

---

✅ **Готово!** После установки бекапы будут создаваться автоматически каждый день.
