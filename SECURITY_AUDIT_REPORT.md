# 🔒 АУДИТ БЕЗОПАСНОСТИ И РЕВЬЮ КОДА

**Проект:** notamerularavel (Нота Миру)  
**Дата:** 2026-01-19  
**Версия Laravel:** 11.x

---

## 📊 РЕЗЮМЕ

| Категория | Статус | Критичность |
|-----------|--------|-------------|
| SQL-инъекции | ⚠️ Низкий риск | Средняя |
| XSS уязвимости | 🔴 Обнаружены | Высокая |
| CSRF защита | ✅ В порядке | - |
| Аутентификация | ✅ В порядке | - |
| Загрузка файлов | ⚠️ Частичный риск | Средняя |
| Раскрытие данных | 🔴 Критично | Критическая |
| Публичные скрипты | 🔴 Критично | Критическая |
| Rate Limiting | ✅ В порядке | - |

---

## 🔴 КРИТИЧЕСКИЕ УЯЗВИМОСТИ

### 1. Публичные PHP-скрипты в папке public/

**Риск: КРИТИЧЕСКИЙ 🔴**

В папке `public/` находятся **20+ административных PHP-скриптов** без защиты:

```
public/
├── emergency-db-fix.php         ← Доступ к БД!
├── extreme-db-fix.php           ← Может изменять .env!
├── emergency-rollback.php       ← Откат конфигураций
├── seo-import.php               ← Импорт настроек
├── setup-db-optimization.php    ← Изменение конфигов
├── patch-admin.php              ← Патчи кода
├── optimize-admin-code.php      ← Изменение контроллеров
├── fix-seo-page.php             ← Изменение маршрутов
├── quick-fix.php                ← Исправление middleware
├── db-emergency-fix.php         ← Прямой доступ к БД
├── diagnose-csrf.php            ← Диагностика
├── debug-500.php                ← Отладочная информация
├── test-impression-tracking.php ← Тестирование
├── test-banner-clicks.php       ← Тестирование
├── test-redirect.php            ← Тестирование
├── reset-banner-stats.php       ← Сброс статистики
├── fix-impression-tracking.php  ← Очистка кеша
├── check-php-version.php        ← Информация о PHP
└── banner-redirect.php          ← Редирект (НУЖЕН)
```

**Проблема:**
- Скрипты доступны ЛЮБОМУ посетителю по прямому URL
- Могут модифицировать `.env`, базу данных, код
- Раскрывают конфиденциальную информацию
- `exec()` и `shell_exec()` в некоторых скриптах

**Рекомендация:**

```bash
# Удалить ВСЕ кроме banner-redirect.php и index.php
rm public/emergency-db-fix.php
rm public/extreme-db-fix.php
rm public/emergency-rollback.php
rm public/seo-import.php
rm public/setup-db-optimization.php
rm public/patch-admin.php
rm public/optimize-admin-code.php
rm public/fix-seo-page.php
rm public/quick-fix.php
rm public/db-emergency-fix.php
rm public/diagnose-csrf.php
rm public/debug-500.php
rm public/test-impression-tracking.php
rm public/test-banner-clicks.php
rm public/test-redirect.php
rm public/reset-banner-stats.php
rm public/fix-impression-tracking.php
rm public/check-php-version.php
```

---

### 2. Страница паролей пользователей

**Риск: ВЫСОКИЙ 🔴**

**Файл:** `resources/views/admin/passwords.blade.php`

Страница показывает **пароли пользователей в открытом виде** в HTML:

```html
<input type="password" value="{{ $user['password'] }}" readonly>
<button data-password="{{ $user['password'] }}">Копировать</button>
```

**Проблема:**
- Пароли хранятся и передаются в открытом виде
- Видны в исходном коде страницы
- Могут быть перехвачены при XSS-атаке
- Противоречит OWASP рекомендациям

**Рекомендация:**
1. НЕ хранить пароли в открытом виде
2. Использовать только хеши (bcrypt)
3. Для сброса паролей - генерировать новые и отправлять на email
4. Удалить эту страницу или переделать логику

---

### 3. XSS уязвимости (Stored XSS)

**Риск: ВЫСОКИЙ 🔴**

**Файлы с использованием `{!! !!}` (небезопасный вывод):**

