# Перенос настроек SEO AI на сервер

## Обзор

Настройки SEO AI хранятся в таблице `settings` в базе данных через модель `Setting`. Для переноса на сервер нужно экспортировать настройки из локальной БД и импортировать их на сервер.

## Что переносится

### Настройки в базе данных:
- `seo_ai_provider` - предпочтительный провайдер (gigachat/openai/chatinfo)
- `gigachat_client_id` - Client ID для GigaChat
- `gigachat_client_secret` - Client Secret для GigaChat
- `gigachat_scope` - Scope для GigaChat (GIGACHAT_API_PERS/GIGACHAT_API_CORP/GIGACHAT_API_B2B)
- `chatinfo_api_key` - API ключ для ChatInfo

### Настройки в .env:
- `OPENAI_API_KEY` - API ключ для OpenAI (не хранится в БД)

## Шаг 1: Экспорт настроек с локального сервера

```bash
cd /Users/mac/SITES_NEW/notamerularavel
php scripts/export-seo-settings.php > seo-settings.json
```

Это создаст файл `seo-settings.json` с настройками в формате JSON.

## Шаг 2: Перенос файла на сервер

Скопируйте файл `seo-settings.json` на сервер:

```bash
# Через SCP
scp seo-settings.json user@notame.ru:/path/to/project/

# Или через SFTP/FTP клиент
```

## Шаг 3: Импорт настроек на сервере

На сервере выполните:

```bash
cd /path/to/project
php scripts/import-seo-settings.php seo-settings.json
```

## Шаг 4: Настройка OpenAI API ключа

Откройте `.env` файл на сервере и добавьте:

```env
OPENAI_API_KEY=sk-proj-...
```

## Шаг 5: Очистка кеша

После импорта очистите кеш:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Альтернативный способ: SQL экспорт/импорт

### Экспорт через SQL:

```sql
SELECT `key`, `value` 
FROM `settings` 
WHERE `key` IN (
    'seo_ai_provider',
    'gigachat_client_id',
    'gigachat_client_secret',
    'gigachat_scope',
    'chatinfo_api_key'
);
```

### Импорт через SQL:

```sql
INSERT INTO `settings` (`key`, `value`, `created_at`, `updated_at`) VALUES
    ('seo_ai_provider', 'gigachat', NOW(), NOW()),
    ('gigachat_client_id', '019b6a0f-fd73-7792-9730-e880dd35e972', NOW(), NOW()),
    ('gigachat_client_secret', 'd830b21d-7f92-43ff-9c02-8cefc63921de', NOW(), NOW()),
    ('gigachat_scope', 'GIGACHAT_API_B2B', NOW(), NOW()),
    ('chatinfo_api_key', 'ae6d2d0d-292f-45ea-9885-ef7b32dcd23b', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `value` = VALUES(`value`),
    `updated_at` = NOW();
```

## Проверка после переноса

1. Зайдите на страницу настроек: `https://notame.ru/notaadmin/seo-settings`
2. Проверьте, что все настройки отображаются корректно
3. Нажмите "Тест" для каждого провайдера, чтобы убедиться, что подключение работает

## Безопасность

⚠️ **Важно:** Файл `seo-settings.json` содержит секретные ключи. После переноса удалите его с сервера:

```bash
rm seo-settings.json
```

Или добавьте в `.gitignore`:

```
seo-settings.json
```
