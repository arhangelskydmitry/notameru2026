#!/usr/bin/env bash
# Генерирует готовое письмо handover из docs/ACCESS.local.md → docs/HANDOVER_READY.local.md
# Выходной файл в .gitignore — не коммитить.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/docs/ACCESS.local.md"
OUT="$ROOT/docs/HANDOVER_READY.local.md"
TEMPLATE="$ROOT/docs/ADMIN_HANDOVER.md"

if [[ ! -f "$SRC" ]]; then
  echo "Нет $SRC — скопируйте docs/ACCESS.local.md.example и заполните."
  exit 1
fi

extract() {
  local key="$1"
  grep -E "$key" "$SRC" | head -1 | sed 's/.*: *//' | tr -d '`' | xargs
}

NOTAME_PASS=$(grep -A1 'Логин:.*notame' "$SRC" | grep 'Пароль:' | head -1 | sed 's/.*: *//' | xargs)
PANEL_PASS=$(grep -A1 'user79975' "$SRC" | grep 'Пароль:' | head -1 | sed 's/.*: *//' | xargs)
DB_PASS=$(grep -A1 'Пользователь: notame_preview' "$SRC" | grep 'Пароль:' | head -1 | sed 's/.*: *//' | xargs)

ADMIN_EMAIL="${HANDOVER_ADMIN_EMAIL:-gp-99@ya.ru}"
ADMIN_PASSWORD="${HANDOVER_ADMIN_PASSWORD:-[задайте или сбросьте через artisan tinker]}"
CONTACT_NAME="${HANDOVER_CONTACT_NAME:-Дмитрий Архангельский}"
CONTACT_EMAIL="${HANDOVER_CONTACT_EMAIL:-d.arhangelsky@gmail.com}"
CONTACT_PHONE="${HANDOVER_CONTACT_PHONE:-[телефон/Telegram]}"

cat > "$OUT" <<EOF
# Handover notame.ru — готово к отправке

> Сгенерировано: $(date -Iseconds)
> Источник: docs/ACCESS.local.md
> **Не коммитить в git.**

---

## Текст для нового администратора

**Тема:** Доступы и инструкция — администрирование notame.ru

Здравствуйте!

Передаю доступы к production-сайту **notame.ru**.

### Сайт
- Production: https://notame.ru
- Админка: https://notame.ru/notaadmin/login
- IP: 193.106.172.155 (Laravel, PHP 8.3, MySQL, nginx)

### SSH (работа с сайтом)
\`\`\`
ssh notame@193.106.172.155
# пароль: ${NOTAME_PASS}
cd ~/current
\`\`\`

### SSH (VPS admin, sudo)
\`\`\`
ssh user79975@193.106.172.155
# пароль: ${PANEL_PASS}
\`\`\`

### MySQL
- База: notame_preview
- Пользователь: notame_preview
- Пароль: ${DB_PASS}

\`\`\`bash
ssh notame@193.106.172.155
mysql notame_preview
\`\`\`

### Админка CMS
- URL: https://notame.ru/notaadmin/login
- Логин: ${ADMIN_EMAIL}
- Пароль: ${ADMIN_PASSWORD}

Справка редактора: см. docs/EDITOR_ADMIN_GUIDE.md в репозитории (или запросите у передающего).

### Пути на сервере
- Код: /srv/domains/notame.ru/current
- .env: /srv/domains/notame.ru/current/.env
- Логи Laravel: ~/current/storage/logs/laravel.log
- Бэкапы: /srv/domains/notame.ru/backups/

### Первые шаги
1. Войти в админку, открыть раздел «Статьи»
2. Сделать бэкап: https://notame.ru/notaadmin/backups
3. Ознакомиться с docs/EDITOR_ADMIN_GUIDE.md

### Контакты при вопросах
${CONTACT_NAME}, ${CONTACT_EMAIL}, ${CONTACT_PHONE}

---

Полный шаблон: docs/ADMIN_HANDOVER.md
Техническая справка (без паролей): docs/PRODUCTION_ACCESSES.md
Git workflow: docs/GIT_WORKFLOW.md
EOF

chmod 600 "$OUT"
echo "✓ Записано: $OUT"
echo "  Проверьте ADMIN_PASSWORD и контакты перед отправкой."
