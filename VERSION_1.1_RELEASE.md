# 🎉 НотаМиру CMS v1.1 - Stable Release

**Дата релиза:** 24 января 2026  
**Версия:** 1.1.0 (Stable)  
**Предыдущая версия:** 1.0.0

---

## 📋 Что Нового в v1.1

### 🎨 Улучшения UI/UX

1. **Рефакторинг Главной Страницы**
   - Новая структура с двумя независимыми вертикальными колонками
   - Исправлены проблемы с отступами между блоками
   - Единый sticky-сайдбар для всех виджетов
   - Оптимизированная CSS структура

2. **Улучшения Баннерной Системы**
   - Добавлены настройки отображения по типам страниц (Главная, Категории, Статьи, Прочие)
   - Баннеры теперь отображаются на всех типах страниц
   - Исправлена зона `sidebar-top` на всех страницах
   - Добавлены правильные отступы и padding для баннеров

### 🐛 Исправленные Ошибки

1. **Баннеры**
   - Исправлена верстка при добавлении баннера 300x350
   - Исправлено отображение на страницах категорий и статей
   - Добавлен внутренний padding для баннеров

2. **Изображения**
   - Исправлено отображение изображений на локальном сервере
   - Корректная обработка абсолютных URL с доменом notame.ru
   - Автоматическое преобразование в относительные пути

3. **Редактор Контента**
   - Исправлена ошибка в функции `rewriteContent()` (переменная `$content`)
   - Улучшена интеграция TinyMCE 6 с Laravel FileManager
   - Добавлена множественная поддержка методов передачи URL (polling, events, postMessage)

4. **База Данных**
   - Добавлены поля для настройки отображения баннеров
   - Исправлена обработка пустых дат в баннерах

### 🔧 Технические Улучшения

1. **Инструменты Обновления**
   - Веб-скрипт для запуска миграций без SSH (`update-system.php`)
   - Веб-скрипт для очистки кеша (`clear-cache.php`)
   - SQL скрипты для ручного обновления через phpMyAdmin

2. **Документация**
   - Создана подробная документация для каждого исправления
   - Инструкции по deployment на production

---

## 📦 Структура Базы Данных v1.1

### Новые Поля в Таблице `banners`:

```sql
ALTER TABLE `banners` ADD COLUMN `show_on_home` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `banners` ADD COLUMN `show_on_category` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `banners` ADD COLUMN `show_on_post` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `banners` ADD COLUMN `show_on_other` TINYINT(1) NOT NULL DEFAULT 1;
```

---

## 🚀 Установка/Обновление

### Обновление с v1.0 до v1.1:

**На Production (без SSH):**
1. Загрузите обновленные файлы через FTP
2. Выполните SQL миграцию через phpMyAdmin (см. UPGRADE_TO_1.1.md)
3. Очистите кеш через `clear-cache.php`

**На локальном сервере:**
```bash
php artisan migrate
php artisan cache:clear
php artisan view:clear
```

---

## 📁 Основные Файлы Версии 1.1

### Backend:
- `app/Models/Banner.php` - Модель с поддержкой типов страниц
- `app/Helpers/BannerHelper.php` - Фильтрация по типу страницы
- `app/Http/Controllers/BannerController.php` - CRUD с новыми полями
- `app/Http/Controllers/AdminPanelController.php` - Исправлена функция rewriteContent

### Frontend:
- `resources/views/frontend/index.blade.php` - Новая структура с независимыми колонками
- `resources/views/frontend/post.blade.php` - Добавлен баннер в сайдбар
- `resources/views/partials/sidebar.blade.php` - Исправлена зона баннера
- `resources/views/admin/post-edit.blade.php` - Улучшенная интеграция с FileManager

### Admin UI:
- `resources/views/admin/banners/create.blade.php` - Чекбоксы для типов страниц
- `resources/views/admin/banners/edit.blade.php` - Чекбоксы для типов страниц

### Database:
- `database/migrations/2026_01_24_001500_add_page_types_to_banners.php`

---

## ⚠️ Известные Ограничения

1. **FileManager** - Требует дополнительной настройки для полной совместимости с TinyMCE 6
2. **AI Сервисы** - Функция рерайта требует настроенный GigaChat или ChatInfo
3. **Локальный Сервер** - Требуется MAMP с правильной конфигурацией MySQL socket

---

## 🔮 Что Дальше (v2.0)

См. файл `ROADMAP_2026.md` для полного плана развития:
1. 🎨 Дизайн и оформление в настройках (40-60ч)
2. 🏷️ Редактирование тегов (10-15ч)
3. 📂 Файловый менеджер (30-40ч)
4. 💾 Автоматические бекапы (20-30ч)

---

## 👥 Команда

**Разработка:** AI Assistant (Claude)  
**Тестирование:** Production Environment  
**Поддержка:** notame.ru

---

## 📞 Поддержка

Для вопросов и отчетов об ошибках:
- **Логи:** `storage/logs/laravel.log`
- **Документация:** См. папку `/docs` и корневые .md файлы

---

**Статус:** ✅ Стабильная версия, готова к использованию  
**Последнее обновление:** 24 января 2026
