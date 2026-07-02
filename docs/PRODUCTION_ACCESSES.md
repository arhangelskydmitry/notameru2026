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

## Справка

Подробнее про разделы админки, роли и диагностику входа: [EDITOR_ADMIN_GUIDE.md](EDITOR_ADMIN_GUIDE.md).
