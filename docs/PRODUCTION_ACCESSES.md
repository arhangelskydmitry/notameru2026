# Все необходимые доступы (production)

Единый документ с доступами к notame.ru. Источник — handover production-сервера.

---

## 1. Админка сайта (главное для редактора)

### Вход

| | |
|---|---|
| **URL** | https://notame.ru/notaadmin/login |
| **Логин** | email из таблицы `wp_users` в MySQL (поле `user_email`) |
| **Пароль** | тот же, что был на IQHost — переносился с БД |

После входа: https://notame.ru/notaadmin/

### Дополнительно в админке

| Что | Где / как |
|-----|-----------|
| **Бэкапы** | https://notame.ru/notaadmin/backups |
| **Экспорт** | на сервере: `php artisan site:export` |

### Экспорт (на сервере)

```bash
cd /srv/domains/notame.ru/current
php artisan site:export
```

### Если пароль админа неизвестен — сброс на сервере

```bash
cd /srv/domains/notame.ru/current
php artisan tinker --execute="
\$u = App\Models\WordPress\User::where('user_email','EMAIL_АДМИНА')->first();
\$u->admin_password = bcrypt('НОВЫЙ_ПАРОЛЬ');
\$u->admin_password_plain = 'НОВЫЙ_ПАРОЛЬ';
\$u->save();
"
```

> В handover указаны `App\Models\User` и поле `password` — в этом проекте нужны `App\Models\WordPress\User` и `admin_password` (таблица `wp_users`). Legacy-пароль IQHost также хранится в `user_pass` и принимается при входе.

Альтернатива — сброс паролей всех пользователей с ролями:

```bash
php artisan admin:generate-passwords --reset
```

---

## 2. SSH на production-сервер

**Приватные ключи и пароли не хранятся в репозитории** — см. `docs/ACCESS.local.md` (шаблон: [ACCESS.local.md.example](ACCESS.local.md.example)).

Handover для нового администратора: [ADMIN_HANDOVER.md](ADMIN_HANDOVER.md).

### 2a. VPS-администратор (sudo)

| | |
|---|---|
| **Хост** | `193.106.172.155` |
| **Порт** | `22` |
| **Пользователь** | `user79975` |
| **Пароль** | `PANEL_SSH_PASSWORD` в `docs/ACCESS.local.md` |

```bash
ssh user79975@193.106.172.155
```

Полный sudo-доступ (nginx, системные сервисы, ручные бэкапы).

### 2b. Ограниченный доступ (ключ, без sudo)

```bash
ssh -i ~/.ssh/notame_admin_ed25519 notame@193.106.172.155
```

| | |
|---|---|
| **Пользователь** | `notame` |
| **Ключ (локально)** | `~/.ssh/notame_admin_ed25519` |

Каталог `current` доступен на запись. Root/sudo **не выдавался**.

### Пути на сервере

| | |
|---|---|
| **Домашняя папка** | `/srv/domains/notame.ru` |
| **Рабочий проект** | `/srv/domains/notame.ru/current` |
| **Public (nginx)** | `/srv/domains/notame.ru/current/public` |
| **Логи nginx** | `/srv/domains/notame.ru/logs/` |
| **Бэкапы на диске** | `/srv/domains/notame.ru/backups/` |

### Удобная настройка `~/.ssh/config` (ключ notame)

```
Host notame-prod
    HostName 193.106.172.155
    User notame
    IdentityFile ~/.ssh/notame_admin_ed25519
    IdentitiesOnly yes
```

После этого: `ssh notame-prod`

### Типовые команды на сервере

```bash
cd /srv/domains/notame.ru/current

php8.3 artisan site:export
php8.3 artisan optimize:clear
tail -f storage/logs/laravel.log
```

---

## 3. Остальные доступы — на сервере

MySQL, почта, API-ключи (Метрика, SEO AI, Telegram и т.д.) **не дублируются в репозитории**. Актуальные значения — только в `.env` на production.

После SSH:

```bash
cd /srv/domains/notame.ru/current

# все переменные окружения (секреты — не копировать в git/issue)
cat .env

# только нужные группы
grep -E '^(DB_|WORDPRESS_DB_|MAIL_|YANDEX_|TELEGRAM_|OPENAI_|APP_)' .env

# сводка Laravel
php artisan about
```

### Что смотреть в `.env`

| Группа | Переменные | Зачем |
|--------|------------|-------|
| База данных | `DB_*` | основная MySQL |
| Legacy WP | `WORDPRESS_DB_*` | старая база (миграция) |
| Приложение | `APP_URL`, `APP_KEY` | URL сайта, шифрование |
| Почта | `MAIL_*` | отправка писем |
| Аналитика | `YANDEX_METRIKA_*` | Метрика API |
| Интеграции | `TELEGRAM_*`, `OPENAI_*` и др. | боты, AI |

### Проверка MySQL (если есть клиент)

```bash
cd /srv/domains/notame.ru/current
source .env 2>/dev/null || export $(grep -v '^#' .env | xargs)
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SHOW TABLES LIMIT 5;"
```

### Структура деплоя

```bash
ls -la /srv/domains/notame.ru/
# current -> симлинк на активный релиз (если используется)
readlink -f /srv/domains/notame.ru/current
```

### Важно

- **Секреты не коммитить** в git и не вставлять в PR/issue.
- Два уровня SSH: `user79975` (sudo) и `notame` (только проект, без sudo).
- Cloud Agent **не может** подключиться по SSH без ключей/паролей на вашей машине.

---

## Справка

Подробнее про разделы админки, роли и диагностику входа: [EDITOR_ADMIN_GUIDE.md](EDITOR_ADMIN_GUIDE.md).
