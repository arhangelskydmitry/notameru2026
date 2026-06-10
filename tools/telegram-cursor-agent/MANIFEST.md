# Telegram Bot Kit Manifest

Эта папка содержит переносимый комплект кода и инструкций для интеграции Telegram-бота с Cursor Agent в другой проект.

## Содержимое

- `README.md` — инструкция по переносу, настройке и деплою.
- `package.json` — root workspace scripts для двух ботов.
- `package-lock.json` — lock-файл после проверки установки зависимостей.
- `apps/telegram-agent` — основной Telegram-бот-разработчик.
- `apps/telegram-iiko-mentor` — дополнительный бот-наставник/тестировщик.
- `deploy/env` — примеры переменных окружения без секретов.
- `deploy/systemd` — примеры systemd user services.
- `docs/telegram-bot-specification.md` — полная спецификация поведения бота.

## Что не включено

- реальные `.env`;
- токены Telegram;
- ключи OpenAI;
- ключи Cursor;
- `dist`;
- `node_modules`;
- production логи.

## Проверка комплекта

Комплект проверен командами:

```bash
npm install
npm run typecheck
```

## Что адаптировать в новом проекте

- названия ботов и package names;
- тексты prompt в `agentRunner.ts`;
- env-переменные;
- systemd unit names;
- проектные команды вроде `iiko_status`, если они не нужны;
- интеграции внешних API.
