# 📦 УСТАНОВКА "УМНОЕ СЛИЯНИЕ ТЕГОВ" - Пошаговая инструкция

## ✅ Что было создано

### 1. Контроллер
```
✓ app/Http/Controllers/TagMergeController.php
```

Методы:
- `index()` - главная страница анализа
- `findSimilarTags()` - алгоритм поиска похожих
- `areSimilar()` - проверка похожести
- `previewMerge()` - предпросмотр слияния
- `executeMerge()` - выполнение слияния
- `bulkMerge()` - массовое слияние

### 2. View (Blade шаблон)
```
✓ resources/views/admin/tags/merge-index.blade.php
```

Компоненты:
- Статистика (4 карточки)
- Фильтры (порог, минимум статей)
- Группы похожих тегов
- Модальное окно предпросмотра
- JavaScript для AJAX

### 3. Маршруты
```
✓ routes/web.php (обновлен)
```

Добавлено:
```php
Route::get('/merge-duplicates', ...)->name('merge-index');
Route::post('/merge-preview', ...)->name('merge-preview');
Route::post('/merge-execute', ...)->name('merge-execute');
Route::post('/merge-bulk', ...)->name('merge-bulk');
```

### 4. Меню админки
```
✓ resources/views/admin/tags/index.blade.php (обновлен)
```

Добавлена кнопка: **"Умное слияние"** (красная)

### 5. Документация
```
✓ TAG_MERGE_GUIDE.md - полное руководство
✓ DETAILED_ROADMAP.md - дорожная карта
✓ PRIORITY_MATRIX.md - матрица приоритетов
✓ INSTALLATION_TAG_MERGE.md - эта инструкция
```

---

## 🚀 УСТАНОВКА НА PRODUCTION

### Шаг 1: Загрузить файлы

```bash
# На вашем компьютере
cd /Users/mac/SITES_NEW/notamerularavel

# Создать архив новых файлов
tar -czf tag-merge-feature.tar.gz \
  app/Http/Controllers/TagMergeController.php \
  resources/views/admin/tags/merge-index.blade.php \
  resources/views/admin/tags/index.blade.php \
  routes/web.php \
  TAG_MERGE_GUIDE.md

# Загрузить на сервер через SFTP/SCP
scp tag-merge-feature.tar.gz user@notame.ru:/home/user/
```

### Шаг 2: Распаковать на сервере

```bash
# SSH на сервер
ssh user@notame.ru

# Перейти в директорию проекта
cd /path/to/notamerularavel

# Создать бэкап перед обновлением
cp routes/web.php routes/web.php.backup
cp resources/views/admin/tags/index.blade.php resources/views/admin/tags/index.blade.php.backup

# Распаковать архив
tar -xzf ~/tag-merge-feature.tar.gz -C ./

# Проверить что файлы на месте
ls -la app/Http/Controllers/TagMergeController.php
ls -la resources/views/admin/tags/merge-index.blade.php
```

### Шаг 3: Очистить кеш

```bash
# Очистить все кеши Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Пересобрать маршруты (опционально)
php artisan route:cache

# Пересобрать конфигурацию (опционально)
php artisan config:cache
```

### Шаг 4: Проверить маршруты

```bash
# Убедиться что новые маршруты зарегистрированы
php artisan route:list | grep merge

# Должно показать:
# GET  notaadmin/tags/merge-duplicates
# POST notaadmin/tags/merge-preview
# POST notaadmin/tags/merge-execute
# POST notaadmin/tags/merge-bulk
```

### Шаг 5: Проверить права доступа

```bash
# Убедиться что веб-сервер имеет права на чтение
chmod 644 app/Http/Controllers/TagMergeController.php
chmod 644 resources/views/admin/tags/merge-index.blade.php
chmod 644 resources/views/admin/tags/index.blade.php
chmod 644 routes/web.php

# Для директорий
chmod 755 app/Http/Controllers/
chmod 755 resources/views/admin/tags/
```