| Файл | Проблема |
|------|----------|
| `resources/views/frontend/post.blade.php` | Вывод контента статей |
| `resources/views/frontend/page.blade.php` | Вывод контента страниц |
| `resources/views/admin/banners/preview.blade.php` | Предпросмотр баннеров |
| `resources/views/admin/passwords.blade.php` | Flash-сообщения |

**Пример (post.blade.php:98):**
```php
{!! \App\Helpers\ContentHelper::getContent($post) !!}
```

**Проблема:**
- Контент из БД выводится без экранирования
- Если в контент внедрён JavaScript - он выполнится
- Злоумышленник может внедрить вредоносный код через редактор

**Рекомендация:**
1. Использовать HTML Purifier для очистки контента
2. Настроить Content Security Policy (CSP) header
3. Валидировать контент при сохранении

```php
// Установить HTML Purifier
composer require ezyang/htmlpurifier

// Использовать при выводе
{!! clean($post->post_content) !!}
```

---

## ⚠️ УЯЗВИМОСТИ СРЕДНЕЙ КРИТИЧНОСТИ

### 4. SQL-запросы с сырыми данными

**Риск: СРЕДНИЙ ⚠️**

Найдено **17 использований** сырых SQL-запросов:

```php
// app/Console/Commands/MonitorDbConnections.php
DB::select('SHOW PROCESSLIST');

// app/Http/Controllers/AdminPanelController.php
DB::raw('COALESCE(CAST(views_meta.meta_value AS UNSIGNED), 0) as current_views')

// app/Models/Banner.php
->selectRaw('SUM(impressions) as total_impressions...')
```

**Оценка:**
- Большинство запросов НЕ используют пользовательский ввод
- `DB::select()` с литералами безопасны
- `selectRaw()` с агрегатными функциями безопасны

**Рекомендация:**
- ✅ Текущее использование в целом безопасно
- ⚠️ При добавлении новых запросов использовать bindings

---

### 5. Загрузка файлов

**Риск: СРЕДНИЙ ⚠️**

**Файл:** `app/Http/Controllers/ImageUploadController.php`

```php
$request->validate([
    'file' => 'required|image|mimes:jpeg,png,gif,webp|max:51200', // 50MB
]);
```

**Что хорошо:**
- ✅ Валидация MIME-типов
- ✅ Ограничение размера (50MB)
- ✅ Конвертация в WebP
- ✅ Генерация уникальных имен файлов

**Что можно улучшить:**
- ⚠️ Не проверяется содержимое файла (можно обойти MIME-type)
- ⚠️ 50MB слишком много для изображений

**Рекомендация:**

```php
$request->validate([
    'file' => 'required|image|mimes:jpeg,png,gif,webp|max:10240', // 10MB
    'file' => 'dimensions:max_width=5000,max_height=5000',
]);
```

---

### 6. Использование exec() и shell_exec()

**Риск: СРЕДНИЙ ⚠️**

**Найдено в файлах:**
- `public/debug-500.php` - `exec("php -l ...")`
- `public/emergency-db-fix.php` - `$pdo->exec("KILL ...")`
- `app/Console/Commands/BackupDatabase.php` - `exec($command)` для mysqldump

**Проблема:**
- `exec()` в публичных скриптах - критическая уязвимость
- В Artisan командах - допустимо с осторожностью

**Рекомендация:**
- Удалить публичные скрипты с `exec()`
- В командах использовать escapeshellarg() (уже используется)

---

## ✅ ЧТО СДЕЛАНО ПРАВИЛЬНО

### 7. CSRF защита

