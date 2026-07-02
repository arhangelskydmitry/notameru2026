# Админка сайта — справка для редактора

> Краткий список доступов: [PRODUCTION_ACCESSES.md](PRODUCTION_ACCESSES.md)

## Вход

| | |
|---|---|
| **URL** | https://notame.ru/notaadmin/login |
| **Логин** | email из таблицы `wp_users` (поле `user_email`) |
| **Пароль** | тот же, что был на IQHost — переносился с БД |

После входа открывается дашборд: https://notame.ru/notaadmin/

### Как проверяется пароль

Админка принимает два варианта:

1. **`admin_password`** — Laravel bcrypt (если пароль задавался через админку или `admin:generate-passwords`)
2. **`user_pass`** — legacy-хеш WordPress/IQHost (phpass или `$wp$` + bcrypt)

Если пароль с IQHost не подходит, возможно, для пользователя уже установлен отдельный `admin_password`.

---

## Основные разделы

| Раздел | URL |
|--------|-----|
| Статьи | https://notame.ru/notaadmin/posts |
| Создать статью | https://notame.ru/notaadmin/posts/create |
| Категории | https://notame.ru/notaadmin/categories |
| Теги | https://notame.ru/notaadmin/tags |
| Баннеры | https://notame.ru/notaadmin/banners |
| Бэкапы | https://notame.ru/notaadmin/backups |
| Мета-описания | https://notame.ru/notaadmin/meta-descriptions |
| Счётчики | https://notame.ru/notaadmin/counters |

Раздел **Бэкапы** доступен только супер-админу.

---

## Экспорт сайта (на сервере)

Полный экспорт БД, изображений и конфигов:

```bash
cd /srv/domains/notame.ru/current
php artisan site:export
```

Опции:

```bash
php artisan site:export --skip-images   # только БД и конфиги
php artisan site:export --skip-env      # без .env
php artisan site:export --output=/path/to/exports
```

Архив сохраняется в `storage/exports/`. Подробнее: [SYNC_GUIDE.md](SYNC_GUIDE.md).

---

## Сброс пароля админа

### Вариант 1: tinker (один пользователь)

```bash
cd /srv/domains/notame.ru/current
php artisan tinker --execute="
\$u = App\Models\WordPress\User::where('user_email','EMAIL_АДМИНА')->first();
\$u->admin_password = bcrypt('НОВЫЙ_ПАРОЛЬ');
\$u->admin_password_plain = 'НОВЫЙ_ПАРОЛЬ';
\$u->save();
"
```

> **Важно:** используйте модель `App\Models\WordPress\User` и поле `admin_password`, а не `App\Models\User` / `password`.

### Вариант 2: artisan (все пользователи с ролями)

```bash
cd /srv/domains/notame.ru/current
php artisan admin:generate-passwords --reset
```

Команда сгенерирует новые пароли для всех пользователей с ролями в админке и выведет пароль супер-админа в консоль.

---

## Роли

| Роль | Возможности |
|------|-------------|
| **Супер-админ** | Полный доступ, пользователи, бэкапы, настройки |
| **Редактор** | Все статьи, категории, теги, модерация |
| **Автор** | Только свои статьи |

---

## Если не пускает в админку

1. Проверьте, что email есть в `wp_users.user_email`.
2. У пользователя должна быть роль в таблице `user_roles` — без роли вход запрещён.
3. Попробуйте пароль от IQHost; если не подходит — сбросьте через tinker (см. выше).
4. Очистите кеш: `php artisan optimize:clear`.
