# 🎵 Нота Миру - CMS для новостного портала

[![Version](https://img.shields.io/badge/version-2.0-blue.svg)](https://github.com/yourusername/notamerularavel)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Современная система управления контентом для новостного портала о музыке, культуре и шоу-бизнесе.**

## ✨ Основные возможности

### 📝 Управление контентом
- **Многофункциональный редактор** (TinyMCE) с поддержкой медиа
- **Система ролей**: Супер-админ, Редактор, Автор
- **Привязка к категориям и тегам**
- **Выбор даты и времени публикации**
- **Alt и Title атрибуты** для изображений
- **SEO-оптимизация** для каждой статьи

### 🔐 Система ролей и прав

#### Супер-админ
- Полный доступ ко всем функциям
- Управление пользователями и ролями
- Настройка системы

#### Редактор
- Редактирование всех статей
- Модерация контента
- Управление категориями и тегами

#### Автор
- Создание и редактирование только своих статей
- Ограниченный доступ к интерфейсу
- Поле "Автор" только для чтения

### 📱 Мобильная оптимизация
- ✅ Адаптивный дизайн для всех устройств
- ✅ Оптимизированные шрифты (H1: 18px, H2: 16px)
- ✅ Нет горизонтального скролла
- ✅ Адаптивные изображения и таблицы
- ✅ Мобильное меню с правильным z-index

### 🔍 SEO и микроразметка
- **Open Graph** для всех типов страниц
- **Twitter Cards** интеграция
- **Schema.org** микроразметка (WebSite, Organization, NewsArticle)
- **Уникальные meta descriptions**
- **Canonical URLs**
- **robots.txt** настроен
- **XML Sitemap** с автообновлением

### 📡 RSS фиды
- **Яндекс Новости** - оптимизированный формат
- **Яндекс Дзен** - с полным контентом
- **Яндекс Турбо** - быстрые страницы

### 📊 Аналитика и статистика
- Счетчик просмотров статей
- Статистика авторов
- Популярные статьи
- Журнал активности (ActivityLog)

## 🚀 Быстрый старт

### Требования
- PHP 8.3+
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ (опционально, для сборки фронтенда)

### Яндекс Метрика и Вебмастер

Проект интегрирован с Яндекс Метрикой и Яндекс Вебмастером. Для настройки добавьте следующие переменные в `.env` файл:

```env
# Яндекс Метрика
YANDEX_METRIKA_ID=XXXXXXXX
YANDEX_METRIKA_TOKEN=ваш_oauth_token_для_api

# Яндекс Вебмастер
YANDEX_WEBMASTER_VERIFICATION=XXXXXXXXXXXXXXXXXXXX
YANDEX_WEBMASTER_TOKEN=ваш_oauth_token_для_api
YANDEX_WEBMASTER_HOST_ID=https:example.com:443

# WordPress Database (для моделей WordPress)
WP_DB_CONNECTION=mysql
WP_DB_HOST=127.0.0.1
WP_DB_PORT=3306
WP_DB_DATABASE=wordpress
WP_DB_USERNAME=root
WP_DB_PASSWORD=
```

### Настройка WordPress базы данных:

Проект использует модели WordPress для работы с пользователями и контентом. Необходимо настроить подключение к базе данных WordPress:

1. **Установите переменные окружения** в `.env` файле (см. выше)
2. **Убедитесь, что MySQL сервер запущен**
3. **База данных WordPress существует** и содержит таблицы (wp_users, wp_posts, etc.)
4. **Пользователь имеет права доступа** к базе данных

Если у вас нет WordPress базы данных, создайте её или импортируйте существующий дамп WordPress.

### Получение ID для Яндекс Метрики:
1. Перейдите на https://metrika.yandex.ru/
2. Создайте новый счетчик
3. Скопируйте ID счетчика (число в коде)

### Получение кода верификации для Яндекс Вебмастера:
**Если сайт уже добавлен в Яндекс.Вебмастер:**
- Мета-тег не требуется! Оставьте поле пустым.

**Если сайт еще не добавлен:**
1. Перейдите на https://webmaster.yandex.ru/
2. Добавьте сайт
3. Выберите метод верификации "Мета-тег"
4. Скопируйте код верификации

### Управление настройками в админке:
1. Зайдите в админ-панель как суперадмин
2. Перейдите в раздел "Яндекс сервисы"
3. Введите ID счетчика и API токен для Яндекс Метрики
4. Настройте API токен и Host ID для Яндекс Вебмастер
5. Код верификации оставьте пустым (если сайт уже верифицирован)
6. Сохраните настройки

## API Использование

### Яндекс Метрика API
```php
use App\Services\YandexMetrikaService;

$metrika = app(YandexMetrikaService::class);

// Получить статистику посещений за последние 7 дней
$stats = $metrika->getVisitsStatistics('7daysAgo', 'today');

// Получить популярные страницы
$pages = $metrika->getPopularPages('30daysAgo', 'today', 10);

// Получить источники трафика
$sources = $metrika->getTrafficSources();
```

### Яндекс Вебмастер API
```php
use App\Services\YandexWebmasterService;

$webmaster = app(YandexWebmasterService::class);

// Получить статистику индексации
$indexing = $webmaster->getIndexingStats();

// Получить популярные запросы
$queries = $webmaster->getPopularQueries('7daysAgo', 'today', 20);

// Получить позиции в поиске
$positions = $webmaster->getSearchPositions('ваш запрос');
```

## Установка

```bash
# Клонируйте репозиторий
git clone https://github.com/yourusername/notamerularavel.git
cd notamerularavel

# Установите зависимости
composer install

# Скопируйте .env файл
cp .env.example .env

# Сгенерируйте ключ приложения
php artisan key:generate

# Настройте базу данных в .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=notameru
# DB_USERNAME=root
# DB_PASSWORD=

# Выполните миграции
php artisan migrate

# Заполните начальные данные (роли, права)
php artisan db:seed

# Запустите сервер
php artisan serve
```

Откройте браузер: `http://localhost:8000`

## 📖 Документация

### Структура проекта

```
notamerularavel/
├── app/
│   ├── Console/Commands/     # Artisan команды
│   ├── Helpers/              # Вспомогательные функции
│   ├── Http/
│   │   ├── Controllers/      # Контроллеры
│   │   └── Middleware/       # Middleware
│   ├── Models/               # Eloquent модели
│   │   └── WordPress/        # WordPress модели
│   └── Services/             # Бизнес-логика
├── config/                   # Конфигурация
├── database/
│   ├── migrations/           # Миграции БД
│   └── seeders/              # Начальные данные
├── docs/                     # Документация (включая TROUBLESHOOTING.md)
├── public/                   # Публичные файлы
│   └── imgnews/              # Загруженные изображения
├── resources/
│   └── views/                # Blade шаблоны
│       ├── admin/            # Админ-панель
│       └── frontend/         # Фронтенд
├── routes/
│   ├── web.php               # Web маршруты
│   └── api.php               # API маршруты
└── storage/                  # Хранилище
```

### Основные маршруты

```
/                           - Главная страница
/category/{slug}            - Страница категории
/tag/{slug}                 - Страница тега
/author/{id}                - Страница автора
/{slug}                     - Просмотр статьи

/notaadmin                  - Админ-панель
/notaadmin/login            - Вход в админку
/notaadmin/posts            - Управление статьями
/notaadmin/posts/create     - Создание статьи

/sitemap.xml                - XML Sitemap
/feed/zen1                  - Яндекс Дзен RSS
/yandex/news                - Яндекс Новости RSS
/yandex/turbo               - Яндекс Турбо RSS
```

### Команды Artisan

```bash
# Очистка всех кешей
php artisan optimize:clear

# Генерация sitemap
php artisan sitemap:generate

# Миграция данных из WordPress
php artisan migrate:wordpress

# Оптимизация изображений
php artisan images:optimize
```

## 🔧 Конфигурация

### Переменные окружения (.env)

```env
APP_NAME="Нота Миру"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://notame.ru

DB_CONNECTION=mysql
DB_DATABASE=notameru

# Telegram интеграция
TELEGRAM_BOT_TOKEN=your_token
TELEGRAM_CHAT_ID=your_chat_id

# VK интеграция
VK_ACCESS_TOKEN=your_token
VK_GROUP_ID=your_group_id
```

## 📚 Дополнительная документация

- [📖 SEO Migration Guide](docs/SEO_MIGRATION.md)
- [📖 Autoposting Guide](docs/AUTOPOSTING_COMPLETE_GUIDE.md)
- [📖 Image Alt/Title Guide](docs/IMAGE_ALT_TITLE_GUIDE.md)
- [📖 Deployment Guide](DEPLOYMENT_GUIDE.md)
- [📖 Database Setup](DATABASE_DEPLOYMENT.md)

## 🛠️ Технологии

### Backend
- **Laravel 11.x** - PHP фреймворк
- **MySQL 8.0** - База данных
- **Redis** - Кеширование (опционально)

### Frontend
- **Blade** - Шаблонизатор
- **Bootstrap 5** - CSS фреймворк
- **jQuery** - JavaScript библиотека
- **TinyMCE** - WYSIWYG редактор

### Интеграции
- **Telegram Bot API** - Автопостинг
- **VK API** - Публикация в VK
- **Яндекс RSS** - Дзен, Новости, Турбо

## 🔒 Безопасность

- ✅ Защита от CSRF атак
- ✅ XSS фильтрация
- ✅ SQL Injection защита (Eloquent ORM)
- ✅ Проверка прав доступа на уровне контроллеров
- ✅ Middleware аутентификации
- ✅ Защищенные маршруты

## 📈 Производительность

- **Кеширование**: Sitemap, статистика, популярные статьи
- **Ленивая загрузка**: Бесконечный скролл для списков
- **Оптимизация изображений**: WebP, сжатие
- **Минимизация запросов**: Eager loading в Eloquent

## 🐛 Исправленные проблемы (v2.0)

- ✅ ActivityLog работает корректно с авторизацией
- ✅ Sitemap regeneration без ошибок 500
- ✅ Проверки авторизации во всех контроллерах
- ✅ Мобильное меню полностью выезжает (z-index исправлен)
- ✅ Календарь адаптирован для мобильных устройств
- ✅ Нет горизонтального скролла на статьях
- ✅ Дата публикации сохраняется корректно

## 🤝 Вклад в проект

Contributions are welcome! Пожалуйста:

1. Fork репозиторий
2. Создайте feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit изменения (`git commit -m 'Add some AmazingFeature'`)
4. Push в branch (`git push origin feature/AmazingFeature`)
5. Откройте Pull Request

## 📝 Changelog

### Version 2.0 (2025-11-13)

#### ✨ Новые возможности
- Система ролей и прав доступа (Супер-админ, Редактор, Автор)
- Привязка статей к тегам в админ-панели
- Выбор даты и времени публикации
- Alt и Title атрибуты для изображений в редакторе
- Полная мобильная оптимизация

#### 🔧 Улучшения
- Авторы видят только свои статьи
- Скрыты фильтры и колонка автора для авторов
- Поле автора только для чтения для авторов
- Уникальные meta descriptions для страниц авторов
- Open Graph для категорий, тегов, авторов
- RSS фиды оптимизированы (Яндекс Новости)

#### 🐛 Исправления
- ActivityLog корректно работает с авторизацией
- Sitemap regeneration работает без ошибок
- Проверки авторизации во всех методах контроллера
- Мобильное меню полностью выезжает
- Календарь адаптирован для мобильных
- Нет горизонтального скролла

## 📄 Лицензия

Этот проект лицензирован под MIT License - смотрите файл [LICENSE](LICENSE) для деталей.

## 👨‍💻 Автор

**Нота Миру Team**

- Website: [https://notame.ru](https://notame.ru)
- GitHub: [@yourusername](https://github.com/yourusername)

## 🙏 Благодарности

- Laravel Community
- TinyMCE Team
- Bootstrap Team
- Все контрибьюторы проекта

---

**Сделано с ❤️ для музыкального сообщества**

