# Nota Miru — Mac App API

Базовый URL: `https://notame.ru/api/mac/v1`

Тонкий REST-фасад над существующими Laravel-моделями (`WordPress\Post`, `PressCard`, `SeoGeneratorService` и т.д.). Mac-клиент работает только с JSON, без прямого доступа к MySQL.

## Аутентификация

### POST `/auth/login`

```json
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "MacBook Pro"
}
```

Ответ `200`:

```json
{
  "token": "nm_mac_…",
  "user": {
    "id": 1,
    "name": "Иван Иванов",
    "email": "user@example.com",
    "position": "Редактор",
    "role": "editor",
    "role_label": "Редактор",
    "is_super_admin": false,
    "is_editor": true
  }
}
```

Дальнейшие запросы: заголовок `Authorization: Bearer {token}`.

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/auth/me` | Текущий пользователь |
| POST | `/auth/logout` | Отозвать токен |

Токены хранятся в таблице `mac_app_tokens` (Laravel DB), срок — 90 дней.

## Статьи

| Метод | Путь | Права |
|-------|------|-------|
| GET | `/posts?status=draft&q=поиск` | Свои; редактор/админ — все |
| GET | `/posts/{id}` | По `canEditPost` |
| POST | `/posts` | Создание |
| PUT | `/posts/{id}` | Редактирование + SEO |
| PATCH | `/posts/{id}/status` | Смена статуса |
| POST | `/posts/seo/generate` | SEO через `SeoGeneratorService` |
| POST | `/posts/summarize` | Выжимка через `ArticleSummarizeService` |
| GET | `/categories` | Список категорий |

Статусы WordPress: `draft`, `publish`, `pending`, `future`.

### POST `/posts`

```json
{
  "title": "Заголовок",
  "content": "<p>HTML</p>",
  "excerpt": "",
  "status": "draft"
}
```

### PUT `/posts/{id}`

```json
{
  "title": "…",
  "content": "…",
  "excerpt": "…",
  "status": "publish",
  "seo": {
    "seo_title": "…",
    "seo_description": "…",
    "focus_keyword": "…",
    "og_title": "…",
    "og_description": "…"
  }
}
```

## Пресс-карты

| Метод | Путь | Права |
|-------|------|-------|
| GET | `/press-cards` | Редактор/админ |
| POST | `/press-cards` | Редактор/админ |
| GET | `/press-cards/journalists` | Список журналистов |
| GET | `/press-cards/{id}/pdf` | PDF (как в веб-админке) |

## Пользователи

| Метод | Путь | Права |
|-------|------|-------|
| GET | `/users` | Редактор/админ |
| PATCH | `/users/{id}/active` | `{ "active": true }` |

## Деплой

1. Миграция: `php artisan migrate` (таблица `mac_app_tokens`)
2. Убедиться, что `routes/api.php` подключён (префикс `/api`)
3. Mac-клиент: `clients/macos/` (bundle `ru.factory-media.NotaMiru`)

```bash
cd clients/macos && ./build.sh
```
