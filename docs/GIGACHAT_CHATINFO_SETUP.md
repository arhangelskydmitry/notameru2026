# Настройка GigaChat и ChatInfo через базу данных

## Быстрый старт

### Через Artisan Tinker

```bash
php artisan tinker
```

```php
use App\Models\Setting;

// Настроить GigaChat
Setting::set('gigachat_client_id', 'ваш-client-id');
Setting::set('gigachat_client_secret', 'ваш-client-secret');
Setting::set('gigachat_scope', 'GIGACHAT_API_PERS');

// Настроить ChatInfo
Setting::set('chatinfo_api_key', 'ваш-api-ключ');

// Установить провайдера
Setting::set('seo_ai_provider', 'gigachat');
```

### Проверка настроек

```php
use App\Models\Setting;

// Проверить GigaChat
echo Setting::get('gigachat_client_id');
echo Setting::get('gigachat_client_secret') ? '***' : 'не установлен';

// Проверить ChatInfo
echo Setting::get('chatinfo_api_key') ? '***' : 'не установлен';
```

## Полная документация

См. файл `docs/GIGACHAT_CHATINFO_SETUP.md` для подробной информации.
