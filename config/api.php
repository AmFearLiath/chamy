<?php

return [
    'enabled' => true,
    'prefix'  => '/api/v1',
    'rate_limit' => [
        'enabled'  => true,
        'requests' => 60,
        'per'      => 60,
    ],
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Api-Key'],
        'max_age'         => 86400,
    ],
];