---

## 🧪 ТЕСТИРОВАНИЕ

### Тест 1: Доступность страницы

```bash
# 1. Войдите в админку
https://notame.ru/notaadmin/login

# 2. Перейдите в раздел "Теги"
https://notame.ru/notaadmin/tags

# 3. Нажмите кнопку "Умное слияние" (красная)
https://notame.ru/notaadmin/tags/merge-duplicates

# Ожидается: Страница с анализом похожих тегов
```

### Тест 2: Анализ похожих тегов

1. На странице слияния установите:
   - Порог похожести: **80%**
   - Минимум статей: **0**

2. Нажмите **"Найти похожие"**

3. Проверьте результат:
   - ✅ Показаны группы похожих тегов
   - ✅ Статистика в верхних карточках
   - ✅ Для каждой группы видны теги

### Тест 3: Предпросмотр слияния

1. Выберите любую группу
2. Нажмите **"Предпросмотр"**
3. Проверьте модальное окно:
   - ✅ Основной тег
   - ✅ Теги для слияния
   - ✅ Статистика (текущее, добавится, итого)
   - ✅ Примеры статей

### Тест 4: Выполнение слияния

**⚠️ ВАЖНО: Начните с тестовой группы!**

1. Выберите группу с малым количеством статей
2. Проверьте предпросмотр
3. Нажмите **"Подтвердить слияние"**
4. Дождитесь завершения
5. Проверьте результат:
   - ✅ Карточка стала зеленой
   - ✅ Показана статистика
   - ✅ Группа исчезла через 3 секунды

6. Проверьте в разделе "Теги":
   - ✅ Старые теги удалены
   - ✅ Основной тег обновлен
   - ✅ Счетчик правильный

### Тест 5: Массовое слияние

**⚠️ ОСТОРОЖНО: Только после успешного теста 4!**

1. Выберите несколько небольших групп (чекбоксы)
2. Нажмите **"Объединить выбранные (N)"**
3. Подтвердите действие
4. Дождитесь завершения
5. Проверьте результаты

---

## 🔍 ПРОВЕРКА ЛОГОВ

### Просмотр логов слияния

```bash
# Посмотреть последние логи
tail -f storage/logs/laravel.log

# Найти логи слияния
grep "Теги объединены" storage/logs/laravel.log

# Пример успешного лога:
# [2026-01-25 10:30:45] local.INFO: Теги объединены
# {"primary_tag":"Джаз","merged_tags":["джаз","ДЖАЗ"],"updated_articles":35,"new_count":85}
```

### Проверка ошибок

```bash
# Поиск ошибок
grep "ERROR" storage/logs/laravel.log | tail -20

# Проверка ошибок слияния
grep "Ошибка при слиянии тегов" storage/logs/laravel.log
```

---

## 🐛 РЕШЕНИЕ ПРОБЛЕМ

### Проблема 1: 404 на странице merge-duplicates

**Причина:** Маршруты не загружены

**Решение:**
```bash
php artisan route:clear
php artisan route:cache
php artisan cache:clear
```

### Проблема 2: "Class TagMergeController not found"

**Причина:** Автозагрузка не обновлена

**Решение:**
```bash
composer dump-autoload
php artisan cache:clear
```

### Проблема 3: Пустая страница или ошибка 500

**Причина:** Синтаксическая ошибка или права доступа

**Решение:**
```bash
# Проверить права
ls -la app/Http/Controllers/TagMergeController.php

# Проверить синтаксис PHP
php -l app/Http/Controllers/TagMergeController.php

# Посмотреть логи
tail -50 storage/logs/laravel.log
```

### Проблема 4: Кнопка "Умное слияние" не появилась

**Причина:** Кеш Blade не обновлен

**Решение:**
```bash
php artisan view:clear
php artisan cache:clear

# Проверить файл
cat resources/views/admin/tags/index.blade.php | grep "merge-index"
```

### Проблема 5: AJAX ошибки при предпросмотре

