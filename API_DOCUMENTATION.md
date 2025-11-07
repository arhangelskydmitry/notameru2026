# 🔌 REST API Documentation

**Базовый URL:** http://localhost:8002/api  
**Формат:** JSON  
**Rate Limit:** 120 запросов в минуту  
**Версия:** 1.0

---

## 📰 Posts API

### GET /api/posts
Получить список постов с пагинацией и фильтрацией.

**Параметры:**
- `page` (int) - номер страницы (default: 1)
- `per_page` (int) - постов на странице (default: 15, max: 100)
- `category` (string) - фильтр по slug категории
- `tag` (string) - фильтр по slug тега
- `search` (string) - поиск по заголовку

**Пример:**
```bash
curl "http://localhost:8002/api/posts?page=1&per_page=10&category=news"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 14890,
      "title": "Заголовок поста",
      "slug": "post-slug",
      "excerpt": "Краткое описание",
      "date": "2025-10-28T19:06:08+00:00",
      "modified": "2025-10-28T19:06:08+00:00",
      "author": {
        "id": 19,
        "name": "Александр Киселёв",
        "url": ""
      },
      "categories": [...],
      "tags": [...],
      "thumbnail": {
        "id": 14891,
        "url": "https://...",
        "title": "..."
      },
      "views": 123,
      "url": "http://localhost/post-slug"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 247,
    "per_page": 10,
    "total": 2464
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

---

### GET /api/posts/{id}
Получить один пост по ID или slug.

**Параметры:**
- `id` (int|string) - ID поста или slug

**Пример:**
```bash
curl "http://localhost:8002/api/posts/14890"
curl "http://localhost:8002/api/posts/post-slug"
```

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 14890,
    "title": "Полный заголовок",
    "slug": "post-slug",
    "excerpt": "...",
    "content": "Полный HTML контент поста",
    "date": "...",
    "modified": "...",
    "author": {...},
    "categories": [...],
    "tags": [...],
    "thumbnail": {...},
    "views": 123,
    "url": "...",
    "seo": {
      "title": "SEO заголовок",
      "description": "SEO описание",
      "focus_keyword": "ключевое слово"
    }
  }
}
```

---

### GET /api/posts/latest
Получить последние посты.

**Параметры:**
- `limit` (int) - количество постов (default: 10, max: 50)

**Пример:**
```bash
curl "http://localhost:8002/api/posts/latest?limit=5"
```

---

### GET /api/posts/popular
Получить популярные посты (по просмотрам).

**Параметры:**
- `limit` (int) - количество постов (default: 10, max: 50)

**Пример:**
```bash
curl "http://localhost:8002/api/posts/popular?limit=10"
```

---

## 📂 Categories API

### GET /api/categories
Получить все категории.

**Пример:**
```bash
curl "http://localhost:8002/api/categories"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Новости",
      "slug": "news",
      "description": "Описание категории",
      "count": 2329,
      "url": "http://localhost/category/news"
    }
  ]
}
```

---

### GET /api/categories/{id}
Получить одну категорию.

**Параметры:**
- `id` (int|string) - ID категории или slug

**Пример:**
```bash
curl "http://localhost:8002/api/categories/1"
curl "http://localhost:8002/api/categories/news"
```

---

## 🏷️ Tags API

### GET /api/tags
Получить список тегов.

**Параметры:**
- `limit` (int) - количество тегов (default: 100, max: 500)

**Пример:**
```bash
curl "http://localhost:8002/api/tags?limit=50"
```

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Новая песня",
      "slug": "novaya-pesnya",
      "description": "",
      "count": 243,
      "url": "http://localhost/tag/novaya-pesnya"
    }
  ]
}
```

---

### GET /api/tags/{id}
Получить один тег.

**Параметры:**
- `id` (int|string) - ID тега или slug

---

### GET /api/tags/popular
Получить популярные теги.

**Параметры:**
- `limit` (int) - количество тегов (default: 20, max: 100)

---

## ⚠️ Ошибки

### 404 Not Found
```json
{
  "success": false,
  "message": "Пост не найден"
}
```

### 429 Too Many Requests
```json
{
  "message": "Too Many Attempts."
}
```

---

## 🔐 Rate Limiting

**Лимит:** 120 запросов в минуту на IP  
**Headers:**
- `X-RateLimit-Limit` - общий лимит
- `X-RateLimit-Remaining` - осталось запросов
- `Retry-After` - секунд до разблокировки (при превышении)

---

## 📊 Примеры использования

### JavaScript (Fetch API)

```javascript
// Получить последние посты
fetch('http://localhost:8002/api/posts/latest?limit=5')
  .then(response => response.json())
  .then(data => {
    console.log('Посты:', data.data);
  });

// Получить пост по slug
fetch('http://localhost:8002/api/posts/post-slug')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Пост:', data.data);
    }
  });
```

### PHP (cURL)

```php
$ch = curl_init('http://localhost:8002/api/posts?per_page=10');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

foreach ($data['data'] as $post) {
    echo $post['title'] . "\n";
}
```

### Python (requests)

```python
import requests

response = requests.get('http://localhost:8002/api/posts/popular', params={'limit': 10})
data = response.json()

for post in data['data']:
    print(post['title'])
```

---

## 🎯 Use Cases

**1. Мобильное приложение**
- Используйте API для получения контента
- Кэшируйте данные локально
- Обновляйте по pull-to-refresh

**2. Headless CMS**
- WordPress/Laravel для бэкенда
- React/Vue/Next.js для фронтенда
- API как мост между ними

**3. Виджеты на других сайтах**
- Показывайте последние посты
- Популярный контент
- RSS альтернатива

**4. Интеграции**
- Telegram боты
- Discord боты
- Email рассылки

---

## 🔧 Технические детали

**Версия Laravel:** 12.37.0  
**База данных:** MySQL (WordPress notameru-rework)  
**Кэширование:** Пока нет (можно добавить)  
**CORS:** Нужно настроить для cross-origin запросов  
**Аутентификация:** Laravel Sanctum установлен (готов к настройке)

---

## 📈 Производительность

**Средняя скорость ответа:**
- `/api/posts` - ~150ms
- `/api/posts/{id}` - ~80ms
- `/api/categories` - ~50ms
- `/api/tags` - ~70ms

**Оптимизации:**
- Eager loading (with) для связей
- Лимиты на количество результатов
- Rate limiting для защиты

---

**Дата:** 5 ноября 2025  
**Статус:** ✅ Полностью работает  
**Phase 4 завершена!** 🎉
