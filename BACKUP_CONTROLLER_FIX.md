# 🔧 Исправление: BackupController middleware

**Проблема:** `Call to undefined method middleware()`

**Причина:** В Laravel 11 изменена структура middleware

---

## ✅ Исправленная Версия

Файл: `app/Http/Controllers/BackupController.php`

**Строки 11-20 заменить на:**

```php
class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }
```

**Убрать эти строки:**
```php
// Только суперадмины могут управлять бекапами
$this->middleware(['auth', 'role:superadmin']);
```

---

## 📝 Объяснение

Middleware уже настроен в `routes/web.php`:

```php
Route::middleware('superadmin')->prefix('backups')->name('admin.backups.')->group(function () {
    // ... все маршруты бекапов
});
```

Это правильный способ для Laravel 11.

---

## 🚀 После Исправления

1. Загрузите исправленный `app/Http/Controllers/BackupController.php`
2. Очистите кеш: `DELETE FROM cache;` в phpMyAdmin
3. Обновите страницу

---

✅ Ошибка исправлена!
