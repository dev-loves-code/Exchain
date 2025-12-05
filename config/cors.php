<?php

return [
    'paths' => ['api/*', 'broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173','http://localhost:5174'
],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
