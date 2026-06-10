# 🔧 ИСПРАВЛЕНИЕ ОШИБКИ 403 FORBIDDEN

## 🔍 ПРИЧИНЫ ОШИБКИ

Ошибка **403 Forbidden** возникает по следующим причинам:

1. ❌ Неправильные права доступа к файлам/папкам
2. ❌ Веб-сервер указывает не на папку `public`
3. ❌ Отсутствует файл `.htaccess` (для Apache)
4. ❌ Неправильный владелец файлов
5. ❌ SELinux блокирует доступ (на CentOS/RHEL)

---

## ✅ РЕШЕНИЕ 1: УСТАНОВКА ПРАВИЛЬНЫХ ПРАВ ДОСТУПА

Подключитесь к серверу по SSH и выполните:

```bash
# Перейдите в директорию проекта
cd /path/to/your/project

# Установите правильные права на папки
find . -type d -exec chmod 755 {} \;

# Установите правильные права на файлы
find . -type f -exec chmod 644 {} \;

# Дайте права на запись для storage и bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Установите правильного владельца (замените www-data на вашего пользователя)
# Для Apache (Ubuntu/Debian):
sudo chown -R www-data:www-data storage bootstrap/cache

# Для Nginx (Ubuntu/Debian):
sudo chown -R www-data:www-data storage bootstrap/cache

# Для Apache (CentOS/RHEL):
sudo chown -R apache:apache storage bootstrap/cache

# Для Nginx (CentOS/RHEL):
sudo chown -R nginx:nginx storage bootstrap/cache
```

---

## ✅ РЕШЕНИЕ 2: НАСТРОЙКА DOCUMENT ROOT

### 🔴 **ВАЖНО:** Веб-сервер должен указывать на папку `public`, а НЕ на корень проекта!

### Для **Apache** (Ubuntu/Debian):

```bash
# Откройте конфигурацию сайта
sudo nano /etc/apache2/sites-available/notameru.conf
```

Добавьте или исправьте:

```apache
<VirtualHost *:80>
    ServerName notame.ru
    ServerAlias www.notame.ru
    
    # ВАЖНО: Указываем на папку public
    DocumentRoot /var/www/notameru/public
    
    <Directory /var/www/notameru/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Логи
    ErrorLog ${APACHE_LOG_DIR}/notameru_error.log
    CustomLog ${APACHE_LOG_DIR}/notameru_access.log combined
</VirtualHost>
```

Активируйте конфигурацию:

```bash
# Включите mod_rewrite
sudo a2enmod rewrite

# Активируйте сайт
sudo a2ensite notameru.conf

# Перезапустите Apache
sudo systemctl restart apache2
```

### Для **Nginx**:

```bash
# Откройте конфигурацию сайта
sudo nano /etc/nginx/sites-available/notameru
```

Добавьте или исправьте:

```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name notame.ru www.notame.ru;
    
    # ВАЖНО: Указываем на папку public
    root /var/www/notameru/public;
    
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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Активируйте конфигурацию:

```bash
# Создайте символическую ссылку
sudo ln -s /etc/nginx/sites-available/notameru /etc/nginx/sites-enabled/

# Проверьте конфигурацию
sudo nginx -t

# Перезапустите Nginx
sudo systemctl restart nginx
```

---

## ✅ РЕШЕНИЕ 3: ПРОВЕРКА .htaccess (для Apache)

Убедитесь что в папке `public` есть файл `.htaccess`:

```bash
cd /var/www/notameru/public
ls -la | grep .htaccess
```

Если файла нет, создайте его:

```bash
nano .htaccess
```

Добавьте содержимое:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

Установите права:

```bash
chmod 644 .htaccess
```

---

## ✅ РЕШЕНИЕ 4: ОТКЛЮЧЕНИЕ SELinux (CentOS/RHEL)

Если используете CentOS/RHEL/AlmaLinux, SELinux может блокировать доступ:

```bash
# Проверьте статус SELinux
sestatus

# Временно отключите (для теста)
sudo setenforce 0

