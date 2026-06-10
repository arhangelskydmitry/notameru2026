<?php

return [
    'notame_agent' => [
        'base_url' => env('NOTAME_AGENT_URL', 'http://127.0.0.1:3020'),
        'internal_token' => env('NOTAME_AGENT_INTERNAL_TOKEN'),
        'timeout' => (int) env('NOTAME_AGENT_TIMEOUT', 120),
    ],
];
