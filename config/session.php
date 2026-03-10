<?php

return [
    'driver'   => $_ENV['SESSION_DRIVER'] ?? 'file',
    'name'     => $_ENV['SESSION_NAME'] ?? 'chamy_session',
    'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),
];
