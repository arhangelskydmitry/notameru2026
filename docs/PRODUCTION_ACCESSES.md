# Все необходимые доступы (production)

Секреты (пароли SSH, MySQL, админки) — только в **`docs/ACCESS.local.md`** (не в git).

Handover для нового администратора: [ADMIN_HANDOVER.md](ADMIN_HANDOVER.md).  
Чеклист и статус передачи: [HANDOVER_CHECKLIST.md](HANDOVER_CHECKLIST.md).

---

## 1. Сайт

| | |
|---|---|
| **Production** | https://notame.ru |
| **Админка** | https://notame.ru/notaadmin/login |
| **Preview** | https://notame-preview.factorymedia.ru/ |
| **IP** | `193.106.172.155` |
| **Стек** | Laravel, PHP 8.3, MySQL 8.0, nginx |
| **Хостинг** | VPS Meloman (Factory Media) |

> **notame.ru ≠ notame.pro** — разные проекты.

---

## 2. SSH

### 2a. Пользователь сайта `notame` (рекомендуется)

Работа с Laravel и файлами проекта. **Без sudo.**

```bash
ssh notame@193.106.172.155
cd ~/current    # = /srv/domains/notame.ru/current
```

| | |
|---|---|
| **Логин** | `notame` |
| **Пароль** | `docs/ACCESS.local.md` |
| **Ключ (альтернатива)** | `~/.ssh/notame_admin_ed25519` или deploy-ключ с сервера |

Deploy-ключ на сервере: `/srv/domains/notame.ru/shared/deploy-keys/notame_deploy_ed25519`

Скачать ключ (нужен доступ `user79975`):

```bash
ssh user79975@193.106.172.155 \
  'sudo cat /srv/domains/notame.ru/shared/deploy-keys/notame_deploy_ed25519' \
  > ~/notame_deploy_ed25519
chmod 600 ~/notame_deploy_ed25519
ssh -i ~/notame_deploy_ed25519 notame@193.106.172.155
```

### 2b. VPS admin `user79975` (sudo)

nginx, SSL, системные сервисы, ручные бэкапы.

```bash
ssh user79975@193.106.172.155
```

Пароль — в `docs/ACCESS.local.md`.

---

## 3. Пути на сервере

| Что | Путь |
|-----|------|
| **Код Laravel** | `/srv/domains/notame.ru/current` |
| **Public (nginx)** | `/srv/domains/notame.ru/current/public` |
| **`.env`** | `/srv/domains/notame.ru/current/.env` |
| **Логи nginx** | `/srv/domains/notame.ru/logs/` |
| **Логи Laravel** | `/srv/domains/notame.ru/current/storage/logs/laravel.log` |
| **Бэкапы** | `/srv/domains/notame.ru/backups/` |
| **Картинки** | `/srv/domains/notame.ru/current/public/imgnews/` |
| **Handoff-файл** | `/srv/domains/notame.ru/shared/deploy-handoff-20260609.txt` |

---

## 4. MySQL

Параметры handoff (сверить с `.env` на сервере):

| | |
|---|---|
| **Хост** | `localhost:3306` |
| **База** | `notame_preview` |
| **Пользователь** | `notame_preview` |
| **Пароль** | `docs/ACCESS.local.md` |

```bash
ssh notame@193.106.172.155
mysql notame_preview          # ~/.my.cnf у пользователя notame
grep -E '^DB_' ~/current/.env # Laravel-конфиг может отличаться
```

---

## 5. Laravel / PHP

| | |
|---|---|
| **PHP** | `php8.3` |
| **Artisan** | `php8.3 ~/current/artisan` |
| **FPM** | `/run/php/php8.3-fpm.sock` |

```bash
cd ~/current
php8.3 artisan about
php8.3 artisan route:list | grep notaadmin
php8.3 artisan site:export
php8.3 artisan optimize:clear
```

---

## 6. Админка CMS

**URL:** https://notame.ru/notaadmin/login

### Аккаунты с правами

| Email | Роль |
|-------|------|
| `d.arhangelsky@gmail.com` | super_admin |
| `webmaster@notame.ru` | author |
| `gp-99@ya.ru` | editor |
| `rotermelmax@yandex.ru` | editor |
| `radioedit@mail.ru` | editor |

Пароли в БД захешированы (legacy IQHost в `user_pass`, новые в `admin_password`).

### Публикация статьи

1. https://notame.ru/notaadmin/login
2. **Статьи:** https://notame.ru/notaadmin/posts
3. **Создать:** https://notame.ru/notaadmin/posts/create
4. Опубликовать → проверить https://notame.ru/

**Бэкапы:** https://notame.ru/notaadmin/backups

### Сброс пароля админки

```bash
ssh notame@193.106.172.155
cd ~/current
php8.3 artisan tinker --execute="
\$u = App\Models\WordPress\User::where('user_email','EMAIL_АДМИНА')->first();
\$u->admin_password = bcrypt('НОВЫЙ_ПАРОЛЬ');
\$u->admin_password_plain = 'НОВЫЙ_ПАРОЛЬ';
\$u->save();
echo 'OK';
"
```

Или: `php8.3 artisan admin:generate-passwords --reset`

> Не используйте `App\Models\User` / `password` — в проекте модель `App\Models\WordPress\User`, таблица `wp_users`.

---

## 7. Быстрые команды

```bash
curl -sI https://notame.ru/ | head -3
curl -sI https://notame.ru/notaadmin/login | head -3
dig +short notame.ru A

cd ~/current
php8.3 artisan config:clear && php8.3 artisan cache:clear && php8.3 artisan view:clear

tail -50 ~/current/storage/logs/laravel.log
tail -50 /srv/domains/notame.ru/logs/nginx-error.log
```

Перезагрузка nginx (только `user79975`):

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 8. Безопасность

- **Не коммитить** пароли в git, PR, issue, открытые чаты.
- На production не запускать `composer update` и миграции без согласования.
- После передачи доступов третьим лицам — **сменить пароли** (SSH, MySQL, админка).
- Полный handoff на VPS: `/srv/domains/notame.ru/shared/deploy-handoff-20260609.txt`

---

## Справка

[EDITOR_ADMIN_GUIDE.md](EDITOR_ADMIN_GUIDE.md) — разделы админки, роли, диагностика.
