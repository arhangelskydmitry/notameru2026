# Пресс-карты «Нота Миру»

## Админка

- Список: `/notaadmin/press-cards`
- Выдача: `/notaadmin/press-cards/create` или кнопка «Выдать» в карточке пользователя
- Доступ: главный редактор и суперадмин

## Публичная проверка

`https://ваш-домен/press-verify/NM-2026-0001`

## Деплой

```bash
php artisan migrate --force
composer install
php artisan storage:link
```

## Номер карты

Автоформат: `NM-YYYY-NNNN`.

## PDF

Кнопка «Скачать PDF». Используется `barryvdh/laravel-dompdf`.
