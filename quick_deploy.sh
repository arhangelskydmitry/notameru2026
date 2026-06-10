#!/bin/bash
set -e

echo "========================================="
echo "  СМИ 2.0 - Deployment to GitHub"
echo "========================================="
echo ""

cd /Users/mac/Sites/notamerularavel

echo "📦 Adding files to commit..."
/usr/bin/git add .

echo ""
echo "💾 Creating commit..."
/usr/bin/git commit -m "СМИ 2.0 - Стабильная версия

✨ Новые возможности:
- Система ролей и прав доступа (авторы/редакторы/админы)
- Привязка статей к тегам
- Выбор даты и времени публикации
- Alt и Title для изображений в редакторе
- Полная оптимизация для мобильных устройств

🔧 Улучшения:
- Скрыты элементы управления для авторов
- Авторы видят только свои статьи
- Поле автора только для чтения для авторов
- Уникальные meta descriptions для всех страниц
- Open Graph для категорий, тегов, авторов
- RSS фиды оптимизированы (Яндекс Новости, Дзен, Турбо)

🐛 Исправления:
- ActivityLog корректно работает с авторизацией
- Sitemap regeneration работает без ошибок
- Проверки авторизации во всех контроллерах
- Мобильное меню полностью выезжает
- Календарь адаптирован для мобильных
- Нет горизонтального скролла на мобильных

📱 Мобильная оптимизация:
- H1: 18px, H2: 16px на мобильных
- Адаптивные изображения и таблицы
- Оптимизированные grid layouts
- Overflow handling для всех элементов

🔐 Безопасность:
- Проверка прав на редактирование постов
- Защита от несанкционированного доступа
- Правильная система аутентификации

📊 SEO:
- robots.txt настроен
- Sitemap актуальный
- Meta tags оптимизированы
- Canonical URLs
- Структурированные данные (Schema.org)

Версия: 2.0
Статус: Стабильная
Repository: https://github.com/arhangelskydmitry/notameru2026.git"

echo ""
echo "🏷️  Creating tag v2.0..."
/usr/bin/git tag -a v2.0 -m "СМИ 2.0 - Стабильная версия

Полнофункциональная CMS для новостного сайта с системой ролей,
мобильной оптимизацией, SEO и RSS интеграциями."

echo ""
echo "🚀 Pushing to GitHub..."
/usr/bin/git push -u origin main

echo ""
echo "🏷️  Pushing tags..."
/usr/bin/git push origin --tags

echo ""
echo "========================================="
echo "  ✅ SUCCESS!"
echo "========================================="
echo ""
echo "📦 Version: СМИ 2.0"
echo "🏷️  Tag: v2.0"
echo "🌐 Repository: https://github.com/arhangelskydmitry/notameru2026.git"
echo ""
echo "Next steps:"
echo "1. Visit: https://github.com/arhangelskydmitry/notameru2026"
echo "2. Create Release from tag v2.0"
echo "3. Add release notes from RELEASE_NOTES_v2.0.md"
echo ""

