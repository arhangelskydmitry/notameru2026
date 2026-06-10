<?php
/**
 * Скрипт для настройки GigaChat и ChatInfo через базу данных
 * 
 * Использование:
 * php setup_ai_services.php
 * 
 * Или через tinker:
 * php artisan tinker
 * require 'setup_ai_services.php';
 */

use App\Models\Setting;

echo "=== Настройка GigaChat и ChatInfo ===\n\n";

// Пример настройки GigaChat
echo "1. Настройка GigaChat:\n";
echo "   Setting::set('gigachat_client_id', 'ваш-client-id');\n";
echo "   Setting::set('gigachat_client_secret', 'ваш-client-secret');\n";
echo "   Setting::set('gigachat_scope', 'GIGACHAT_API_PERS');\n\n";

// Пример настройки ChatInfo
echo "2. Настройка ChatInfo:\n";
echo "   Setting::set('chatinfo_api_key', 'ваш-api-ключ');\n\n";

// Пример установки провайдера
echo "3. Установка предпочтительного провайдера:\n";
echo "   Setting::set('seo_ai_provider', 'gigachat'); // или 'chatinfo', 'openai'\n\n";

// Проверка текущих настроек
echo "=== Текущие настройки ===\n";
echo "GigaChat Client ID: " . (Setting::get('gigachat_client_id') ?: 'не установлен') . "\n";
echo "GigaChat Client Secret: " . (Setting::get('gigachat_client_secret') ? '***' : 'не установлен') . "\n";
echo "GigaChat Scope: " . (Setting::get('gigachat_scope') ?: 'не установлен') . "\n";
echo "ChatInfo API Key: " . (Setting::get('chatinfo_api_key') ? '***' : 'не установлен') . "\n";
echo "Предпочтительный провайдер: " . (Setting::get('seo_ai_provider') ?: 'не установлен') . "\n";

echo "\n=== Готово! ===\n";
