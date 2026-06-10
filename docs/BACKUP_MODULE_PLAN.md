# 💾 Автоматические Бекапы - План Разработки

**Модуль:** Автоматические бекапы и управление через админку  
**Приоритет:** 🔥 ВЫСОКИЙ  
**Сложность:** ⭐⭐⭐ (средняя-высокая)  
**Оценка:** 20-30 часов  
**Дата начала:** 24 января 2026

---

## 🎯 Цели Модуля

1. ✅ Автоматическое создание бекапов по расписанию
2. ✅ Веб-интерфейс для управления бекапами (суперадмин)
3. ✅ Скачивание бекапов через админку
4. ✅ Восстановление из бекапа одним кликом
5. ✅ Ротация старых бекапов
6. ✅ Поддержка удаленных хранилищ (опционально)

---

## 📁 Структура Файлов

```
app/
├── Console/
│   └── Commands/
│       ├── BackupCreate.php        # Команда создания бекапа
│       └── BackupCleanup.php       # Команда очистки старых бекапов
├── Http/
│   └── Controllers/
│       └── Admin/
│           └── BackupController.php # Веб-интерфейс управления
├── Models/
│   └── Backup.php                  # Модель бекапа
└── Services/
    └── BackupService.php           # Бизнес-логика бекапов

database/
└── migrations/
    └── 2026_01_24_create_backups_table.php

resources/
└── views/
    └── admin/
        └── backups/
            ├── index.blade.php     # Список бекапов
            ├── create.blade.php    # Создание вручную
            ├── settings.blade.php  # Настройки
            └── restore.blade.php   # Восстановление

storage/
└── app/
    └── backups/                    # Локальное хранилище

config/
└── backup.php                      # Конфигурация модуля
```

---

## 🗄️ База Данных

### Таблица: `backups`

```sql
CREATE TABLE `backups` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `type` ENUM('full', 'database', 'files') DEFAULT 'full',
  `size` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Размер в байтах',
  `status` ENUM('in_progress', 'completed', 'failed') DEFAULT 'in_progress',
  `storage` ENUM('local', 'remote') DEFAULT 'local',
  `storage_path` VARCHAR(500) NULL COMMENT 'Путь в хранилище',
  `triggered_by` VARCHAR(50) DEFAULT 'auto' COMMENT 'auto, manual, user_id',
  `manifest` JSON NULL COMMENT 'Метаданные: таблицы, файлы, версия',
  `error_message` TEXT NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔧 Настройки (config/backup.php)

```php
return [
    'enabled' => env('BACKUP_ENABLED', true),
    
    // Расписание
    'schedule' => [
        'enabled' => true,
        'frequency' => 'daily', // daily, weekly, monthly
        'time' => '03:00',      // Время запуска (МСК)
        'day_of_week' => 0,     // Для weekly (0=воскресенье)
        'day_of_month' => 1,    // Для monthly
    ],
    
    // Что бекапить
    'include' => [
        'database' => true,
        'files' => [
            'public/uploads',
            'public/images',
            'public/wp-content/uploads',
        ],
        'config' => true, // .env, config/*.php
        'code' => false,  // Весь код (долго)
    ],
    
    // Исключения
    'exclude' => [
        'storage/logs/*',
        'storage/framework/cache/*',
        'storage/framework/sessions/*',
        'storage/framework/views/*',
        '*.log',
        '.DS_Store',
    ],
    
    // Хранилище
    'storage' => [
        'local' => [
            'path' => storage_path('app/backups'),
            'max_size' => 50 * 1024 * 1024 * 1024, // 50 GB
        ],
        'remote' => [
            'enabled' => false,
            'driver' => 's3', // s3, yandex, ftp
            'path' => 'backups/',
        ],
    ],
    
    // Ротация
    'retention' => [
        'keep_last' => 7,           // Последние 7 бекапов
        'keep_daily' => 7,          // По одному за день (7 дней)
        'keep_weekly' => 4,         // По одному за неделю (4 недели)
        'keep_monthly' => 6,        // По одному за месяц (6 месяцев)
    ],
    
    // Уведомления
    'notifications' => [
        'enabled' => true,
        'email' => env('BACKUP_EMAIL', env('ADMIN_EMAIL')),
        'on_success' => false, // Email при успехе
        'on_failure' => true,  // Email при ошибке
    ],
];
```

---

## 📝 Этапы Разработки

### Этап 1: Фундамент (День 1-2, 6-8ч)
- [x] Создать миграцию таблицы `backups`
- [x] Создать модель `Backup`
- [x] Создать конфиг `config/backup.php`
- [x] Создать `BackupService` с базовой логикой

### Этап 2: Команды (День 2-3, 6-8ч)
- [ ] `BackupCreate` команда для создания бекапа
  - [ ] Дамп БД через `mysqldump`
  - [ ] Архивация файлов (tar.gz)
  - [ ] Создание manifest.json
  - [ ] Сохранение в storage/app/backups
  - [ ] Запись в БД таблицу backups
- [ ] `BackupCleanup` команда для ротации
  - [ ] Удаление старых бекапов по политике retention
  - [ ] Обновление записей в БД
- [ ] Регистрация команд в `Kernel.php` (scheduler)

### Этап 3: Веб-интерфейс (День 3-4, 6-8ч)
- [ ] `BackupController@index` - список бекапов
- [ ] `BackupController@create` - создание вручную (AJAX)
- [ ] `BackupController@download` - скачивание бекапа
- [ ] `BackupController@destroy` - удаление бекапа
- [ ] `BackupController@settings` - настройки модуля
- [ ] Представления (Blade шаблоны)

### Этап 4: Восстановление (День 4-5, 4-6ч)
- [ ] `BackupController@restore` - восстановление из бекапа
  - [ ] Распаковка архива
  - [ ] Импорт БД
  - [ ] Восстановление файлов
  - [ ] Откат при ошибке
- [ ] Мастер восстановления (шаг за шагом)

### Этап 5: Дополнительно (Опционально, 2-4ч)
- [ ] Удаленное хранилище (Yandex.Disk)
- [ ] Уведомления на Email
- [ ] Проверка целостности бекапа
- [ ] Прогресс-бар для долгих операций

---

## 🧪 Тестирование

### Сценарии:
1. ✅ Создание бекапа вручную
2. ✅ Автоматическое создание по расписанию
3. ✅ Скачивание бекапа
4. ✅ Восстановление из бекапа
5. ✅ Удаление бекапа
6. ✅ Ротация старых бекапов
7. ✅ Работа при нехватке места
8. ✅ Восстановление при поврежденном архиве

---

## 🔒 Безопасность

- ✅ Доступ только для суперадминов (middleware)
- ✅ CSRF защита на всех формах
- ✅ Валидация всех параметров
- ✅ Rate limiting на операции (1 бекап в 5 минут)
- ✅ Логирование всех действий
- ✅ Хранение вне public/ директории

---

## 📊 Метрики Успеха

После завершения:
- ✅ Бекапы создаются автоматически каждый день
- ✅ Можно скачать любой бекап через админку
- ✅ Восстановление работает без ошибок
- ✅ Старые бекапы удаляются автоматически
- ✅ Суперадмин получает уведомления об ошибках

---

## 🚀 Начинаем!

**Первый шаг:** Создаем миграцию и модель

```bash
php artisan make:migration create_backups_table
php artisan make:model Backup
php artisan make:command BackupCreate
php artisan make:controller Admin/BackupController
```

---

**Дата начала:** 24 января 2026  
**Планируемое завершение:** 28-29 января 2026 (4-5 дней)
