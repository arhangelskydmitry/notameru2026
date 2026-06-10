# 📊 Руководство по SEO-анализу и регенерации

## Обзор

Система SEO-анализа позволяет:
1. **Анализировать** все статьи на соответствие SEO-критериям
2. **Генерировать SQL** с рекомендуемыми правками
3. **Перегенерировать SEO** через AI (ChatInfo/GigaChat/OpenAI)
4. **Применять исправления** на сервере

---

## 📈 Результаты анализа (21.01.2026)

| Метрика | Значение |
|---------|----------|
| Всего статей | 2709 |
| Требуют улучшения (score < 70) | 2605 (96%) |
| С полными SEO данными | 104 (4%) |

### Основные проблемы

1. **SEO Title идентичен заголовку** (99%)
   - Нужен рерайт для уникальности

2. **Focus Keyword отсутствует** (85%)
   - Критично для ранжирования

3. **Ключевые слова пустые** (80%)
   - Нужны релевантные keywords

4. **OG/Twitter данные пустые** (90%)
   - Важно для шаринга в соцсетях

5. **SEO Title слишком длинный** (40%)
   - Обрезается в поисковой выдаче

---

## 🛠️ Команды

### 1. Анализ всех статей

```bash
# Полный анализ с минимальным score 70
php artisan seo:analyze-all --min-score=70 --output=storage/seo_fixes.sql

# Только анализ (без AI регенерации)
php artisan seo:analyze-all --dry-run

# Анализ первых 100 статей
php artisan seo:analyze-all --limit=100

# С регенерацией через AI
php artisan seo:analyze-all --regenerate
```

**Опции:**
| Опция | Описание | По умолчанию |
|-------|----------|--------------|
| `--regenerate` | Перегенерировать через AI | false |
| `--limit=N` | Ограничить количество | 0 (все) |
| `--offset=N` | Пропустить первые N | 0 |
| `--min-score=N` | Минимальный score | 50 |
| `--output=PATH` | Путь к SQL | storage/seo_fixes.sql |
| `--dry-run` | Только анализ | false |

### 2. Пакетная регенерация

```bash
# Обработать 50 статей
php artisan seo:batch-regenerate --batch=50

# Продолжить с последнего
php artisan seo:batch-regenerate --continue

# Использовать конкретный AI провайдер
php artisan seo:batch-regenerate --provider=chatinfo

# Только статьи без SEO
php artisan seo:batch-regenerate --only-empty

# Только статьи с плохим score
php artisan seo:batch-regenerate --only-bad
```

**Опции:**
| Опция | Описание | По умолчанию |
|-------|----------|--------------|
| `--batch=N` | Размер пакета | 50 |
| `--from=ID` | Начать с ID | 0 |
| `--provider=X` | AI провайдер | auto |
| `--delay=MS` | Задержка между запросами | 1000 |
| `--output=PATH` | Путь к SQL | storage/seo_regenerate.sql |
| `--continue` | Продолжить обработку | false |
| `--only-empty` | Только пустые SEO | false |
| `--only-bad` | Только score < 50 | false |

---

## 📋 Критерии оценки SEO

### Оценка по 100-балльной шкале

| Критерий | Вес | Требования |
|----------|-----|------------|
| SEO Title | 20% | 30-60 символов, рерайт заголовка |
| SEO Description | 20% | 120-160 символов, уникальный |
| Keywords | 15% | 3-10 ключевых слов |
| Focus Keyword | 25% | Есть, в title и description |
| Open Graph | 10% | og_title, og_description |
| Twitter Card | 10% | twitter_title, twitter_description |

### Уровни качества

- **90-100**: ✅ Отлично
- **70-89**: 🟡 Хорошо
- **50-69**: ⚠️ Требует улучшения
- **0-49**: ❌ Критично

---

## 🚀 Процесс массовой регенерации

### Этап 1: Анализ

```bash
# Анализируем все статьи
php artisan seo:analyze-all --min-score=70 --dry-run

# Получаем отчёт с количеством проблемных статей
```

### Этап 2: Пакетная регенерация

```bash
# Запускаем пакетами по 50 статей
php artisan seo:batch-regenerate --batch=50 --delay=1500

# После каждого пакета проверяем SQL
cat storage/seo_regenerate.sql | tail -50

# Продолжаем обработку
php artisan seo:batch-regenerate --continue
```

### Этап 3: Применение на сервере

```bash
# Через MySQL CLI
mysql -u USERNAME -p DATABASE_NAME < storage/seo_regenerate.sql

# Через phpMyAdmin
# 1. Откройте phpMyAdmin
# 2. Выберите базу данных
# 3. Вкладка "Импорт"
# 4. Загрузите файл seo_regenerate.sql
```

---

## ⏱️ Оценка времени

Для 2605 проблемных статей:

| Параметр | Значение |
|----------|----------|
| Среднее время на статью | ~4 сек (с задержкой 1 сек) |
| Пакет 50 статей | ~3.5 мин |
| Полная обработка | ~3.5 часа |
| Рекомендуемая стратегия | По 200 статей в день |

### Экономия API

При использовании ChatInfo/GigaChat:
- Дешевле чем OpenAI
- Нет географических ограничений
- Лучше понимание русского языка

---

## 📁 Генерируемые файлы

| Файл | Описание |
|------|----------|
| `storage/seo_fixes.sql` | SQL из команды analyze-all |
| `storage/seo_regenerate.sql` | SQL из команды batch-regenerate |
| `storage/seo_regenerate_state.json` | Состояние для продолжения |
| `storage/seo_full_analysis.sql` | Полный анализ |

---

## 🔧 Настройка AI провайдеров

### ChatInfo (рекомендуется)
```env
CHATINFO_API_KEY=your_api_key
```

### GigaChat
Настраивается через панель администратора:
- Перейдите в /notaadmin/seo
- Введите Client ID и Client Secret

### OpenAI
```env
OPENAI_API_KEY=sk-...
```

---

## 💡 Рекомендации

1. **Начните с малого** - протестируйте на 10-20 статьях
2. **Проверяйте качество** - просмотрите SQL перед применением
3. **Делайте бэкап** - перед применением SQL сохраните таблицу post_seo
4. **Используйте --continue** - процесс можно прервать и продолжить
5. **Мониторьте API лимиты** - следите за балансом провайдера

---

## 📊 Применение на сервере

### Полный процесс

1. **Локально**:
   ```bash
   php artisan seo:batch-regenerate --batch=100
   ```

2. **Проверка SQL**:
   ```bash
   cat storage/seo_regenerate.sql | head -100
   ```

3. **Загрузка на сервер**:
   - Скачайте `storage/seo_regenerate.sql`
   - Откройте phpMyAdmin
   - Импортируйте файл

4. **Верификация**:
   ```sql
   SELECT COUNT(*) FROM post_seo WHERE seo_score = 100;
   ```

---

## ⚠️ Важные замечания

- **Не редактируйте SQL вручную** - формат критичен для импорта
- **UTF-8** - файлы в кодировке UTF-8 с BOM могут вызвать проблемы
- **Транзакции** - SQL содержит START TRANSACTION / COMMIT
- **Идемпотентность** - повторное применение обновит записи (не дублирует)
