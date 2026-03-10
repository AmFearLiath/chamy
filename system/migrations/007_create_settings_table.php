<?php

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `group` VARCHAR(50) NOT NULL DEFAULT 'general',
                `key` VARCHAR(100) NOT NULL,
                value TEXT NULL,
                type VARCHAR(20) NOT NULL DEFAULT 'string',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_group_key (`group`, `key`),
                INDEX idx_group (`group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Standard-Einstellungen
        $settings = [
            ['general', 'site_name', 'Chamy CMS', 'string'],
            ['general', 'site_description', 'Ein modulares Content-Management-System', 'string'],
            ['general', 'site_locale', 'de', 'string'],
            ['general', 'site_timezone', 'Europe/Berlin', 'string'],
            ['general', 'maintenance_mode', '0', 'boolean'],
            ['content', 'default_status', 'draft', 'string'],
            ['content', 'items_per_page', '20', 'integer'],
            ['content', 'enable_revisions', '1', 'boolean'],
            ['media', 'max_upload_size', '10485760', 'integer'],
            ['media', 'allowed_extensions', 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,mp4,mp3', 'string'],
            ['media', 'upload_path', 'uploads', 'string'],
            ['theme', 'admin_theme', 'default', 'string'],
            ['theme', 'frontend_theme', 'default', 'string'],
        ];

        $stmt = $db->getPdo()->prepare(
            "INSERT IGNORE INTO {$prefix}settings (`group`, `key`, value, type) VALUES (?, ?, ?, ?)"
        );
        foreach ($settings as $row) {
            $stmt->execute($row);
        }
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}settings");
    },
];
