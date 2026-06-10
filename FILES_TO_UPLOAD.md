# ✅ Финальный Список Файлов для Загрузки на Production

**Дата:** 24 января 2026  
**Всего файлов:** 12  
**Критичных:** 6 (обязательно последние версии)

---

## ⭐ КРИТИЧНО: 6 Исправленных Файлов

### 1. `app/Services/BackupService.php` ⭐⭐⭐ САМЫЙ ВАЖНЫЙ
**Путь на production:** `/var/www/iq210692/data/www/notame.ru/app/Services/BackupService.php`

**Содержит исправления:**
- ✅ Mysqldump автоопределение (MAMP/system/usr/bin)
- ✅ open_basedir защита (@ и try-catch)
- ✅ Force flag поддержка (пропуск rate limit)
- ✅ Безопасная передача пароля (MYSQL_PWD)
- ✅ **Shared-hosting параметры** (`--skip-lock-tables --no-tablespaces`)

**Без этого файла модуль НЕ ЗАРАБОТАЕТ на shared-хостинге!**

---

### 2. `app/Http/Controllers/BackupController.php`
**Путь на production:** `/var/www/iq210692/data/www/notame.ru/app/Http/Controllers/BackupController.php`

**Содержит исправления:**
- ✅ Убран middleware из конструктора (Laravel 11 совместимость)
- ✅ Убраны неиспользуемые методы (settings, saveSettings)

---

### 3. `app/Console/Commands/BackupCreate.php`
**Путь на production:** `/var/www/iq210692/data/www/notame.ru/app/Console/Commands/BackupCreate.php`

**Содержит исправления:**
- ✅ Поддержка флага --force для игнорирования rate limit

---

### 4. `resources/views/layouts/admin.blade.php`
**Путь на production:** `/var/www/iq210692/data/www/notame.ru/resources/views/layouts/admin.blade.php`

**Содержит исправления:**
- ✅ SweetAlert2 CDN подключен

---

### 5. `resources/views/admin/backups/index.blade.php`
**Путь на production:** `/var/www/iq210692/data/www/notame.ru/resources/views/admin/backups/index.blade.php`

**Содержит исправления:**
- ✅ Убрана кнопка "Настройки" (несуществующий view)

---

### 6. `routes/web.php`
**Путь на production:** `/var/www/iq210692/data/www/notame.ru/routes/web.php`

**Содержит исправления:**
- ✅ Убраны routes для settings (несуществующие методы)

---

## 📁 5 Файлов Без Изменений (загрузить как есть)

### 6. `app/Models/Backup.php`
**Путь:** `/var/www/iq210692/data/www/notame.ru/app/Models/Backup.php`

### 7. `app/Console/Commands/BackupCleanup.php`
**Путь:** `/var/www/iq210692/data/www/notame.ru/app/Console/Commands/BackupCleanup.php`

### 8. `config/backup.php`
**Путь:** `/var/www/iq210692/data/www/notame.ru/config/backup.php`

### 9. `database/migrations/2026_01_24_120000_create_backups_table.php`
**Путь:** `/var/www/iq210692/data/www/notame.ru/database/migrations/2026_01_24_120000_create_backups_table.php`

### 10. `routes/console.php`
**Путь:** `/var/www/iq210692/data/www/notame.ru/routes/console.php`

---

## 🗄️ SQL Скрипт (не загружать, выполнить в phpMyAdmin)

### 11. `database/sql/create_backups_table.sql`
**Действие:** Скопировать код → phpMyAdmin → SQL → Выполнить

---

## 📋 Чеклист Перед Загрузкой

- [ ] Все 12 файлов подготовлены
- [ ] **6 исправленных файлов** - последние версии
- [ ] `BackupService.php` - **последняя версия** (с `--skip-lock-tables`)
- [ ] `BackupController.php` - без методов settings
- [ ] `index.blade.php` - без кнопки "Настройки"
- [ ] `routes/web.php` - без routes для settings
- [ ] FTP клиент подключен к серверу
- [ ] Сделан бекап текущих файлов (если есть совпадения)

---

## 🚀 После Загрузки

### Шаг 1: Создать папку
```
Путь: /var/www/iq210692/data/www/notame.ru/storage/app/backups/
Права: 755 или 775
```

### Шаг 2: Очистить кеш
```sql
DELETE FROM cache;
DELETE FROM cache_locks;
```

### Шаг 3: Тест
```
URL: https://notame.ru/notaadmin/backups
Действие: Создать тестовый бекап (Только БД)
```

---

## ⚠️ ВАЖНО

### Файл `BackupService.php` КРИТИЧЕН!

Если загрузить старую версию без исправления для shared-хостинга:
❌ **Ошибка:** `Access denied; need RELOAD privilege`

Убедитесь что загружаете версию с параметрами:
✅ `--skip-lock-tables --no-tablespaces`

**Проверка:** Откройте файл → Найдите строку 198-204 → Должно быть:
```php
$command = sprintf(
    '%s %s --user=%s %s --skip-lock-tables --quick --no-tablespaces',
    ...
);
```

---

## 📊 Контрольные Суммы (опционально)

Если нужна дополнительная проверка целостности файлов:

```bash
# Локально
md5 app/Services/BackupService.php
```

Сравните с файлом на production после загрузки.

---

## 🆘 Если Что-то Пошло Не Так

### Вариант 1: Перезагрузить все 12 файлов
Убедитесь что берете файлы из `/Users/mac/SITES_NEW/notamerularavel/`

### Вариант 2: Проверить логи
```
storage/logs/laravel.log
```

### Вариант 3: Написать в поддержку
Приложите скриншот ошибки из логов или браузера.

---

✅ **Список готов!** Загрузите все 12 файлов на production.

---

**Файлов для загрузки:** 11 (было 12)  
**Время загрузки:** ~5 минут  
**Критичных файлов:** 6 ⭐
