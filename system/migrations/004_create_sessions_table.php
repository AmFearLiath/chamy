<?php

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}sessions (
                id VARCHAR(128) NOT NULL PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                payload TEXT NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                last_activity INT UNSIGNED NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}sessions");
    },
];
