<?php

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}api_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                abilities TEXT NULL,
                last_used_at TIMESTAMP NULL DEFAULT NULL,
                expires_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                UNIQUE KEY unique_token (token_hash),
                CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id)
                    REFERENCES {$prefix}users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}api_tokens");
    },
];
