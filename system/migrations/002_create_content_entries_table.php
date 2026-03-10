<?php

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}content_entries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uuid VARCHAR(36) NOT NULL,
                content_type VARCHAR(100) NOT NULL,
                locale VARCHAR(10) NOT NULL DEFAULT 'de',
                status VARCHAR(50) NOT NULL DEFAULT 'draft',
                version INT UNSIGNED NOT NULL DEFAULT 1,
                data JSON NOT NULL,
                created_by INT UNSIGNED NULL,
                updated_by INT UNSIGNED NULL,
                published_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_uuid (uuid),
                INDEX idx_content_type (content_type),
                INDEX idx_status (status),
                INDEX idx_locale (locale),
                INDEX idx_type_status (content_type, status),
                INDEX idx_created_at (created_at),
                INDEX idx_updated_at (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}content_entries");
    },
];
