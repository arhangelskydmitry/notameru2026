#!/bin/bash

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 ОТПРАВКА NOTAME.RU НА GITHUB"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 ШАГ 1: Создайте репозиторий на GitHub"
echo ""
echo "   1. Откройте: https://github.com/new"
echo "   2. Repository name: notame_ru_2026"
echo "   3. Description: Notame.ru - новостной портал на Laravel + Moonshine CMS"
echo "   4. Public ✅"
echo "   5. НЕ добавляйте README ❌"
echo "   6. Нажмите 'Create repository'"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
read -p "Репозиторий создан? (нажмите Enter для продолжения) " _
echo ""
echo "📤 ШАГ 2: Отправка кода на GitHub..."
echo ""

# Отправляем код
git push -u origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅✅✅ УСПЕШНО ОТПРАВЛЕНО!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "🔗 Репозиторий: https://github.com/d-arhangelsky/notame_ru_2026"
    echo ""
    echo "📊 Статистика:"
    echo "   - Коммитов: $(git rev-list --count HEAD)"
    echo "   - Файлов: $(git ls-files | wc -l | xargs)"
    echo "   - Размер: $(du -sh .git | cut -f1)"
    echo ""
else
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "❌ ОШИБКА ПРИ ОТПРАВКЕ"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Возможные причины:"
    echo "  1. Репозиторий не создан на GitHub"
    echo "  2. Неверное имя пользователя (d-arhangelsky)"
    echo "  3. Нет прав доступа"
    echo ""
    echo "Проверьте и повторите попытку"
fi
