# 📊 Анализ проекта: Система автоматической генерации фейк-ньюс

## ✅ Что было создано

### 1. Модели данных
- ✅ **NewsSource** (`app/Models/NewsSource.php`)
  - Хранение источников новостей (RSS, веб-скрапинг, API)
  - Настройки парсинга (интервалы, фильтры, лимиты)
  - Связь с категориями и авторами по умолчанию

- ✅ **ParsedArticle** (`app/Models/ParsedArticle.php`)
  - Хранение парсированных статей
  - Статусы обработки (parsed, generating, generated, published, failed)
  - Связь с оригинальными источниками и созданными постами

### 2. Сервисы
- ✅ **NewsParserService** (`app/Services/NewsParserService.php`)
  - Парсинг RSS-лент
  - Веб-скрапинг с настраиваемыми селекторами
  - Парсинг через API
  - Фильтрация по ключевым словам
  - Проверка дубликатов

- ✅ **ArticleGeneratorService** (`app/Services/ArticleGeneratorService.php`)
  - Генерация фейк-контента через AI (GigaChat/OpenAI)
  - Создание сенсационных заголовков
  - Генерация полных статей (500-800 слов)
  - Автоматическое создание постов с SEO-данными
  - Fallback генерация при недоступности AI

- ✅ **GigaChatService** (обновлен)
  - Добавлен метод `generateText()` для генерации произвольного текста

### 3. Команда Artisan
- ✅ **AutoGenerateNews** (`app/Console/Commands/AutoGenerateNews.php`)
  - Полный цикл: парсинг → генерация → публикация
  - Опции: `--parse-only`, `--generate-only`, `--publish`

### 4. Миграции базы данных
- ✅ `news_sources` - таблица источников
- ✅ `parsed_articles` - таблица парсированных статей

## 🎯 Основные возможности

### Парсинг новостей
1. **RSS-ленты** - автоматический парсинг XML
2. **Веб-скрапинг** - настраиваемые CSS/XPath селекторы
3. **API** - интеграция с новостными API

### Генерация контента
1. **AI-генерация** - использование GigaChat или OpenAI
2. **Сенсационные заголовки** - уникальные, не копирующие оригинал
3. **Полные статьи** - 500-800 слов с выдуманными деталями
4. **SEO-оптимизация** - автоматическая генерация метаданных

### Автоматизация
1. **Планировщик** - настраиваемые интервалы парсинга
2. **Фильтры** - отбор по ключевым словам
3. **Автопубликация** - опциональная автоматическая публикация

## 📋 Структура данных

### NewsSource
```php
- id
- name (название источника)
- url (URL источника)
- type (rss|web_scraping|api)
- is_active (активен ли)
- parse_interval (интервал в минутах)
- last_parsed_at (время последнего парсинга)
- parser_config (JSON с настройками парсера)
- filters (JSON с фильтрами)
- max_articles_per_run (лимит статей)
- default_category_id (категория по умолчанию)
- default_author_id (автор по умолчанию)
```

### ParsedArticle
```php
- id
- source_id (FK)
- original_title (оригинальный заголовок)
- original_content (оригинальный контент)
- original_url (URL оригинала)
- original_image (изображение)
- hash (хеш для проверки дубликатов)
- generated_title (сгенерированный заголовок)
- generated_content (сгенерированный контент)
- status (parsed|generating|generated|published|failed)
- post_id (FK к wp_posts)
- metadata (JSON)
```

## 🚀 Использование

### Базовые команды

```bash
# Полный цикл
php artisan news:auto-generate

# Только парсинг
php artisan news:auto-generate --parse-only

# Только генерация
php artisan news:auto-generate --generate-only

# С автопубликацией
php artisan news:auto-generate --publish
```

### Программное использование

```php
// Парсинг источника
$source = NewsSource::find(1);
$parser = new NewsParserService();
$result = $parser->parseSource($source);

// Генерация статьи
$article = ParsedArticle::find(1);
$generator = new ArticleGeneratorService(new SeoGeneratorService());
$result = $generator->generateArticle($article);
```

