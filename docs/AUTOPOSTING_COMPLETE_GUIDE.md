# 📡 СИСТЕМА АВТОПОСТИНГА И RSS

## ✅ Что было создано

Полноценная система автоматической публикации статей в социальные сети и RSS-лента для Яндекс.Дзен.

---

## 📋 Содержание

1. [RSS лента для Яндекс.Дзен](#rss-лента-для-яндексдзен)
2. [Автопостинг в Telegram](#автопостинг-в-telegram)
3. [Автопостинг в VK](#автопостинг-в-vk)
4. [Настройка](#настройка)
5. [Использование](#использование)
6. [Устранение неполадок](#устранение-неполадок)

---

## 🌐 RSS лента для Яндекс.Дзен

### URL
```
https://ваш-сайт.ru/feed/yandex-zen
```

Локально:
```
http://localhost:8002/feed/yandex-zen
```

### Характеристики
- ✅ Формат: RSS 2.0
- ✅ Расширения: Яндекс.Дзен (yandex, media, turbo)
- ✅ Количество статей: последние 50
- ✅ Полный контент статей
- ✅ Изображения в формате WebP
- ✅ Категории как теги
- ✅ Автоматическое обновление

### Структура XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" 
     xmlns:yandex="http://news.yandex.ru" 
     xmlns:media="http://search.yahoo.com/mrss/" 
     xmlns:turbo="http://turbo.yandex.ru">
  <channel>
    <title>Нота Миру</title>
    <link>https://ваш-сайт.ru</link>
    <description>Новости звезд шоу-бизнеса, музыки и культуры</description>
    <language>ru</language>
    
    <item>
      <title>Заголовок статьи</title>
      <link>https://ваш-сайт.ru/slug-statyi</link>
      <guid isPermaLink="true">https://ваш-сайт.ru/slug-statyi</guid>
      <pubDate>Fri, 07 Nov 2025 10:37:12 +0300</pubDate>
      <category>Категория</category>
      <description>Краткое описание...</description>
      <yandex:full-text>Полный текст статьи...</yandex:full-text>
      <enclosure url="https://ваш-сайт.ru/imgnews/image.webp" type="image/webp" />
      <media:content url="https://ваш-сайт.ru/imgnews/image.webp" medium="image" />
    </item>
  </channel>
</rss>
```

### Подключение к Яндекс.Дзен

1. Войдите в [Дзен](https://zen.yandex.ru/)
2. Перейдите в настройки канала
3. Выберите "Добавить источник"
4. Укажите URL RSS: `https://ваш-сайт.ru/feed/yandex-zen`
5. Дождитесь проверки модераторами (обычно 1-3 дня)

---

## 📱 Автопостинг в Telegram

### Возможности
- ✅ Автоматическая отправка при публикации статьи
- ✅ Изображение + текст
- ✅ HTML форматирование (жирный текст, ссылки)
- ✅ Автоматические хештеги из категорий
- ✅ Защита от дубликатов

### Формат сообщения

```
📰 Заголовок статьи

Краткое описание статьи (до 300 символов)...

📖 Читать полностью

#категория1 #категория2 #категория3
```

### Как получить токены

#### 1. Создание бота

1. Найдите [@BotFather](https://t.me/BotFather) в Telegram
2. Отправьте команду `/newbot`
3. Введите имя бота (например: "Нота Миру Бот")
4. Введите username бота (например: "notamiru_bot")
5. Получите токен (формат: `1234567890:ABCdefGHIjklMNOpqrsTUVwxyz`)

#### 2. Настройка канала

**Для публичного канала:**
1. Создайте канал в Telegram
2. Добавьте бота как администратора с правом "Публикация сообщений"
3. ID канала = `@your_channel` (например: `@notamiru`)

**Для приватного канала:**
1. Создайте приватный канал
2. Добавьте бота как администратора
3. Чтобы узнать ID канала:
   - Перешлите любое сообщение из канала боту [@userinfobot](https://t.me/userinfobot)
   - Он покажет ID (формат: `-100xxxxxxxxxx`)

### Настройка в .env

```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHANNEL_ID=@notamiru
```

или для приватного канала:

```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHANNEL_ID=-100xxxxxxxxxx
```

---

## 🔵 Автопостинг в VK

### Возможности
- ✅ Автоматическая отправка при публикации статьи
- ✅ Загрузка изображения на сервер VK
- ✅ Публикация на стене сообщества
- ✅ Автоматические хештеги из категорий
- ✅ Защита от дубликатов

### Формат сообщения

```
Заголовок статьи

Краткое описание статьи (до 400 символов)...

Читать полностью: https://ваш-сайт.ru/slug

#категория1 #категория2 #категория3
```

### Как получить токены

#### 1. Создание приложения

1. Перейдите на [vk.com/apps?act=manage](https://vk.com/apps?act=manage)
2. Нажмите "Создать приложение"
3. Выберите "Standalone приложение"
4. Введите название: "Автопостинг Нота Миру"
5. Подтвердите категорию

#### 2. Получение токена

1. В настройках приложения перейдите в "Настройки"
2. Скопируйте "ID приложения"
3. Перейдите в "Токены"
4. Нажмите "Создать токен"
5. Выберите нужные права:
   - ✅ `photos` - Загрузка фото
   - ✅ `wall` - Публикация на стене
   - ✅ `groups` - Управление сообществом
6. Скопируйте токен доступа

#### 3. ID сообщества

1. Откройте вашу группу ВКонтакте
2. ID находится в URL: `vk.com/club12345` → ID = `12345`
3. Или `vk.com/public12345` → ID = `12345`

**Важно:** Указывайте ID БЕЗ префикса "club" или "public", только число!

### Настройка в .env

```env
VK_ACCESS_TOKEN=vk1.a.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
VK_GROUP_ID=12345
```

---

## ⚙️ Настройка

### 1. Файлы конфигурации

Все настройки хранятся в:
- `.env` - токены и ID
- `config/services.php` - конфигурация сервисов

### 2. Добавьте настройки в .env

Откройте файл `.env` и добавьте:

```env
# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHANNEL_ID=@yourchannel

# VK API
VK_ACCESS_TOKEN=your_vk_access_token_here
VK_GROUP_ID=12345
```

### 3. Очистите кеш

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🚀 Использование

### Автоматическая публикация

Система работает автоматически! При каждой публикации статьи:

1. Вы создаете или редактируете статью в админке
2. Меняете статус на **"Опубликовано"** (publish)
3. `PostObserver` отлавливает событие
4. Статья автоматически отправляется в Telegram (если настроен)
5. Статья автоматически отправляется в VK (если настроен)
6. Пост помечается meta-полем `_auto_posted` (защита от дубликатов)

### Ручная отправка (если нужно)

Если вы хотите отправить статью вручную, создайте Artisan команду:

```php
// app/Console/Commands/SendPostToSocial.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\Post;
use App\Services\TelegramService;
use App\Services\VKService;

class SendPostToSocial extends Command
{
    protected $signature = 'post:send {postId}';
    protected $description = 'Send post to social networks';

    public function handle(TelegramService $telegram, VKService $vk)
    {
        $post = Post::findOrFail($this->argument('postId'));
        
        $this->info("Sending post: {$post->post_title}");
        
        if ($telegram->sendPost($post)) {
            $this->info('✓ Sent to Telegram');
        }
        
        if ($vk->sendPost($post)) {
            $this->info('✓ Sent to VK');
        }
    }
}
```

Использование:
```bash
php artisan post:send 12345
```

---

## 🔍 Проверка работы

### 1. Проверка RSS

Откройте в браузере:
```
http://localhost:8002/feed/yandex-zen
```

**Ожидается:**
- XML файл с последними 50 статьями
- Каждая статья содержит заголовок, описание, ссылку, изображение
- Правильная структура RSS 2.0

### 2. Проверка автопостинга

1. Зайдите в админку: `http://localhost:8002/notaadmin/posts`
2. Создайте тестовую статью:
   - Заголовок: "Тестовая статья"
   - Содержание: "Это тестовая статья для проверки автопостинга"
   - Добавьте изображение
   - Выберите категорию
3. Опубликуйте (статус = "Опубликовано")
4. Проверьте:
   - Telegram канал - статья должна появиться
   - VK группа - статья должна появиться
   - `storage/logs/laravel.log` - должны быть записи об отправке

### 3. Проверка логов

```bash
tail -f storage/logs/laravel.log
```

**Успешная отправка:**
```
[2025-11-07 10:00:00] local.INFO: Post 12345 sent to Telegram
[2025-11-07 10:00:01] local.INFO: Post 12345 sent to VK
```

**Ошибки:**
```
[2025-11-07 10:00:00] local.WARNING: Telegram credentials not configured
[2025-11-07 10:00:00] local.ERROR: Telegram send error: Invalid token
```

---

## 🛠 Устранение неполадок

### RSS не работает (404 ошибка)

**Проблема:** RSS лента возвращает 404

**Решение:**
```bash
php artisan route:clear
php artisan cache:clear
```

### Telegram не отправляет сообщения

**Проблема 1:** Неверный токен бота

**Решение:** Проверьте токен в `.env`, убедитесь что он скопирован полностью

**Проблема 2:** Бот не добавлен в канал

**Решение:** Добавьте бота в канал как администратора с правом публикации

**Проблема 3:** Неверный ID канала

**Решение:** 
- Для публичных каналов: `@channel_name`
- Для приватных: используйте [@userinfobot](https://t.me/userinfobot) для получения ID

### VK не публикует посты

**Проблема 1:** Неверный токен

**Решение:** Создайте новый токен с правами `photos`, `wall`, `groups`

**Проблема 2:** Неверный ID группы

**Решение:** Проверьте ID группы без префикса (только число)

**Проблема 3:** Ошибка загрузки фото

**Решение:** Убедитесь что изображения доступны по URL (проверьте `/imgnews/`)

### Дубликаты сообщений

**Проблема:** Статья отправляется несколько раз

**Решение:** Проверьте meta-поле `_auto_posted`:

```sql
SELECT * FROM wp_postmeta 
WHERE meta_key = '_auto_posted' 
AND post_id = 12345;
```

Если нужно повторно отправить статью, удалите это поле:

```sql
DELETE FROM wp_postmeta 
WHERE meta_key = '_auto_posted' 
AND post_id = 12345;
```

### Изображения не загружаются

**Проблема:** В Telegram/VK изображения не отображаются

**Решение:** Убедитесь что:
1. Файлы находятся в `public/imgnews/`
2. Файлы доступны по URL: `http://localhost:8002/imgnews/image.webp`
3. У поста установлено featured image (миниатюра)

---

## 📊 Архитектура системы

### Компоненты

```
┌─────────────────────────────────────────────────────────────┐
│                        АВТОПОСТИНГ                          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
            ┌─────────────────────────────────┐
            │      PostObserver               │
            │  (отслеживает публикацию)       │
            └─────────────────────────────────┘
                              │
                 ┌────────────┴────────────┐
                 ▼                         ▼
       ┌──────────────────┐      ┌──────────────────┐
       │ TelegramService  │      │   VKService      │
       └──────────────────┘      └──────────────────┘
                 │                         │
                 ▼                         ▼
          [Telegram API]           [VK API]
          

┌─────────────────────────────────────────────────────────────┐
│                        RSS ЛЕНТА                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
            ┌─────────────────────────────────┐
            │      RssController              │
            │  (генерирует XML)               │
            └─────────────────────────────────┘
                              │
                              ▼
                    [Яндекс.Дзен читает RSS]
```

### Файлы

```
app/
├── Http/Controllers/
│   └── RssController.php           # RSS лента
├── Services/
│   ├── TelegramService.php         # Telegram API
│   └── VKService.php                # VK API
├── Observers/
│   └── PostObserver.php             # Отслеживание публикаций
└── Providers/
    └── AppServiceProvider.php      # Регистрация Observer

config/
└── services.php                     # Конфигурация API

routes/
└── web.php                          # Маршрут RSS
```

---

## 🎯 Дополнительные возможности

### Отложенная публикация через Queue

Для высоконагруженных сайтов рекомендуется использовать очереди:

```php
// app/Jobs/SendPostToSocialNetworks.php
<?php

namespace App\Jobs;

use App\Models\WordPress\Post;
use App\Services\TelegramService;
use App\Services\VKService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPostToSocialNetworks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function handle(TelegramService $telegram, VKService $vk)
    {
        $telegram->sendPost($this->post);
        $vk->sendPost($this->post);
    }
}
```

Использование в Observer:

```php
use App\Jobs\SendPostToSocialNetworks;

private function autoPost(Post $post)
{
    if ($post->getMeta('_auto_posted')) {
        return;
    }
    
    // Отправляем в очередь вместо синхронной отправки
    SendPostToSocialNetworks::dispatch($post);
    
    $post->setMeta('_auto_posted', time());
}
```

### Статистика кликов

Добавьте UTM-метки для отслеживания:

```php
// В TelegramService и VKService
private function formatMessage($post)
{
    $url = route('post', $post->post_name);
    $url .= '?utm_source=telegram&utm_medium=social&utm_campaign=autopost';
    // ...
}
```

---

## 📝 Заключение

Система полностью настроена и готова к работе. Вам осталось только добавить токены в `.env` файл и начать публиковать статьи!

**Контрольный список:**
- ✅ RSS лента: `http://localhost:8002/feed/yandex-zen`
- ⬜ Telegram: добавить `TELEGRAM_BOT_TOKEN` и `TELEGRAM_CHANNEL_ID` в `.env`
- ⬜ VK: добавить `VK_ACCESS_TOKEN` и `VK_GROUP_ID` в `.env`
- ✅ Автоматическая публикация работает через `PostObserver`
- ✅ Защита от дубликатов настроена
- ✅ Логирование включено

---

**Документация создана:** 7 ноября 2025  
**Версия Laravel:** 11.x  
**API версии:** Telegram Bot API 7.0+, VK API 5.131

