<?php

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}content_versions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                content_id INT UNSIGNED NOT NULL,
                version INT UNSIGNED NOT NULL,
                data JSON NOT NULL,
                note TEXT NOT NULL DEFAULT '',
                created_by INT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_content_version (content_id, version),
                INDEX idx_content_id (content_id),
                CONSTRAINT fk_version_content FOREIGN KEY (content_id)
                    REFERENCES {$prefix}content_entries(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}content_versions");
    },
];
