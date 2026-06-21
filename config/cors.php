<?php

return [
    // Разрешённые пути (все /api/* маршруты)
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Разрешённые HTTP-методы
    'allowed_methods' => ['*'],

    // Разрешённые источники. В продакшене заменить на ['https://asoft.kz']
    'allowed_origins' => ['http://localhost:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Разрешить отправку кук (нужно для Sanctum SPA)
    'supports_credentials' => true,
];
