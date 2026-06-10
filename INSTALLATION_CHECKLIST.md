# ✅ Чеклист Установки - Модуль Бекапов

**Дата:** 24 января 2026  
**Версия:** v2.0  
**Время:** ~10 минут

---

## 📋 Подготовка

- [ ] Доступ к phpMyAdmin (база: `iq210692_notamerurework`)
- [ ] Доступ к FTP (FileZilla или другой клиент)
- [ ] Логин суперадмина для проверки

---

## 🗄️ Шаг 1: SQL (1 мин)

- [ ] Открыть phpMyAdmin
- [ ] Выбрать базу `iq210692_notamerurework`
- [ ] Вкладка **SQL**
- [ ] Скопировать код из `database/sql/create_backups_table.sql`
- [ ] Нажать **Выполнить**
- [ ] Проверить: должно появиться "Query OK" или "1 row inserted"

---

## 📤 Шаг 2: Загрузить Файлы (5 мин)

### Backend (7 файлов):

- [ ] `app/Models/Backup.php`
- [ ] `app/Services/BackupService.php` ⚠️ **ИСПРАВЛЕННАЯ**
- [ ] `app/Console/Commands/BackupCreate.php`
- [ ] `app/Console/Commands/BackupCleanup.php`
- [ ] `app/Http/Controllers/BackupController.php` ⚠️ **ИСПРАВЛЕННАЯ**
- [ ] `config/backup.php`
- [ ] `database/migrations/2026_01_24_120000_create_backups_table.php`

### Frontend (2 файла):

- [ ] `resources/views/admin/backups/index.blade.php`
- [ ] `resources/views/layouts/admin.blade.php` ⚠️ **ИСПРАВЛЕННАЯ**

### Routes (2 файла):

- [ ] `routes/console.php`
- [ ] `routes/web.php`

---

## 📁 Шаг 3: Создать Папку (1 мин)

- [ ] Через FTP или файловый менеджер хостинга
- [ ] Создать: `/storage/app/backups/`
- [ ] Установить права: **755** или **775**

---

## 🧹 Шаг 4: Очистить Кеш (1 мин)

### Вариант A: phpMyAdmin
- [ ] Открыть вкладку **SQL**
- [ ] Выполнить:
```sql
DELETE FROM cache;
DELETE FROM cache_locks;
```

### Вариант B: Веб-скрипт (если есть)
- [ ] Открыть: `https://notame.ru/clear-cache.php?key=clear2026`

---

## 🧪 Шаг 5: Тестирование (2 мин)

- [ ] Войти в админку как **Суперадмин**
- [ ] В меню должен появиться пункт: **💾 Бекапы**
- [ ] Открыть: `https://notame.ru/notaadmin/backups`
- [ ] Страница открылась (статистика: 0 бекапов)
- [ ] Нажать **"Создать Бекап"**
- [ ] Выбрать **"Только База Данных"**
- [ ] Ждать 1-2 минуты
- [ ] Должно появиться: **"Бекап успешно создан!"**
- [ ] В списке появился новый бекап
- [ ] Размер ~150-200 MB
- [ ] Нажать кнопку **"Скачать"** (должно начаться скачивание)

---

## ✅ Проверка Успеха

После завершения все пункты должны быть отмечены:

- ✅ SQL выполнен (таблица backups создана)
- ✅ 11 файлов загружены
- ✅ Папка создана с правами 755
- ✅ Кеш очищен
- ✅ Страница бекапов открывается
- ✅ Тестовый бекап создан
- ✅ Бекап можно скачать

---

## 🆘 Решение Проблем

### Ошибка: "Table 'backups' doesn't exist"
- Повторить SQL код в phpMyAdmin

### Ошибка: "Permission denied"
- Проверить права на `/storage/app/backups/` (755)

### Ошибка: "Can't find variable: Swal"
- Проверить что загружен исправленный `admin.blade.php` с SweetAlert2

### Ошибка: "Call to undefined method middleware"
- Проверить что загружен исправленный `BackupController.php`

### Ошибка: "mysqldump error code 2"
- Проверить что загружен исправленный `BackupService.php`

### Ошибка: "open_basedir restriction in effect"
- ✅ Исправлено! Загрузите последнюю версию `BackupService.php`
- Если проблема остается, добавьте в `.env`: `BACKUP_MYSQLDUMP_PATH=mysqldump`

### Страница не открывается
- Очистить кеш еще раз
- Проверить логи: `storage/logs/laravel.log`

---

## 📞 Поддержка

Если что-то не работает, проверьте:
1. Все ли 11 файлов загружены?
2. Создана ли папка `/storage/app/backups/`?
3. Очищен ли кеш?
4. Залогинены ли вы как суперадмин?

---

**Удачной установки!** 🚀

**Примерное время:** 10 минут  
**Сложность:** ⭐⭐ (Средняя)  
**Статус:** Production Ready
