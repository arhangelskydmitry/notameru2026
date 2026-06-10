<?php

return [
    'enabled' => (bool) env('MAX_ENABLED', false),
    'bot_token' => env('MAX_BOT_TOKEN', ''),
    'chat_id' => env('MAX_CHAT_ID', ''),
    'api_base' => env('MAX_BOT_API_BASE', 'https://platform-api.max.ru'),
    'message_format' => env('MAX_MESSAGE_FORMAT', 'markdown'),
    'http_timeout' => (int) env('MAX_HTTP_TIMEOUT', 15),
];
