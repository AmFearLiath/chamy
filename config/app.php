<?php

return [
    'name'            => $_ENV['APP_NAME'] ?? 'Chamy',
    'env'             => $_ENV['APP_ENV'] ?? 'production',
    'debug'           => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'url'             => $_ENV['APP_URL'] ?? 'http://localhost',
    'locale'          => $_ENV['APP_LOCALE'] ?? 'de',
    'fallback_locale' => $_ENV['APP_FALLBACK_LOCALE'] ?? 'en',
    'timezone'        => 'Europe/Berlin',
    'version'         => '1.0.0',
];