**Причина:** CSRF token или неправильный URL

**Решение:**
```bash
# Проверить сессии
php artisan session:table  # если используется БД для сессий
php artisan cache:clear

# Проверить в браузере:
# - Console → Network → проверить запрос
# - Console → Errors → проверить JavaScript ошибки
```

---

## 📊 МОНИТОРИНГ ПОСЛЕ УСТАНОВКИ

### Метрики для отслеживания:

```sql
-- Количество тегов до/после
SELECT COUNT(*) FROM wp_terms 
WHERE term_id IN (
  SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'post_tag'
);

-- Топ-10 тегов по количеству статей
SELECT t.name, tt.count 
FROM wp_terms t
JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
WHERE tt.taxonomy = 'post_tag'
ORDER BY tt.count DESC
LIMIT 10;

-- Теги с одной статьей (кандидаты на слияние)
SELECT COUNT(*) FROM wp_term_taxonomy 
WHERE taxonomy = 'post_tag' AND count = 1;
```

### Рекомендации по использованию:

1. **Первый запуск:**
   - Порог: 90% (только явные дубли)
   - Проверить все предпросмотры
   - Слить 3-5 групп вручную

2. **Регулярная очистка:**
   - Раз в месяц
   - Порог: 80%
   - Массовое слияние после проверки

3. **После импорта:**
   - Проверить новые теги
   - Порог: 85%
   - Объединить с существующими

---

## ✅ ЧЕКЛИСТ УСТАНОВКИ

```
□ Файлы загружены на сервер
  □ app/Http/Controllers/TagMergeController.php
  □ resources/views/admin/tags/merge-index.blade.php
  □ resources/views/admin/tags/index.blade.php (обновлен)
  □ routes/web.php (обновлен)

□ Кеш очищен
  □ php artisan cache:clear
  □ php artisan route:clear
  □ php artisan view:clear
  □ php artisan config:clear

□ Маршруты проверены
  □ php artisan route:list | grep merge

□ Права доступа установлены
  □ chmod 644 для файлов
  □ chmod 755 для директорий

□ Тестирование выполнено
  □ Страница открывается
  □ Анализ работает
  □ Предпросмотр работает
  □ Слияние выполняется
  □ Логи записываются

□ Документация прочитана
  □ TAG_MERGE_GUIDE.md
  □ Понятны все функции
  □ Знаем как откатить изменения
```

---

## 🔄 ОТКАТ ИЗМЕНЕНИЙ (если что-то пошло не так)

### Быстрый откат:

```bash
# Восстановить бэкапы
cp routes/web.php.backup routes/web.php
cp resources/views/admin/tags/index.blade.php.backup \
   resources/views/admin/tags/index.blade.php

# Удалить новые файлы
rm app/Http/Controllers/TagMergeController.php
rm resources/views/admin/tags/merge-index.blade.php

# Очистить кеш
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Откат слияния тегов из БД:

**⚠️ Автоматического отката нет!**

Варианты:
1. Восстановить из бэкапа БД (рекомендуется делать перед слиянием)
2. Вручную пересоздать удаленные теги
3. Использовать логи для определения что было изменено

---

## 📞 ПОДДЕРЖКА

При возникновении проблем:

1. **Проверьте логи:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **Проверьте права:**
   ```bash
   ls -la app/Http/Controllers/TagMergeController.php
   ```

3. **Очистите кеш:**
   ```bash
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

4. **Проверьте маршруты:**
   ```bash
   php artisan route:list | grep merge
   ```

---

## 🎉 ГОТОВО!

Если все шаги выполнены и тесты пройдены - система готова к использованию!

**Следующие шаги из roadmap:**
1. ✅ Слияние тегов - **ЗАВЕРШЕНО**
2. ⏳ Автоматические мета-описания (6 часов)
3. ⏳ Ленивая загрузка изображений (3 часа)
4. ⏳ Кеширование БД (6 часов)

**Начать следующий этап?** 🚀
