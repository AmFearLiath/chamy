<?php

/**
 * Legal Manager – Migration 003
 *
 * Tabelle für deklarierte externe Dienste / Drittanbieter.
 */

return [
    'up' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}legal_services (
                id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name              VARCHAR(255)  NOT NULL,
                provider          VARCHAR(255)  NOT NULL DEFAULT '',
                category          VARCHAR(100)  NOT NULL DEFAULT 'other',
                purpose           TEXT          NOT NULL DEFAULT '',
                data_collected    TEXT          NOT NULL DEFAULT '',
                privacy_url       VARCHAR(500)  NULL,
                consent_required  TINYINT(1)    NOT NULL DEFAULT 1,
                is_active         TINYINT(1)    NOT NULL DEFAULT 1,
                source_module     VARCHAR(100)  NULL COMMENT 'NULL = manuell, sonst Modul-ID',
                locale            VARCHAR(10)   NOT NULL DEFAULT 'de',
                created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_locale (locale)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}legal_services");
    },
];
