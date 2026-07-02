# Handover: новый администратор notame.ru

Готовое сообщение для передачи доступов. **Перед отправкой** подставьте значения из `docs/ACCESS.local.md` (см. [ACCESS.local.md.example](ACCESS.local.md.example)) или передайте пароли отдельно по защищённому каналу.

Техническая справка без секретов: [PRODUCTION_ACCESSES.md](PRODUCTION_ACCESSES.md).

---

## Текст письма

**Тема:** Доступы и инструкция — администрирование notame.ru

---

Здравствуйте!

Передаю доступы к production-сайту **notame.ru** (музыкальный журнал на Laravel). Задача на старт: **разобраться в проекте на сервере** и **опубликовать новую статью** через админку.

---

### 1. Сайт

| | |
|---|---|
| **Production** | https://notame.ru |
| **Админка** | https://notame.ru/notaadmin/login |
| **Хостинг** | VPS Meloman (Factory Media) |
| **IP** | `193.106.172.155` |
| **Стек** | Laravel, PHP 8.3, MySQL 8.0, nginx |

> **Не путать** с **notame.pro** — это другой проект (новый Node-сайт), к notame.ru не относится.

---

### 2. SSH-доступ к серверу

**Основной (VPS-администратор, sudo):**

| Параметр | Значение |
|----------|----------|
| **Хост** | `193.106.172.155` |
| **Порт** | `22` |
| **Логин** | `user79975` |
| **Пароль** | `[SSH_PASSWORD]` |

```bash
ssh user79975@193.106.172.155
```

После входа — полный sudo-доступ (администратор VPS).

**Дополнительно (ключ, без sudo):** см. deploy-ключ в [PRODUCTION_ACCESSES.md](PRODUCTION_ACCESSES.md) §2a.

**Рекомендуется для работы с сайтом — пользователь `notame`:**

| Параметр | Значение |
|----------|----------|
| **Хост** | `193.106.172.155` |
| **Логин** | `notame` |
| **Пароль** | `[NOTAME_SSH_PASSWORD]` из `docs/ACCESS.local.md` |

```bash
ssh notame@193.106.172.155
cd ~/current
```

---

### 3. Пути проекта на сервере

| Что | Путь |
|-----|------|
| **Корень Laravel** | `/srv/domains/notame.ru/current` |
| **Public (nginx)** | `/srv/domains/notame.ru/current/public` |
| **Конфиг (.env)** | `/srv/domains/notame.ru/current/.env` |
| **Загрузки / медиа** | `/srv/domains/notame.ru/current/storage/` |
| **Картинки статей** | `/srv/domains/notame.ru/current/public/imgnews/` |
| **Логи nginx** | `/srv/domains/notame.ru/logs/` |
| **Логи Laravel** | `/srv/domains/notame.ru/current/storage/logs/laravel.log` |
| **Бэкапы** | `/srv/domains/notame.ru/backups/` |

| **Бэкапы** | `/srv/domains/notame.ru/backups/` |
| **Handoff на VPS** | `/srv/domains/notame.ru/shared/deploy-handoff-20260609.txt` |

---

### 4. MySQL

| | |
|---|---|
| **База** | `notame_preview` |
| **Пользователь** | `notame_preview` |
| **Пароль** | `[DB_PASSWORD]` из `docs/ACCESS.local.md` |

```bash
ssh notame@193.106.172.155
mysql notame_preview
grep -E '^DB_' ~/current/.env
```

---

### 5. Доступ в админку сайта (для публикации статей)

| | |
|---|---|
| **URL** | https://notame.ru/notaadmin/login |
| **Логин** | `[ADMIN_EMAIL]` |
| **Пароль** | `[ADMIN_PASSWORD]` (или legacy с IQHost; иначе сброс — см. ниже) |

**Аккаунты с правами:**

| Email | Роль |
|-------|------|
| `d.arhangelsky@gmail.com` | super_admin |
| `webmaster@notame.ru` | author |
| `gp-99@ya.ru` | editor |
| `rotermelmax@yandex.ru` | editor |
| `radioedit@mail.ru` | editor |

