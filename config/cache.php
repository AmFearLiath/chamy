<?php

return [
    'driver'   => $_ENV['CACHE_DRIVER'] ?? 'file',
    'prefix'   => $_ENV['CACHE_PREFIX'] ?? 'chamy_cache_',
    'lifetime' => (int) ($_ENV['CACHE_LIFETIME'] ?? 3600),
];
