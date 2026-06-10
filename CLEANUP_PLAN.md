# Очистка Проекта - Удаление Временной Документации

## 📂 Файлы для Удаления (Временная документация)

### Исправления и Fix-документы (Уже не актуальны):
```
ADMIN_DASHBOARD_500_FIX.md
ADMIN_DB_PROBLEM.md
ADMIN_MENU_UPDATE.md
BANNER_LAYOUT_FIX.md
BANNER_PADDING_FIX.md
BANNER_SIDEBAR_FIX.md
BLOCK_SPACING_FIX.md
BROKEN_PIPE_BROWSER_OK.md
BROKEN_PIPE_FINAL.md
BROKEN_PIPE_FINAL_STATUS.md
BROKEN_PIPE_GLOBAL_SUPPRESSION.md
BROKEN_PIPE_ROOT_CAUSE_FIX.md
BROKEN_PIPE_SOLUTION.md
CLEANUP.md
DB_CONNECTION_FIX.md
DB_CONNECTION_OPTIMIZATION.md
DB_CONNECTION_SOLUTION.md
EMERGENCY_FIX.md
FIX_DS_STORE_ERROR.md
HOMEPAGE_REFACTOR.md
LOCAL_IMAGES_FIX.md
PRODUCTION_FIX.md
REWRITE_CONTENT_FIX.md
RSS_IMAGES_FIX.md
SEO_ANALYSIS_CONTRAST_FIX.md
SEO_ANALYSIS_UPDATE.md
SEO_TITLE_SUFFIX_FIX.md
SIDEBAR_SPACING_FIX.md
SSL_CERTIFICATE_FIX.md
TINYMCE_FILEMANAGER_FIX.md
VALET_SOLUTION.md
YANDEX_API_READY.md
YANDEX_API_SETUP.md
YANDEX_BROKEN_PIPE_FINAL_FIX.md
YANDEX_BROKEN_PIPE_FIX.md
YANDEX_ANALYTICS_BROKEN_PIPE_FIX.md
YANDEX_SETUP.md
YANDEX_SETTINGS_DATABASE.md
YANDEX_SSL_FIX.md
YANDEX_STATUS_FIX.md
YANDEX_WEBMASTER_TOKEN_ISSUE.md
```

### Инструкции по загрузке (Разовые):
```
UPLOAD_FILES_LIST.md
UPLOAD_INSTRUCTIONS.md
FILES_TO_UPLOAD.md
FINAL_UPLOAD_LIST.md
FINAL_FIX_SUMMARY.md
UPDATE_SCRIPT_MANUAL.md
QUICK_UPDATE_GUIDE.md
WEB_SETUP_GUIDE.md
README_WEB_IMPORT.md
DEPLOYMENT_GUIDE.md
PRODUCTION_CHECKLIST.md
DATABASE_DEPLOYMENT.md
```

### Временные SQL файлы:
```
database/add_banner_page_types.sql
truncate_solution.sql
truncate_wp_tables.sql
delete_wp_tables.sql
notameru-rework-6.sql
```

### Временные файлы в storage:
```
storage/seo_test_batch.sql
storage/seo_full_analysis.sql
storage/seo_analysis_80.sql
storage/seo_analysis_test.sql
storage/SEO_ANALYSIS_REPORT.md
storage/exports/full_export_2026-01-20_094547/
storage/backups/notameru_production_20251109_145826.sql
storage/backups/notameru_20251109_145826.sql
```

### Временные PHP скрипты (Если загружали):
```
public/update-system.php
public/clear-cache.php
public/check-update.php
```

---

## 📚 Файлы для Сохранения (Важная документация)

### Основная документация:
```
✅ README.md
✅ ROADMAP_2026.md
✅ VERSION_1.1_RELEASE.md
✅ SECURITY_AUDIT_REPORT.md
✅ BANNER_PAGE_TYPES.md (актуальная функциональность)
```