Публикация статей — **через веб-админку**, не через правку файлов на диске.

**Типовой сценарий:**

1. Войти: https://notame.ru/notaadmin/login
2. Раздел **«Статьи»**: https://notame.ru/notaadmin/posts
3. **«Создать статью»**: https://notame.ru/notaadmin/posts/create
4. Заголовок, текст (TinyMCE), категория, теги, обложка
5. Сохранить и опубликовать (статус «Опубликовано»)
6. Проверить на сайте: https://notame.ru/

**Бэкапы** (перед первыми правками):  
https://notame.ru/notaadmin/backups

---

### 6. Анализ проекта после SSH

```bash
ssh user79975@193.106.172.155
cd /srv/domains/notame.ru/current

php8.3 artisan --version
php8.3 artisan about

php8.3 artisan route:list | grep notaadmin

ls -la app/ routes/ resources/views/

grep -E '^DB_' .env

tail -100 storage/logs/laravel.log
tail -50 /srv/domains/notame.ru/logs/nginx-error.log
```

**БД** (параметры из `.env`):

```bash
mysql -u [DB_USERNAME] -p [DB_DATABASE]
```

Логин/пароль БД: `[DB_USERNAME]` / `[DB_PASSWORD]` (или смотреть в `.env` на сервере).

---

### 7. Полезные команды (если что-то «не обновилось»)

```bash
cd /srv/domains/notame.ru/current

php8.3 artisan config:clear
php8.3 artisan cache:clear
php8.3 artisan view:clear
php8.3 artisan config:cache
```

Перезагрузка nginx (обычно не нужна для статей):

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

### 8. Чеклист «всё работает»

```bash
curl -sI https://notame.ru/ | head -3
curl -sI https://notame.ru/notaadmin/login | head -3
dig +short notame.ru A
```

Ожидается HTTP 200 и A-запись `193.106.172.155`.

---

### 9. Ограничения и безопасность

- **Не коммитьте** и **не пересылайте** `.env`, пароли SSH/админки/БД в открытых каналах.
- На production **не запускайте** `composer update` и миграции без согласования.
- Перед крупными изменениями — бэкап через `/notaadmin/backups` или:

```bash
sudo tar -czf /srv/domains/notame.ru/backups/manual-$(date +%Y%m%d).tar.gz \
  -C /srv/domains/notame.ru/current .
```

- Preview-стенд (не production): https://notame-preview.factorymedia.ru/
- После передачи доступов — **смените пароли** (SSH, MySQL, админка).

---

### 10. Контакты

По техническим вопросам хостинга / SSH / восстановлению:  
**[ВАШЕ_ИМЯ], [EMAIL], [TELEGRAM/ТЕЛЕФОН]**

---

Если что-то не открывается — пришлите скрин или текст ошибки и вывод:

```bash
tail -30 /srv/domains/notame.ru/current/storage/logs/laravel.log
```

---

**С уважением,**  
[Ваше имя]

---

## Что подставить перед отправкой

| Плейсхолдер | Откуда |
|-------------|--------|
| `[NOTAME_SSH_PASSWORD]` | `docs/ACCESS.local.md` |
| `[SSH_PASSWORD]` | `PANEL_SSH_PASSWORD` в `docs/ACCESS.local.md` |
| `[ADMIN_EMAIL]` / `[ADMIN_PASSWORD]` | учётка редактора или создайте новую (см. ниже) |
| `[DB_USERNAME]` / `[DB_PASSWORD]` | `.env` на сервере после SSH |
| Контакты | `HANDOVER_CONTACT_*` в `docs/ACCESS.local.md` |

---

## Если учётки админки нет

**Через SSH** (замените email и пароль):

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

У пользователя должна быть роль в админке (таблица `user_roles`). Назначить роль можно супер-админом в `/notaadmin/users`.

Альтернатива — сгенерировать пароли всем пользователям с ролями:

```bash
php8.3 artisan admin:generate-passwords --reset
```
