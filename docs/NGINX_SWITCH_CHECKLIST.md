# Nginx Switch Checklist

Документ для момента, когда сайт снова будет переведен на схему с `nginx`.

Цель: быстро проверить, какие проблемы уже закрыты на уровне проекта, а какие нужно добить только на стороне веб-сервера, и сразу отправить в техподдержку точную просьбу.

## Что уже исправлено в проекте

- canonical URL на ключевых страницах приведены к `https://notame.ru/...`
- главная и внутренние страницы теперь отдают Schema.org JSON-LD
- trust-страницы доступны и включены в sitemap:
  - `https://notame.ru/editorial`
  - `https://notame.ru/advertising`
  - `https://notame.ru/privacy`
- `llms.txt` и `llms-full.txt` опубликованы в корне сайта
- legacy redirect для URL Соболева с `%E2%84%961` исправлен
- trailing slash исправлен на Apache-слое через `.htaccess`
- HTML главной и listing-страниц облегчен за счет более подходящих размеров превью (`small` / `medium` вместо `large`)
- sitemap, robots и feeds уже рабочие и валидные

## Что остается проверить после переключения на nginx

### 1. Redirect layer

Проверить, что `nginx` не добавляет лишние хопы и не ломает canonicalization:

- `http://notame.ru/` -> сразу `301` на `https://notame.ru/`
- `http://www.notame.ru/` -> сразу `301` на `https://notame.ru/`
- `https://www.notame.ru/` -> сразу `301` на `https://notame.ru/`
- URL со слешем на конце должны сразу редиректить на финальный canonical без промежуточного `http`

Особенно проверить:

- `https://notame.ru/category/anons/`
- `https://notame.ru/tag/rassmeshi-menya/`
- `https://notame.ru/author/19/`
- `https://notame.ru/archive/2025/`
- `https://notame.ru/archive/2025/12/`
- `https://notame.ru/date/2025-12-31/`

Ожидаемое поведение:

- один прямой `301`
- без промежуточного `http://...`
- без `:443` в `Location`

### 2. Static resource caching

Сейчас аудит по “объему ресурсов” упирается не только в размеры картинок, но и в серверные заголовки для статики.

После переключения на `nginx` нужно проверить, что статические файлы отдаются с публичным кешированием:

- `*.js`
- `*.css`
- `*.svg`
- `*.png`
- `*.jpg`
- `*.jpeg`
- `*.webp`
- `favicon.*`

Желаемое поведение:

- `Cache-Control: public, max-age=31536000, immutable` для версионируемой статики
- как минимум `public, max-age=2592000` или дольше для обычной статики

Проверить на примерах:

- `https://notame.ru/js/jquery.marquee.min.js`
- `https://notame.ru/images/logo.png`
- `https://notame.ru/favicon.svg`
- любой `https://notame.ru/imgnews/...webp`

### 3. Compression

Проверить, что `nginx` отдает сжатие для текстовых ресурсов:

- HTML
- CSS
- JS
- XML
- TXT

Желательно включить `gzip` или `brotli` для:

- `text/html`
- `text/plain`
- `text/xml`
- `application/xml`
- `application/rss+xml`
- `application/javascript`
- `text/css`
- `application/json`

### 4. Public page caching

Главная страница и публичные listing-страницы не должны выглядеть как полностью приватные ответы для анонимного пользователя.

Сейчас это может проявляться как:

- `Cache-Control: private, must-revalidate`

После переключения на `nginx` стоит проверить, не можно ли отдавать публичные страницы более дружественно для кеширования на edge/browser-уровне, если это не конфликтует с приложением.

## Команды для быстрой проверки

```bash
curl -I "http://notame.ru/"
curl -I "http://www.notame.ru/"
curl -I "https://www.notame.ru/"
curl -I "https://notame.ru/category/anons/"
curl -I "https://notame.ru/tag/rassmeshi-menya/"
curl -I "https://notame.ru/author/19/"
curl -I "https://notame.ru/archive/2025/"
curl -I "https://notame.ru/date/2025-12-31/"

curl -I "https://notame.ru/js/jquery.marquee.min.js"
curl -I "https://notame.ru/images/logo.png"
curl -I "https://notame.ru/favicon.svg"
```

## Короткая просьба в техподдержку

Можно отправить вот такой текст:

```text
После переключения сайта notame.ru на nginx нужно проверить и поправить серверную часть в двух местах.

1. Редиректы:
- любой http должен сразу отдавать 301 на https://notame.ru$request_uri
- любой https://www должен сразу отдавать 301 на https://notame.ru$request_uri
- URL со слешем на конце должны сразу редиректить на финальный https canonical без промежуточного перехода на http
- в заголовках Location не должно быть :443

2. Статические ресурсы:
- для js/css/svg/png/jpg/webp нужно включить публичное кеширование через Cache-Control
- желательно long max-age для статики
- включить gzip/brotli для html/js/css/xml/txt

На уровне приложения основные правки уже сделаны, остались именно серверные настройки nginx.
```

## Что проверить сразу после переключения

- главная открывается без 5xx
- `llms.txt` и `llms-full.txt` доступны
- `sitemap.xml` доступен
- `robots.txt` доступен
- `feed`, `feed/rambler`, `feed/zen1`, `yandex/news`, `yandex/turbo` доступны
- Schema.org на главной не исчезла
- trailing slash и `www/http` канонизируются правильно
