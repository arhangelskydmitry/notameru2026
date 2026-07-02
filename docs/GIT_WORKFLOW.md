# Git workflow: production + main

Монорепозиторий: **Laravel (notame.ru)** + **macOS-клиент** (`clients/macos/`).

Эталон web-кода — **боевой сервер**. Mac-клиент живёт только в git.

## Ветки

| Ветка | Назначение |
|-------|------------|
| **`production`** | Зеркало web-кода с сервера (notame.ru) |
| **`main`** | Интеграция: web из `production` + разработка Mac + документация |
| **`cursor/*`** | Задачи агента / фичи |

```
production  ←── sync с сервера (scripts/sync-from-production.sh)
     │
     ▼ merge
   main      ←── clients/macos/, docs, доработки
     │
     ▼
 cursor/feature-*
```

## Синхронизация с сервером

```bash
git checkout production
./scripts/sync-from-production.sh
git add -A
git commit -m "sync: production snapshot $(date +%Y-%m-%d)"
git push origin production
```

Переменные (или `docs/ACCESS.local.md`):

```bash
export NOTAME_SSH_PASSWORD='...'
export NOTAME_SSH_USER=notame
export NOTAME_SSH_HOST=193.106.172.155
```

**Не синхронизируется:** `.env`, `vendor/`, `storage/`, `public/imgnews/`, `clients/macos/`.

## Обновить main после production

```bash
git checkout main
git merge production -m "merge: web from production"
# clients/macos/ остаётся из main
git push origin main
```

## Mac-клиент (Nota Miru)

- Исходники: `clients/macos/`
- Сборка: `cd clients/macos && ./build.sh`
- API: `https://notame.ru/api/mac/v1`
- На **сервер не деплоится** — только Laravel

## Деплой web на сервер

Деплоить **из ветки `production`** (или проверенный merge в `main` → обратно в `production`):

```bash
# пример: только изменённые файлы
rsync -avz --exclude ... ./ notame@193.106.172.155:~/current/
```

Не выкладывать на сервер: `clients/macos/`, `docs/ACCESS.local.md`, `.env`.

## Важно

- Сервер **без `.git`** — `notame.ru/current` → symlink на `notame-preview.factorymedia.ru/current`
- После правок на сервере вручную — обязательно `sync-from-production.sh` → commit в `production`
- Секреты только в `docs/ACCESS.local.md` (в `.gitignore`)
