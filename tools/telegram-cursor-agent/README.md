# Telegram Bot Kit

Переносимый комплект Telegram-ботов для управления проектом через Cursor Agent.

В комплекте:
- `apps/telegram-agent` — основной бот-разработчик, который принимает задачи, запускает Cursor Agent, ведёт очередь задач и журнал беседы.
- `apps/telegram-iiko-mentor` — дополнительный бот-наставник/тестировщик. Его можно оставить, удалить или переименовать под роль нового проекта.
- `deploy/env` — примеры env-файлов без секретов.
- `deploy/systemd` — примеры systemd user services.
- `docs/telegram-bot-specification.md` — полная продуктово-техническая спецификация.

## 1. Быстрый перенос в новый проект

1. Скопируйте папку `telegram-bot-kit` в новый репозиторий.
2. Переименуйте пакеты и сервисы под проект, если нужно.
3. Установите зависимости:

```bash
npm install
```

4. Создайте env-файлы на основе примеров:

```bash
cp apps/telegram-agent/.env.example apps/telegram-agent/.env
cp apps/telegram-iiko-mentor/.env.example apps/telegram-iiko-mentor/.env
```

5. Заполните токены:

```env
TELEGRAM_BOT_TOKEN=
OPENAI_API_KEY=
CURSOR_API_KEY=
ALLOWED_TELEGRAM_USER_IDS=
ALLOWED_TELEGRAM_USERNAMES=
```

6. Проверьте сборку:

```bash
npm run typecheck
npm run build
npm run test
```

7. Запустите локально:

```bash
npm run dev:telegram-agent
```

## 2. Что нужно адаптировать под новый проект

### Названия и роли

В основном боте проверьте:
- `apps/telegram-agent/package.json`;
- `apps/telegram-agent/src/agent/agentRunner.ts`;
- `apps/telegram-agent/src/bot/startupNotifier.ts`;
- `apps/telegram-agent/src/config.ts`.

Замените:
- название проекта;
- обращение к пользователям;
- правила общения;
- ссылки на проектные документы;
- специфичные команды вроде `iiko_status` и `iiko_incidents`, если они не нужны.

### Интеграции проекта

Если в новом проекте нет iiko Control API, можно удалить или заменить:
- `apps/telegram-agent/src/iikoControl/client.ts`;
- команды `/iiko_status`, `/iiko_incidents`;
- `IIKO_CONTROL_API_BASE_URL`;
- аналогичные части в `telegram-iiko-mentor`.

Если второй бот не нужен, можно оставить только:
- `apps/telegram-agent`;
- root scripts для `telegram-agent`;
- один systemd service.

## 3. Основной бот

Основной бот умеет:
- принимать текст;
- распознавать голосовые сообщения через OpenAI;
- слушать сообщения с вложениями и подписью;
- отвечать на вложения без подписи просьбой пояснить задачу;
- вести JSONL-журнал беседы;
- передавать контекст беседы в Cursor Agent;
- запускать Cursor Agent через Cursor SDK;
- ставить задачи в очередь;
- показывать статус очереди;
- защищать рискованные операции через approval-flow;
- создавать резервную копию файлов перед каждой Cursor Agent задачей;
- показывать последнюю резервную копию через `/backup last`;
- показывать summary изменений через `/diff last`;
- откатывать файлы последней агентской задачи через `/rollback last`;
- отправлять понятные статусы в Telegram.

## 3.1. Работа в production path

Для проекта notame.ru основной агент запускается прямо в боевой директории из
`WORKING_DIRECTORY`. Это значит, что безопасное поведение строится не на
отдельном worktree, а на обязательном snapshot перед задачей:

1. Перед запуском Cursor Agent создаётся резервная копия git-visible файлов.
2. После завершения задачи бот фиксирует список изменённых файлов.
3. В задаче сохраняются `backupId`, `changedFiles` и `diffSummary`.
4. Approver-пользователь может выполнить `/rollback last`, чтобы восстановить
   файлы последней агентской задачи из snapshot.

Резервные копии хранятся в `TELEGRAM_AGENT_BACKUP_DIR`, по умолчанию:

```env
TELEGRAM_AGENT_BACKUP_DIR=/srv/domains/notame.ru/shared/telegram-agent-backups
```