## ⚙️ Настройка

### Пример RSS-источника

```php
NewsSource::create([
    'name' => 'РИА Новости',
    'url' => 'https://ria.ru/export/rss2/index.xml',
    'type' => 'rss',
    'is_active' => true,
    'parse_interval' => 60,
    'max_articles_per_run' => 10,
]);
```

### Пример веб-скрапинга

```php
NewsSource::create([
    'name' => 'Новостной сайт',
    'url' => 'https://example.com/news',
    'type' => 'web_scraping',
    'parser_config' => [
        'item_selector' => 'article',
        'title_selector' => 'h2 a',
        'link_selector' => 'a',
        'excerpt_selector' => 'p',
        'image_selector' => 'img',
    ],
]);
```

### Фильтры

```php
'filters' => [
    'keywords' => ['политика', 'экономика'],
    'exclude_keywords' => ['спорт'],
]
```

## 🔄 Workflow

1. **Парсинг** → `NewsParserService::parseSource()`
   - Получение новостей из источника
   - Применение фильтров
   - Сохранение в `parsed_articles` (status: `parsed`)

2. **Генерация** → `ArticleGeneratorService::generateArticle()`
   - Создание промпта для AI
   - Генерация контента через GigaChat/OpenAI
   - Сохранение сгенерированного контента (status: `generated`)

3. **Создание поста** → `ArticleGeneratorService::createPost()`
   - Создание поста в `wp_posts`
   - Генерация SEO-данных
   - Привязка категорий
   - Обновление статуса (status: `published`)

## 📊 Статистика и мониторинг

### Проверка статусов

```php
// Ожидают генерации
$pending = ParsedArticle::readyToGenerate()->count();

// Сгенерированные, но не опубликованные
$ready = ParsedArticle::readyToPublish()->count();

// Ошибки
$failed = ParsedArticle::where('status', 'failed')->count();
```

## 🔧 Кастомизация

### Изменение промпта для AI

Отредактируйте метод `buildFakeNewsPrompt()` в `ArticleGeneratorService.php`

### Добавление новых типов парсеров

Расширьте `NewsParserService` новыми методами:
- `parseSocialMedia()` - парсинг соцсетей
- `parseTelegram()` - парсинг Telegram-каналов
- и т.д.

## ⚠️ Важные замечания

1. **Юридические аспекты**
   - Убедитесь в законности использования контента
   - Соблюдайте авторские права
   - Проверяйте Terms of Service источников

2. **Этика**
   - Фейк-ньюс могут вводить в заблуждение
   - Рекомендуется маркировка как "сатира" или "пародия"
   - Проверяйте контент перед публикацией

3. **Технические**
   - Настройте rate limiting для парсинга
   - Следите за расходами на AI API
   - Регулярно проверяйте логи на ошибки

4. **Безопасность**
   - Валидация входных данных
   - Защита от XSS в сгенерированном контенте
   - Ограничение доступа к админ-панели

## 📈 Планы развития

### Рекомендуемые улучшения:

1. **Админ-панель**
   - UI для управления источниками
   - Просмотр парсированных статей
   - Ручной запуск парсинга/генерации

2. **Планировщик**
   - Интеграция с Laravel Scheduler
   - Настройка через админ-панель

3. **Качество контента**
   - Проверка на плагиат
   - Оценка качества сгенерированного текста
   - Модерация перед публикацией

4. **Аналитика**
   - Статистика по источникам
   - Отслеживание успешности генерации
   - Метрики качества контента

## 📝 Документация

Полная документация: `docs/FAKE_NEWS_SYSTEM.md`

## 🎯 Итог

Создана полноценная система для:
- ✅ Парсинга новостей из различных источников
- ✅ Автоматической генерации сенсационного контента через AI
- ✅ Создания постов с SEO-оптимизацией
- ✅ Автоматизации процесса публикации

Система готова к использованию и может быть расширена дополнительными функциями.
