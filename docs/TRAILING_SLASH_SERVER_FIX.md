# Trailing Slash Server Fix

Проблема:

- Сейчас URL вида `https://notame.ru/category/anons/` и аналогичные для `tag`, `author`, `archive`, `date` сначала получают `301` на `http://notame.ru/...` на уровне `nginx`.
- После этого запрос уже доходит до приложения и в итоге канонизируется правильно, но появляется лишний `http`-хоп.
- Это и даёт технические дубли для поисковых систем.

Что уже исправлено в приложении:

- canonical на listing-страницах указывает на `https://notame.ru/...` без завершающего `/`
- structured data и внутренние ссылки уже приведены к каноническим URL
- `.htaccess` в `public/.htaccess` уже умеет убирать trailing slash, но этот редирект не успевает сработать, потому что `nginx` отвечает раньше

## Что нужно поменять на сервере

Ниже точный `nginx`-вариант, который нужно применить на уровне виртуального хоста сайта.

### 1. HTTP -> HTTPS non-www

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name notame.ru www.notame.ru;

    return 301 https://notame.ru$request_uri;
}
```

### 2. HTTPS www -> HTTPS non-www

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.notame.ru;

    ssl_certificate     /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    return 301 https://notame.ru$request_uri;
}
```

### 3. Main HTTPS host with direct slash canonicalization

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name notame.ru;

    root /path/to/notamerularavel/public;
    index index.php index.html;

    ssl_certificate     /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    # Убираем завершающий slash сразу на HTTPS non-www,
    # не допуская промежуточного редиректа на http.
    if ($uri ~ "^(.+[^/])/$") {
        return 301 https://notame.ru$1$is_args$args;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

## Ожидаемый результат после правки

Эти URL должны редиректить сразу на HTTPS canonical без промежуточного `http`:

- `https://notame.ru/category/anons/` -> `https://notame.ru/category/anons`
- `https://notame.ru/tag/rassmeshi-menya/` -> `https://notame.ru/tag/rassmeshi-menya`
- `https://notame.ru/author/19/` -> `https://notame.ru/author/19`
- `https://notame.ru/archive/2025/` -> `https://notame.ru/archive/2025`
- `https://notame.ru/archive/2025/12/` -> `https://notame.ru/archive/2025/12`
- `https://notame.ru/date/2025-12-31/` -> `https://notame.ru/date/2025-12-31`

## Как проверить

```bash
curl -I "https://notame.ru/category/anons/"
curl -I "https://notame.ru/tag/rassmeshi-menya/"
curl -I "https://notame.ru/author/19/"
curl -I "https://notame.ru/archive/2025/"
curl -I "https://notame.ru/archive/2025/12/"
curl -I "https://notame.ru/date/2025-12-31/"
```

После фикса в заголовке `Location` должен быть сразу:

```text
https://notame.ru/...
```

а не:

```text
http://notame.ru/...
```

## Короткая формулировка для хостинга

Можно отправить в поддержку буквально так:

```text
Нужно исправить rewrite на nginx для notame.ru:
1. Любой http -> сразу 301 на https://notame.ru$request_uri
2. Любой https://www -> сразу 301 на https://notame.ru$request_uri
3. URL с завершающим slash на основном HTTPS-хосте должны редиректить напрямую на https canonical без промежуточного перехода на http.

Сейчас, например, https://notame.ru/category/anons/ отвечает 301 Location: http://notame.ru/category/anons
Нужно, чтобы сразу было:
Location: https://notame.ru/category/anons
```
