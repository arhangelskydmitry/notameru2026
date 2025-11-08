# 🚀 Инструкция по развертыванию Notame.ru на сервере

## 📋 Предварительные требования

- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js 16+
- Git

---

## 🔧 Шаг 1: Клонирование репозитория

```bash
cd /path/to/your/web/directory
git clone https://github.com/arhangelskydmitry/notameru2026.git notame.pro
cd notame.pro
```

---

## 🗄️ Шаг 2: Создание новой базы данных

Создайте новую пустую базу данных для Laravel:

```sql
CREATE DATABASE iq210692_notamerurework CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Важно:** Старая WordPress база `iq210692_notame` остается нетронутой!

---

## ⚙️ Шаг 3: Настройка окружения

Скопируйте production конфигурацию:

```bash
cp .env.production .env
```

Проверьте настройки в `.env`:

```bash
# Новая Laravel база
DB_DATABASE=iq210692_notamerurework
DB_USERNAME=iq210692_notame
DB_PASSWORD=Yrf,ysq123

# Старая WordPress база (для миграции)
WORDPRESS_DB_DATABASE=iq210692_notame
WORDPRESS_DB_USERNAME=iq210692_notame
WORDPRESS_DB_PASSWORD=Yrf,ysq123
```

---

## 📦 Шаг 4: Установка зависимостей

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

---

## 🔄 Шаг 5: Миграция данных из WordPress

Выполните миграции и перенос данных:

```bash
# Создаем таблицы Laravel
php artisan migrate --force

# Мигрируем данные из WordPress
php artisan migrate:wordpress

# Мигрируем SEO данные
php artisan migrate:seo
```

**Это займет ~5-10 минут** (переносится 9,775 записей)

---

## 🖼️ Шаг 6: Настройка изображений

Убедитесь, что папка с изображениями доступна:

```bash
chmod -R 755 public/imgnews
chmod -R 755 storage
chown -R www-data:www-data public/imgnews
chown -R www-data:www-data storage
```

---

## 🔐 Шаг 7: Настройка прав доступа

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🌐 Шаг 8: Настройка веб-сервера

### Apache (.htaccess уже настроен)

Document Root должен указывать на `/path/to/notame.pro/public`

### Nginx (пример конфигурации)

```nginx
server {
    listen 80;
    server_name notame.pro www.notame.pro;
    root /path/to/notame.pro/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🔄 Шаг 9: Оптимизация для production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 👤 Шаг 10: Создание администратора Moonshine

```bash
php artisan moonshine:user
```

Введите:
- **Name:** Admin
- **Email:** admin@notame.pro
- **Password:** (ваш пароль)

---

## ✅ Шаг 11: Проверка работы

1. **Frontend:** https://notame.pro
2. **Админка:** https://notame.pro/admin
3. **API:** https://notame.pro/api/posts

---

## 🔧 Шаг 12: Настройка CRON (опционально)

Для автоматических задач добавьте в crontab:

```bash
* * * * * cd /path/to/notame.pro && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Что будет перенесено:

✅ **2,462 поста** с полным контентом  
✅ **134 категории** с иерархией  
✅ **65 тегов**  
✅ **31 автор**  
✅ **SEO метаданные** (title, description, keywords, OG tags)  
✅ **5,180+ изображений**  
✅ **Комментарии** (если есть)  
✅ **Меню навигации**  

---

## 🚨 Важные замечания:

1. **WordPress база не удаляется** - она остается для возможного отката
2. **Новая база** `iq210692_notamerurework` используется для Laravel
3. **Изображения** уже загружены в `public/imgnews/`
4. **SEO URLs** сохранены (старые ссылки работают)
5. **Редиректы** настроены автоматически

---

## 📝 Локальная разработка

Для локальной работы используйте `.env.local`:

```bash
cp .env.local .env
php artisan serve --port=8002
```

---

## 🆘 Troubleshooting

### Ошибка подключения к БД

```bash
php artisan config:clear
php artisan cache:clear
```

### Проблемы с правами

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Изображения не отображаются

```bash
php artisan storage:link
chmod -R 755 public/imgnews
```

---

## 📞 Поддержка

При возникновении проблем проверьте логи:

```bash
tail -f storage/logs/laravel.log
```

---

**Готово! 🎉 Сайт должен работать на https://notame.pro**