## 4. Очередь задач

Очередь нужна, чтобы несколько запросов из Telegram не запускали параллельные Cursor Agent runs.

Правило:
- одна задача выполняется;
- остальные ждут;
- пользователь видит количество задач в работе и в очереди;
- после рестарта старые `queued`/`running` задачи помечаются как `failed`.

Команда:

```text
/queue
```

## 5. Журнал бесед

Бот пишет историю в JSONL-файлы.

Типы записей:
- `text`;
- `voice`;
- `attachment`;
- `agent_context`;
- `task_status`.

Журнал используется для:
- контекста Cursor Agent;
- восстановления истории;
- аудита;
- follow-up сообщений.

## 6. Голосовые сообщения

Голосовые сообщения обрабатываются так:
1. Telegram file скачивается через Bot API.
2. Файл отправляется в OpenAI transcription API.
3. Распознанный текст сохраняется в журнал.
4. Текст передаётся в обычный обработчик сообщений.

Переменные:

```env
OPENAI_API_KEY=
VOICE_TRANSCRIPTION_MODEL=whisper-1
MAX_VOICE_DURATION_SECONDS=180
```

## 7. Вложения

Бот слушает:
- документы;
- фото;
- видео;
- аудио;
- анимации;
- стикеры;
- caption-сообщения.

Если есть caption, агент получает задачу с описанием вложения.

Если caption нет, бот отвечает:

```text
Вижу вложение. Напишите, пожалуйста, что с ним сделать или что проверить.
```

## 8. Approval-flow

Рискованные операции не выполняются сразу.

К рискованным относятся:
- деплой;
- systemd/nginx/server операции;
- удаление файлов;
- секреты, токены, пароли;
- destructive git-команды;
- force операции.

Бот создаёт pending approval и ждёт подтверждения.

## 9. Systemd деплой

На сервере рекомендуется структура:

```text
/srv/project/current
/srv/project/shared
```

Пример:

```bash
mkdir -p /srv/project/current /srv/project/shared
rsync -az ./ /srv/project/current/
cp deploy/env/telegram-agent.env.example /srv/project/shared/telegram-agent.env
```

Установите unit-файл:

```bash
mkdir -p ~/.config/systemd/user
cp deploy/systemd/notame-telegram-agent.service ~/.config/systemd/user/project-telegram-agent.service
systemctl --user daemon-reload
systemctl --user enable --now project-telegram-agent.service
```

После изменения кода:

```bash
cd /srv/project/current
npm install --include=dev
npm run build
systemctl --user restart project-telegram-agent.service
```

Проверка:

```bash
systemctl --user status project-telegram-agent.service
curl http://127.0.0.1:3000/health
```

## 10. Минимальные env-переменные

```env
TELEGRAM_BOT_TOKEN=
ALLOWED_TELEGRAM_USER_IDS=
ALLOWED_TELEGRAM_USERNAMES=
TELEGRAM_APPROVER_USER_IDS=
TELEGRAM_APPROVER_USERNAMES=
OPENAI_API_KEY=
CURSOR_API_KEY=
CURSOR_MODEL=composer-2.5
WORKING_DIRECTORY=/srv/project/current
TELEGRAM_AGENT_SHARED_DIR=/srv/project/shared
AGENT_RUN_TIMEOUT_MS=180000
MAX_AGENT_PROMPT_CHARS=6000
MAX_VOICE_DURATION_SECONDS=180
VOICE_TRANSCRIPTION_MODEL=whisper-1
AUTO_AGENT_ON_CHAT=true
```

## 11. Проверочный чек-лист

Перед передачей в работу проверьте:
- `npm run typecheck`;
- `npm run build`;
- `npm run test`;
- `/start` в Telegram;
- обычный текст;
- голосовое сообщение;
- файл с подписью;
- файл без подписи;
- `/queue`;
- approval-flow;
- рестарт systemd service;
- отсутствие зависших задач после рестарта.

## 12. Важное про безопасность

Не переносите в комплект:
- `.env`;
- реальные Telegram токены;
- `CURSOR_API_KEY`;
- `OPENAI_API_KEY`;
- server private keys;
- production logs с персональными данными.

В этой папке должны быть только исходники и примеры конфигурации.
