#!/bin/bash

echo "🔧 Создание репозитория на GitHub..."
echo ""
echo "Для создания репозитория нужен Personal Access Token"
echo "Получить токен: https://github.com/settings/tokens/new"
echo "Права: repo (Full control of private repositories)"
echo ""
read -sp "Введите GitHub Token: " GITHUB_TOKEN
echo ""
echo ""

curl -X POST https://api.github.com/user/repos \
  -H "Accept: application/vnd.github.v3+json" \
  -H "Authorization: token $GITHUB_TOKEN" \
  -d '{
    "name": "notame_ru_2026",
    "description": "Notame.ru - новостной портал на Laravel с интеграцией WordPress БД. Миграция с WordPress на Laravel + Moonshine CMS",
    "private": false,
    "has_issues": true,
    "has_projects": true,
    "has_wiki": true
  }'

echo ""
echo ""
echo "✅ Репозиторий создан!"
echo "Теперь отправляем код..."
echo ""

git push -u origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "✅✅✅ УСПЕШНО ОТПРАВЛЕНО!"
    echo "🔗 https://github.com/d-arhangelsky/notame_ru_2026"
fi
