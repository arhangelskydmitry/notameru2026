<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'channel_id' => env('TELEGRAM_CHANNEL_ID'),
    ],

    'vk' => [
        'access_token' => env('VK_ACCESS_TOKEN'),
        'group_id' => env('VK_GROUP_ID'),
    ],

    'yandex' => [
        'metrika_id' => env('YANDEX_METRIKA_ID'),
        'metrika_token' => env('YANDEX_METRIKA_TOKEN'),
        'webmaster_verification' => env('YANDEX_WEBMASTER_VERIFICATION'),
        'webmaster_token' => env('YANDEX_WEBMASTER_TOKEN'),
        'webmaster_host_id' => env('YANDEX_WEBMASTER_HOST_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GigaChat API (Сбербанк)
    |--------------------------------------------------------------------------
    |
    | Для генерации SEO-контента с использованием российского ИИ от Сбера.
    | Получить доступ: https://developers.sber.ru/portal/products/gigachat-api
    |
    */
    'gigachat' => [
        'client_id' => env('GIGACHAT_CLIENT_ID'),
        'client_secret' => env('GIGACHAT_CLIENT_SECRET'),
        'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'), // GIGACHAT_API_PERS или GIGACHAT_API_CORP
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI API
    |--------------------------------------------------------------------------
    |
    | Для генерации SEO-контента с использованием OpenAI GPT.
    | Получить ключ: https://platform.openai.com/api-keys
    |
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ChatInfo API
    |--------------------------------------------------------------------------
    |
    | Для генерации SEO-контента с использованием ChatInfo (GPT-4o).
    | Документация: https://chatinfo.ru/api-docs
    | Получить ключ: https://chatinfo.ru/subscription
    |
    */
    'chatinfo' => [
        'api_key' => env('CHATINFO_API_KEY'),
    ],

    'notame_agent' => [
        'base_url' => env('NOTAME_AGENT_URL', 'http://127.0.0.1:3020'),
        'internal_token' => env('NOTAME_AGENT_INTERNAL_TOKEN'),
        'timeout' => env('NOTAME_AGENT_TIMEOUT', 120),
    ],

];
