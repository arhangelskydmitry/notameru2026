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

Отдельный ключ для админа `notame.ru`. **Приватный ключ не хранится в репозитории** — только на машине администратора.

### Подключение

```bash
ssh -i ~/.ssh/notame_admin_ed25519 notame@193.106.172.155
```

| | |
|---|---|
| **Пользователь** | `notame` |
| **Хост** | `193.106.172.155` |
| **Ключ (локально)** | `~/.ssh/notame_admin_ed25519` |
| **Публичный ключ** | `~/.ssh/notame_admin_ed25519.pub` |

### Пути на сервере

| | |
|---|---|
| **Домашняя папка** | `/srv/domains/notame.ru` |
| **Рабочий проект** | `/srv/domains/notame.ru/current` |

Каталог `current` доступен на запись пользователю `notame`. Root/sudo **не выдавался**.

### Удобная настройка `~/.ssh/config`

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

# artisan
php artisan site:export
php artisan optimize:clear

# логи
tail -f storage/logs/laravel.log
```

---

## Справка

Подробнее про разделы админки, роли и диагностику входа: [EDITOR_ADMIN_GUIDE.md](EDITOR_ADMIN_GUIDE.md).