# Если помогло, настройте правильные контексты:
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/notameru/storage(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/notameru/bootstrap/cache(/.*)?"
sudo restorecon -Rv /var/www/notameru/

# Включите обратно
sudo setenforce 1
```

---

## ✅ РЕШЕНИЕ 5: СОЗДАНИЕ СИМВОЛИЧЕСКОЙ ССЫЛКИ STORAGE

```bash
cd /var/www/notameru

# Создайте символическую ссылку
php artisan storage:link

# Проверьте что ссылка создана
ls -la public/ | grep storage
```

Должны увидеть:
```
lrwxrwxrwx 1 www-data www-data   25 Nov  9 15:00 storage -> /var/www/notameru/storage/app/public
```

---

## ✅ РЕШЕНИЕ 6: ПРОВЕРКА PHP-FPM (для Nginx)

```bash
# Проверьте статус PHP-FPM
sudo systemctl status php8.2-fpm

# Если не запущен, запустите
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Проверьте логи
sudo tail -f /var/log/php8.2-fpm.log
```

---

## 🧪 ДИАГНОСТИКА

### Шаг 1: Проверьте права доступа

```bash
cd /var/www/notameru

# Проверьте права
ls -la

# Должно быть примерно так:
# drwxr-xr-x  www-data www-data  public/
# drwxrwxr-x  www-data www-data  storage/
# drwxrwxr-x  www-data www-data  bootstrap/cache/
```

### Шаг 2: Проверьте Document Root

```bash
# Для Apache
apachectl -S | grep DocumentRoot

# Для Nginx
nginx -T | grep "root"
```

Должно показывать: `/var/www/notameru/public`

### Шаг 3: Проверьте логи

```bash
# Apache
sudo tail -f /var/log/apache2/notameru_error.log

# Nginx
sudo tail -f /var/log/nginx/error.log

# PHP
sudo tail -f /var/log/php8.2-fpm.log
```

### Шаг 4: Проверьте index.php

```bash
# Убедитесь что index.php существует и читаем
ls -la /var/www/notameru/public/index.php
cat /var/www/notameru/public/index.php | head -5
```

---

## 📋 ЧЕКЛИСТ БЫСТРОЙ ПРОВЕРКИ

Выполните эти команды последовательно:

```bash
# 1. Перейдите в директорию проекта
cd /var/www/notameru

# 2. Проверьте структуру
ls -la

# 3. Установите права
sudo chown -R www-data:www-data .
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache

# 4. Проверьте .env
cat .env | grep APP_ENV

# 5. Очистите кеш
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 6. Создайте storage link
php artisan storage:link

# 7. Проверьте веб-сервер
# Для Apache:
sudo systemctl restart apache2

# Для Nginx:
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm

# 8. Проверьте логи
sudo tail -30 /var/log/nginx/error.log
# или
sudo tail -30 /var/log/apache2/error.log
```

---

## 🔐 НАСТРОЙКА SSL (HTTPS)

После исправления ошибки 403, установите SSL:

### С помощью Let's Encrypt (бесплатно):

```bash
# Установите Certbot
sudo apt update
sudo apt install certbot

# Для Apache
sudo apt install python3-certbot-apache
sudo certbot --apache -d notame.ru -d www.notame.ru

# Для Nginx
sudo apt install python3-certbot-nginx
sudo certbot --nginx -d notame.ru -d www.notame.ru

# Автопродление
sudo certbot renew --dry-run
```

---

## ❓ ЧАСТО ЗАДАВАЕМЫЕ ВОПРОСЫ

### Q: Где должны быть файлы проекта?
**A:** Рекомендуется: `/var/www/notameru/` или `/home/username/notameru/`

### Q: На какую папку должен указывать веб-сервер?
**A:** ОБЯЗАТЕЛЬНО на `/var/www/notameru/public`, а НЕ на `/var/www/notameru`

### Q: Какие права должны быть?
**A:** 
- Папки: 755
- Файлы: 644
- storage и bootstrap/cache: 775

### Q: Какой должен быть владелец?
**A:** 
- Ubuntu/Debian Apache: `www-data:www-data`
- Ubuntu/Debian Nginx: `www-data:www-data`
- CentOS/RHEL Apache: `apache:apache`
- CentOS/RHEL Nginx: `nginx:nginx`

---

## 🆘 ВСЕ ЕЩЕ НЕ РАБОТАЕТ?

Отправьте мне вывод этих команд:

```bash
# 1. Версии
php -v
nginx -v  # или httpd -v

# 2. Права
ls -la /var/www/notameru/
ls -la /var/www/notameru/public/

# 3. Конфигурация веб-сервера
# Для Nginx:
cat /etc/nginx/sites-available/notameru

# Для Apache:
cat /etc/apache2/sites-available/notameru.conf

# 4. Логи (последние 50 строк)
sudo tail -50 /var/log/nginx/error.log
# или
sudo tail -50 /var/log/apache2/error.log

# 5. Проверка PHP
php artisan --version
```

---

## ✅ ИТОГОВАЯ КОМАНДА (ОДНА СТРОКА)

Скопируйте и выполните (замените путь):

```bash
cd /var/www/notameru && \
sudo chown -R www-data:www-data . && \
sudo find . -type f -exec chmod 644 {} \; && \
sudo find . -type d -exec chmod 755 {} \; && \
sudo chmod -R 775 storage bootstrap/cache && \
php artisan storage:link && \
php artisan config:clear && \
sudo systemctl restart nginx
```

После этого сайт должен заработать! 🚀











