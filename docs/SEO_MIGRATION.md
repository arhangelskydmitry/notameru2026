# Миграция SEO данных из WordPress в Laravel

## 📋 Обзор

Успешно перенесены SEO данные из WordPress плагина **All in One SEO (AIOSEO)** в Laravel приложение.

## 🗂️ Структура

### Таблица `post_seo`

Создана отдельная таблица для хранения SEO метаданных:

```
post_seo
├── id
├── post_id (FK -> wp_posts.ID)
├── seo_title
├── seo_description
├── seo_keywords (JSON)
├── canonical_url
├── robots
├── og_title
├── og_description
├── og_image
├── og_type
├── og_article_section
├── og_article_tags (JSON)
├── twitter_card
├── twitter_title
├── twitter_description
├── twitter_image
├── focus_keywords (JSON)
├── readability_score
├── seo_score
├── created_at
└── updated_at
```

### Модель `PostSeo`

**Расположение:** `app/Models/PostSeo.php`

**Основные методы:**
- `getTitle()` - Получить SEO title с fallback на post_title
- `getDescription()` - Получить SEO description с умным fallback
- `getOgTitle()` - Open Graph title
- `getOgDescription()` - Open Graph description
- `getTwitterTitle()` - Twitter Card title
- `getTwitterDescription()` - Twitter Card description
- `getKeywordsString()` - Keywords как строку

**Связи:**
- `post()` - Принадлежит посту (BelongsTo)

### Связь в модели `Post`

Добавлена связь в `app/Models/WordPress/Post.php`:

```php
public function seo()
{
    return $this->hasOne(\App\Models\PostSeo::class, 'post_id', 'ID');
}
```

## 🔄 Миграция данных

### Команда миграции

```bash
php artisan migrate:seo [--force]
```

**Опции:**
- `--force` - Перезаписать существующие данные без подтверждения

### Процесс миграции

1. **Источник данных:** WordPress мета-поля из таблицы `wp_postmeta`
   - `_aioseo_title`
   - `_aioseo_description`
   - `_aioseo_keywords`
   - `_aioseo_og_title`
   - `_aioseo_og_description`
   - `_aioseo_og_article_section`
   - `_aioseo_og_article_tags`
   - `_aioseo_twitter_title`
   - `_aioseo_twitter_description`

2. **Обработка AIOSEO тегов:**
   - `#post_title` → Заголовок поста
   - `#separator_sa` → ` - `
   - `#site_title` → `notame.ru`
   - `#post_year` → Год публикации
   - `#post_excerpt` → Excerpt поста
   - `#taxonomy_title` → Название категории

3. **Оптимизация:**
   - Обработка порциями (100 постов)
   - Очистка памяти после каждой порции
   - Progress bar для отслеживания прогресса

### Статистика миграции

✅ **Успешно мигрировано:** 2,462 постов из 2,462

## 🔌 Интеграция с SeoService

Обновлен `app/Services/SeoService.php` для использования данных из `post_seo`:

```php
// Автоматическая загрузка SEO данных
$post->load('seo');

// Использование в методах
$seo = $post->seo;
$title = $seo ? $seo->getTitle() : $post->post_title;
```

### Приоритет данных

1. **Title:** post_seo.seo_title → post_title
2. **Description:** post_seo.seo_description → post_excerpt → первые 160 символов контента
3. **Keywords:** post_seo.seo_keywords → теги поста
4. **OG Image:** post_seo.og_image → миниатюра поста
5. **Canonical:** post_seo.canonical_url → route('post', $post->post_name)

## 📊 SEO поля на страницах

Все страницы постов теперь включают:

### Meta Tags
- `<title>` - SEO оптимизированный заголовок
- `<meta name="description">` - Описание
- `<meta name="keywords">` - Ключевые слова
- `<link rel="canonical">` - Канонический URL
- `<meta name="robots">` - Индексация

### Open Graph
- `og:type` - article
- `og:title` - Заголовок для соцсетей
- `og:description` - Описание для соцсетей
- `og:image` - Изображение
- `og:url` - URL страницы
- `og:site_name` - Название сайта
- `og:locale` - ru_RU

### Twitter Card
- `twitter:card` - summary_large_image
- `twitter:title` - Заголовок
- `twitter:description` - Описание
- `twitter:image` - Изображение

### Schema.org (JSON-LD)
- **NewsArticle** - Структурированные данные статьи
- **BreadcrumbList** - Хлебные крошки
- Автор, дата публикации, изображение, категория

## 🚀 Использование

### В контроллере

```php
use App\Services\SeoService;

$post = Post::with('seo')->find($id);
$seoService = app(SeoService::class);
$seo = $seoService->getPageSeo($post);
```

### В Blade шаблонах

```blade
@php
    $seoService = app(\App\Services\SeoService::class);
    $seo = $seoService->getPageSeo($post);
@endphp

@section('title', $seo['title'])
@section('description', $seo['description'])
```

### Прямой доступ к SEO данным

```php
$post = Post::with('seo')->find($id);

if ($post->seo) {
    echo $post->seo->getTitle();
    echo $post->seo->getDescription();
    echo $post->seo->og_image;
}
```

## 🔧 Команды

### Создание таблицы
```bash
php artisan migrate --path=database/migrations/2025_11_06_000006_create_post_seo_table.php
```

### Миграция данных
```bash
# С подтверждением
php artisan migrate:seo

# Без подтверждения
php artisan migrate:seo --force

# С увеличенной памятью
php -d memory_limit=512M artisan migrate:seo --force
```

### Проверка данных
```bash
php artisan tinker
>>> $post = App\Models\WordPress\Post::with('seo')->first();
>>> $post->seo->seo_title
>>> $post->seo->getTitle()
```

## 📈 Статистика заполненности полей

Из 1,708 записей AIOSEO:

| Поле | Заполнено | Процент |
|------|-----------|---------|
| `_aioseo_title` | 480 | 28.1% |
| `_aioseo_description` | 546 | 32.0% |
| `_aioseo_keywords` | 1,617 | 94.7% |
| `_aioseo_og_title` | 443 | 25.9% |
| `_aioseo_og_description` | 445 | 26.1% |

## ✅ Результат

- ✅ Создана таблица `post_seo`
- ✅ Создана модель `PostSeo` с удобными методами
- ✅ Добавлена связь в модель `Post`
- ✅ Создана команда миграции `migrate:seo`
- ✅ Мигрировано 2,462 постов с SEO данными
- ✅ Обработаны AIOSEO теги и шаблоны
- ✅ Обновлен `SeoService` для использования новых данных
- ✅ Все страницы постов используют SEO данные
- ✅ Open Graph, Twitter Card, Schema.org работают корректно

## 🎯 Преимущества

1. **Отдельная таблица** - SEO данные не засоряют основную таблицу постов
2. **Типизация** - Правильные типы данных (JSON для массивов)
3. **Индексы** - Быстрый доступ по post_id
4. **Cascading Delete** - Автоматическое удаление при удалении поста
5. **Умные методы** - Автоматический fallback на данные поста
6. **Легкое обновление** - Можно перезапустить миграцию с --force

## 🔮 Будущие улучшения

- [ ] Moonshine Resource для редактирования SEO данных
- [ ] SEO анализ и рекомендации (метод `analyzeSeoScore()` уже есть)
- [ ] Автоматическая генерация description на основе AI
- [ ] Проверка битых ссылок в canonical URLs
- [ ] История изменений SEO полей

---

**Дата миграции:** 6 ноября 2025  
**Автор:** AI Assistant  
**Статус:** ✅ Завершено




