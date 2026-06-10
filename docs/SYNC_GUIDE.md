# 📦 Руководство по синхронизации сайта

## Обзор

Система синхронизации позволяет создать **полную копию сайта** для локальной разработки:
- 🗄️ **Полный дамп базы данных** (все таблицы)
- 🖼️ **Все изображения** (uploads, wp-content/uploads, images)
- ⚙️ **Конфигурации** (.env, config/*.php, seo-settings.json)

---

## 🚀 Быстрый старт

### На production сервере (без SSH)

1. Загрузите `public/sync-export.php` на сервер
2. Откройте в браузере:
   ```
   https://notame.ru/sync-export.php?key=notame_backup_2026_YYYYMMDD
   ```
   (где YYYYMMDD — сегодняшняя дата, например `20260120`)
3. Выберите параметры и скачайте архив
4. **УДАЛИТЕ** скрипт после использования!

### На локальном сервере

```bash
# Полный импорт
php artisan site:import ~/Downloads/notame_full_backup_xxx.tar.gz

# Настройка
mv .env.imported .env
nano .env  # Настройте подключение к локальной БД

# Очистка кеша
php artisan config:clear && php artisan cache:clear
```

---

## 📤 Экспорт (создание бэкапа)

### Через Artisan (если есть SSH)

```bash
# Полный бэкап (БД + изображения + конфиги)
php artisan site:export

# Только БД и конфиги (быстро)
php artisan site:export --skip-images

# Без .env файла (безопасный режим)
php artisan site:export --skip-env

# Указать директорию для сохранения
php artisan site:export --output=/path/to/exports
```

### Через веб-интерфейс (без SSH)

1. Загрузите `sync-export.php` на сервер
2. Откройте URL с секретным ключом:
   ```
   https://notame.ru/sync-export.php?key=notame_backup_2026_20260120
   ```
3. Настройте параметры:
   - ✅ **Пропустить изображения** — быстрый экспорт только БД
   - ✅ **Не включать .env** — не экспортировать конфиденциальные данные
4. Нажмите "Создать и скачать полный бэкап"

---

## 📥 Импорт (восстановление)

### Полный импорт

```bash
php artisan site:import /path/to/notame_full_backup_xxx.tar.gz
```

### Опции импорта

```bash
# Тестовый запуск (без изменений)
php artisan site:import archive.tar.gz --dry-run

# Перезаписать существующие данные
php artisan site:import archive.tar.gz --force

# Только БД (без изображений)
php artisan site:import archive.tar.gz --skip-images

# Без конфигураций
php artisan site:import archive.tar.gz --skip-config

# Пропустить импорт БД
php artisan site:import archive.tar.gz --skip-db
```

---

## 📁 Структура архива

```
notame_full_backup_YYYY-MM-DD_HHMMSS.tar.gz
├── manifest.json              # Метаданные бэкапа
├── database/
│   ├── full_database.json     # JSON дамп всех таблиц
│   └── database.sql           # SQL дамп (если mysqldump доступен)
├── config/
│   ├── .env                   # Конфигурация окружения
│   ├── seo-settings.json      # SEO настройки
│   └── config/
│       ├── app.php
│       ├── database.php
│       ├── filesystems.php
│       └── ...
└── images/
    ├── uploads/               # Изображения из /public/uploads
    │   └── 2024/
    │       └── 01/
    │           └── image.webp
    ├── wp-content/uploads/    # WordPress uploads
    └── images/                # Статические изображения
```

---

## 🔐 Безопасность

### Секретный ключ

По умолчанию: `notame_backup_2026_YYYYMMDD` (меняется ежедневно)

**Рекомендуется изменить!** Откройте `sync-export.php` и измените:

```php
define('SECRET_KEY', 'ваш_уникальный_ключ_здесь');
```

### После использования

**ОБЯЗАТЕЛЬНО удалите скрипт:**

```bash
rm public/sync-export.php
```

### .env файл

При импорте `.env` сохраняется как `.env.imported` для безопасности:
- Проверьте содержимое
- Измените параметры БД для локального сервера
- Переименуйте в `.env`

---

## 💡 Сценарии использования

### Первоначальное клонирование сайта

```bash
# На production (через веб-интерфейс):
# https://notame.ru/sync-export.php?key=...

# На локальном сервере:
php artisan site:import ~/Downloads/notame_full_backup_xxx.tar.gz
mv .env.imported .env
# Отредактируйте .env для локальной БД:
# DB_HOST=127.0.0.1
# DB_DATABASE=notame_local
# DB_USERNAME=root
# DB_PASSWORD=

php artisan config:clear
php artisan cache:clear
```

### Обновление локальной копии (только БД)

```bash
# На production: создаём бэкап без изображений
php artisan site:export --skip-images

# На локальном: импортируем с перезаписью
php artisan site:import archive.tar.gz --skip-images --force
```

### Быстрое резервное копирование

```bash
# Только БД, без изображений и .env
php artisan site:export --skip-images --skip-env
```

---

## ⚙️ Настройка локального окружения

### После импорта

1. **Проверьте `.env.imported`:**
   ```bash
   cat .env.imported
   ```

2. **Создайте локальный `.env`:**
   ```bash
   cp .env.imported .env
   ```

3. **Измените настройки БД:**
   ```env
   DB_HOST=127.0.0.1
   DB_DATABASE=notame_local
   DB_USERNAME=root
   DB_PASSWORD=
   
   APP_URL=http://localhost:8000
   APP_DEBUG=true
   ```

4. **Создайте локальную БД:**
   ```bash
   mysql -u root -e "CREATE DATABASE notame_local"
   ```

5. **Импортируйте данные:**
   ```bash
   php artisan site:import archive.tar.gz
   ```

6. **Очистите кеш:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

7. **Запустите сервер:**
   ```bash
   php artisan serve
   ```

---

## ⚠️ Важные замечания

| Пункт | Описание |
|-------|----------|
| **Размер архива** | С изображениями может быть несколько ГБ |
| **Время создания** | До 30 минут для больших сайтов |
| **Таймауты** | Увеличьте лимиты PHP на сервере |
| **.env файл** | Содержит конфиденциальные данные! |
| **Изображения** | Не перезаписываются при повторном импорте |
| **SQL vs JSON** | SQL дамп приоритетнее, но требует mysqldump |

---

## 🛠️ Troubleshooting

### Ошибка "Доступ запрещён"

- Проверьте секретный ключ
- Убедитесь, что дата в ключе актуальна (YYYYMMDD)

### Архив слишком большой

- Используйте `--skip-images` для быстрого экспорта
- Скачивайте в несколько этапов

### Ошибка импорта БД

- Проверьте подключение к MySQL
- Убедитесь, что БД пустая или используйте `--force`
- Проверьте права пользователя MySQL

### Изображения не отображаются

- Проверьте права на папки: `chmod -R 755 public/uploads`
- Проверьте символические ссылки: `php artisan storage:link`

---

## 📊 Команды

| Команда | Описание |
|---------|----------|
| `php artisan site:export` | Полный экспорт сайта |
| `php artisan site:import` | Полный импорт сайта |
| `php artisan articles:export` | Экспорт только статей |
| `php artisan articles:import` | Импорт только статей |
| `php artisan db:backup` | Резервная копия БД |

---

*Последнее обновление: Январь 2026*
