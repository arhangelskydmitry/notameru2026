# Чеклист передачи notame.ru

Статус на **2026-07-02**.

## Выполнено

| # | Задача | Статус |
|---|--------|--------|
| 1 | Документация доступов (`PRODUCTION_ACCESSES.md`, `ACCESS.local.md`) | ✅ |
| 2 | Справка редактора (`EDITOR_ADMIN_GUIDE.md`) | ✅ |
| 3 | Git: ветки `production` + `main`, `GIT_WORKFLOW.md` | ✅ |
| 4 | Автор Глубоков С.Н. в админке и на странице редакции | ✅ |
| 5 | Статья про съезд «Новые люди» опубликована | ✅ |
| 6 | Mac-клиент: версия 0.2.1 (3) в `main` | ✅ |
| 7 | `APP_DEBUG=false` на production | ✅ |
| 8 | Sync с сервера проверен (код совпадает с `production`) | ✅ |

## Сделать вам (5–10 минут)

### 1. Пересобрать Mac-клиент (на Mac)

```bash
cd clients/macos
git pull origin main
./build.sh
# Release + DMG:
./package_dmg.sh
```

В настройках приложения должно быть **0.2.1 (3)**.

### 2. Отправить handover новому админу

```bash
# при необходимости задайте учётку админки:
export HANDOVER_ADMIN_EMAIL='email@example.com'
export HANDOVER_ADMIN_PASSWORD='...'
export HANDOVER_CONTACT_NAME='...'
./scripts/generate-handover.sh
```

Откройте `docs/HANDOVER_READY.local.md` — передайте **защищённым каналом** (не в открытом чате).

Дополнительно можно приложить:
- `docs/EDITOR_ADMIN_GUIDE.md`
- `docs/PRODUCTION_ACCESSES.md` (без `ACCESS.local.md`)

### 3. После смены админа — сменить пароли

- SSH `notame` и `user79975`
- MySQL `notame_preview`
- Пароль входа в админку

### 4. Регулярный ритм

После правок на сервере вручную:

```bash
git checkout production
./scripts/sync-from-production.sh
git add -A && git commit -m "sync: production snapshot $(date +%Y-%m-%d)"
git push origin production
git checkout main && git merge production && git push origin main
```
