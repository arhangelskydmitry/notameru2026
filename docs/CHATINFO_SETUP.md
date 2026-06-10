# Настройка ChatInfo API

## Описание

ChatInfo - российский сервис, предоставляющий доступ к GPT-4o через совместимый с ChatGPT API эндпоинт. Преимущества:
- Оплата из России и стран СНГ
- Использует модель GPT-4o (более мощная, чем GPT-4o-mini)
- Не требует VPN
- Полностью совместим с OpenAI API

## Настройка

### 1. Получение API ключа

1. Зарегистрируйтесь на [chatinfo.ru](https://chatinfo.ru)
2. Подключите тариф "Престиж" (API доступен только на этом тарифе)
3. Получите API ключ на странице [https://chatinfo.ru/subscription](https://chatinfo.ru/subscription)

### 2. Сохранение ключа

Ключ можно сохранить двумя способами:

#### Способ 1: В базе данных (рекомендуется)
```bash
php artisan tinker
```
```php
use App\Models\Setting;
Setting::set('chatinfo_api_key', 'ваш-api-ключ');
```

#### Способ 2: В .env файле
```env
CHATINFO_API_KEY=ваш-api-ключ
```

### 3. Проверка подключения

```bash
php artisan tinker
```
```php
use App\Services\ChatInfoService;
$service = new ChatInfoService();
$service->testConnection(); // Должно вернуть true
```

## Использование

### В команде генерации новостей

```bash
# Использование ChatInfo для рерайта
php artisan news:auto-generate --theme=music --provider=chatinfo --limit=10 --author=1
```

### В SeoGeneratorService

ChatInfo автоматически доступен как провайдер:

```php
use App\Services\SeoGeneratorService;

$seoService = new SeoGeneratorService();
$seoData = $seoService->generateSeoData($title, $excerpt, $content, 'chatinfo');
```

### В админ-панели

1. Перейдите в раздел "SEO AI" (`/notaadmin/seo-settings`)
2. Выберите "ChatInfo (GPT-4o)" как предпочтительного провайдера
3. Нажмите "Тест ChatInfo" для проверки подключения
4. Сохраните настройки

## API особенности

- **Эндпоинт**: `https://chatinfo.ru/v1/chat/completions`
- **Модель**: `gpt-4o`
- **Формат**: Совместим с OpenAI ChatGPT API
- **Цена**: 1 API Unit = 2 обычных запроса
- **Лимит**: Максимальная длина запроса - 10 000 символов
- **Частота**: До 1500 запросов в минуту

## Преимущества ChatInfo

1. **Российский сервис** - оплата из России без проблем
2. **GPT-4o** - более мощная модель, чем GPT-4o-mini
3. **Без VPN** - работает без дополнительных настроек
4. **Совместимость** - можно использовать те же библиотеки, что и для OpenAI

## Сравнение провайдеров

| Провайдер | Модель | Качество | Оплата | VPN |
|-----------|--------|----------|--------|-----|
| GigaChat | GigaChat | Высокое (для русского) | Россия | Нет |
| ChatInfo | GPT-4o | Очень высокое | Россия | Нет |
| OpenAI | GPT-4o-mini | Высокое | Международная | Может потребоваться |

## Документация

Полная документация API: [https://chatinfo.ru/api-docs](https://chatinfo.ru/api-docs)