### Справочники в docs/:
```
✅ docs/SEO_ANALYSIS_GUIDE.md
✅ docs/SEO_AI_MIGRATION.md
✅ docs/SEO_MIGRATION.md
✅ docs/SYNC_GUIDE.md
✅ docs/GIGACHAT_CHATINFO_SETUP.md
✅ docs/CHATINFO_SETUP.md
✅ docs/TROUBLESHOOTING.md
✅ docs/AUTOPOSTING_COMPLETE_GUIDE.md
✅ docs/IMAGE_ALT_TITLE_GUIDE.md
```

### Специальные файлы:
```
✅ FAKE_NEWS_ANALYSIS.md (может пригодиться)
✅ PROJECT_ANALYSIS_AND_IMPROVEMENTS.md (аналитика)
✅ RELEASE_NOTES_v2.0.md (для будущего)
✅ SERVER_SETUP.md (настройки сервера)
```

---

## 🗑️ Команды для Удаления

### Через терминал (локально):
```bash
# Удалить временную документацию
rm -f ADMIN_DASHBOARD_500_FIX.md ADMIN_DB_PROBLEM.md ADMIN_MENU_UPDATE.md
rm -f BANNER_LAYOUT_FIX.md BANNER_PADDING_FIX.md BANNER_SIDEBAR_FIX.md
rm -f BLOCK_SPACING_FIX.md BROKEN_PIPE_*.md CLEANUP.md
rm -f DB_CONNECTION_FIX.md DB_CONNECTION_OPTIMIZATION.md DB_CONNECTION_SOLUTION.md
rm -f EMERGENCY_FIX.md FIX_DS_STORE_ERROR.md HOMEPAGE_REFACTOR.md
rm -f LOCAL_IMAGES_FIX.md PRODUCTION_FIX.md REWRITE_CONTENT_FIX.md
rm -f RSS_IMAGES_FIX.md SEO_ANALYSIS_CONTRAST_FIX.md SEO_ANALYSIS_UPDATE.md
rm -f SEO_TITLE_SUFFIX_FIX.md SIDEBAR_SPACING_FIX.md SSL_CERTIFICATE_FIX.md
rm -f TINYMCE_FILEMANAGER_FIX.md VALET_SOLUTION.md YANDEX_*.md
rm -f UPLOAD_*.md FILES_TO_UPLOAD.md FINAL_*.md UPDATE_SCRIPT_MANUAL.md
rm -f QUICK_UPDATE_GUIDE.md WEB_SETUP_GUIDE.md README_WEB_IMPORT.md
rm -f DEPLOYMENT_GUIDE.md PRODUCTION_CHECKLIST.md DATABASE_DEPLOYMENT.md

# Удалить временные SQL
rm -f database/add_banner_page_types.sql
rm -f truncate_solution.sql truncate_wp_tables.sql delete_wp_tables.sql
rm -f notameru-rework-6.sql

# Удалить временные файлы в storage
rm -f storage/seo_*.sql
rm -rf storage/backups/*.sql
rm -rf storage/exports/full_export_*

# Удалить временные PHP скрипты
rm -f public/update-system.php public/clear-cache.php public/check-update.php
```

---

## 📦 Создание Архива v1.1

### Что Включить в Архив:

1. **Весь код приложения** (кроме временных файлов)
2. **Дамп базы данных** (актуальный)
3. **Важную документацию** (README, ROADMAP, VERSION, docs/)
4. **Конфигурацию** (.env.example)

### Исключить из Архива:

- `/vendor/` (устанавливается через composer)
- `/node_modules/` (устанавливается через npm)
- `/storage/logs/` (логи)
- `/storage/framework/cache/` (кеш)
- `/storage/framework/sessions/` (сессии)
- `/storage/framework/views/` (скомпилированные views)
- `/.git/` (история git)
- `/public/storage` (симлинк)

---

## ✅ Готово к Архивации

После удаления временных файлов проект будет содержать только необходимое для работы и разработки.