**Статус: ХОРОШО ✅**

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/banner/*',  // Только tracking API
];
```

- Все формы защищены CSRF токенами
- API для баннеров исключено (нужно для трекинга)
- `@csrf` используется в Blade-формах

---

### 8. Аутентификация и авторизация

**Статус: ХОРОШО ✅**

```php
// app/Http/Middleware/AdminAuthenticate.php
- Проверка session('admin_user_id')
- Проверка активности аккаунта
- Проверка наличия роли

// app/Http/Middleware/EnsureSuperAdmin.php
- Дополнительная проверка для суперадмина
```

**Что хорошо:**
- Централизованная проверка прав
- Middleware для всех админ-маршрутов
- Разделение прав (admin/superadmin)
- Rate limiting на API (throttle:120,1)

---

### 9. Rate Limiting

**Статус: ХОРОШО ✅**

```php
// routes/web.php
Route::prefix('api')->middleware('throttle:120,1')->group(function() {
    // 120 запросов в минуту
});

Route::post('/api/banner/impression', [...])
    ->middleware('throttle:120,1');
```

- Все API endpoint защищены throttling
- 120 запросов в минуту - разумный лимит

---

### 10. Конфигурация

**Статус: ХОРОШО ✅**

- `.env` файл в gitignore
- Пароли не хардкодированы в коде
- Используются переменные окружения
- Конфигурации через `config()` helper

---

## 🛠️ ПЛАН ИСПРАВЛЕНИЙ

### Немедленно (критические):

```bash
# 1. Удалить все временные скрипты
cd /var/www/iq210692/data/www/notame.ru/public
rm -f emergency-db-fix.php extreme-db-fix.php emergency-rollback.php
rm -f seo-import.php setup-db-optimization.php patch-admin.php
rm -f optimize-admin-code.php fix-seo-page.php quick-fix.php
rm -f db-emergency-fix.php diagnose-csrf.php debug-500.php
rm -f test-impression-tracking.php test-banner-clicks.php
rm -f test-redirect.php reset-banner-stats.php
rm -f fix-impression-tracking.php check-php-version.php

# 2. Оставить только нужные
# index.php, banner-redirect.php, robots.txt, .htaccess
```

### В ближайшее время:

1. **Переделать страницу паролей:**
   - Удалить хранение паролей в открытом виде
   - Использовать отправку на email при сбросе

2. **Добавить HTML Purifier:**
   ```bash
   composer require ezyang/htmlpurifier
   ```

3. **Добавить CSP header:**
   ```php
   // app/Http/Middleware/ContentSecurityPolicy.php
   $response->headers->set('Content-Security-Policy', 
       "default-src 'self'; script-src 'self' 'unsafe-inline' mc.yandex.ru; ..."
   );
   ```

### При разработке:

1. Использовать OWASP рекомендации
2. Не создавать временные скрипты в public/
3. Использовать Artisan команды вместо PHP-скриптов
4. Проводить code review перед деплоем

---

## 📋 ЧЕКЛИСТ БЕЗОПАСНОСТИ

- [ ] Удалить временные PHP-скрипты из public/
- [ ] Переделать страницу управления паролями
- [ ] Установить HTML Purifier
- [ ] Добавить Content Security Policy
- [ ] Уменьшить лимит загрузки файлов до 10MB
- [ ] Проверить APP_DEBUG=false на продакшене
- [ ] Настроить HTTPS редирект
- [ ] Регулярно обновлять зависимости

---

## 📊 АРХИТЕКТУРА И КАЧЕСТВО КОДА

### Что хорошо:

1. **Структура проекта** - стандартная Laravel 11
2. **Middleware** - правильно используются для авторизации
3. **Eloquent ORM** - используется для большинства запросов
4. **Валидация** - присутствует во всех контроллерах
5. **Логирование** - 336+ точек логирования в приложении
6. **Разделение ответственности** - Services, Helpers, Controllers

### Что можно улучшить:

1. **AdminPanelController** слишком большой (1800+ строк)
   - Разделить на несколько контроллеров

2. **Дублирование кода**
   - ContentHelper::fixImagePaths() и ContentHelper::getContent()

3. **Magic strings**
   - Использовать константы/enum для статусов

---

## 🎯 ПРИОРИТЕТ ИСПРАВЛЕНИЙ

| Приоритет | Задача | Время |
|-----------|--------|-------|
| 🔴 P0 | Удалить публичные PHP-скрипты | 5 мин |
| 🔴 P0 | Переделать страницу паролей | 2-4 часа |
| 🟡 P1 | Установить HTML Purifier | 1 час |
| 🟡 P1 | Добавить CSP headers | 1 час |
| 🟢 P2 | Рефакторинг AdminPanelController | 1-2 дня |
| 🟢 P2 | Уменьшить лимит загрузки файлов | 15 мин |

---

**Отчёт подготовлен: 2026-01-19**  
**Следующий аудит рекомендуется: через 3 месяца**
