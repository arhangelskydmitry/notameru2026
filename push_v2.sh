#!/bin/bash

echo "==================================="
echo "  СМИ 2.0 - GitHub Deployment"
echo "==================================="
echo ""

cd /Users/mac/Sites/notamerularavel || exit 1

echo "Step 1: Adding files..."
git add . || exit 1

echo ""
echo "Step 2: Creating commit..."
git commit -m "СМИ 2.0 - Стабильная версия

Новые возможности:
- Система ролей и прав доступа
- Привязка статей к тегам
- Выбор даты и времени публикации
- Alt и Title для изображений
- Полная мобильная оптимизация

Улучшения:
- Авторы видят только свои статьи
- SEO оптимизация всех страниц
- RSS фиды оптимизированы
- Исправлены критические баги

Исправления:
- ActivityLog работает корректно
- Sitemap regeneration без ошибок
- JavaScript ошибки в админке
- Мобильная версия полностью адаптирована

Версия: 2.0" || exit 1

echo ""
echo "Step 3: Creating tag v2.0..."
git tag -a v2.0 -m "СМИ 2.0 - Стабильная версия" || echo "Tag already exists"

echo ""
echo "Step 4: Pushing to GitHub..."
git push origin main || exit 1

echo ""
echo "Step 5: Pushing tags..."
git push origin --tags || echo "Tags already pushed"

echo ""
echo "==================================="
echo "  ✅ DEPLOYMENT SUCCESSFUL!"
echo "==================================="
echo ""
echo "Repository: https://github.com/arhangelskydmitry/notameru2026"
echo "Version: v2.0"
echo ""
echo "Next: Create Release on GitHub"
echo ""

