<?php

/**
 * Legal Manager – Migration 004
 *
 * Tabelle für Consent- / Cookie-Kategorien.
 */

return [
    'up' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}legal_consent_categories (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_key    VARCHAR(100)  NOT NULL,
                label           VARCHAR(255)  NOT NULL,
                description     TEXT          NOT NULL DEFAULT '',
                is_required     TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = technisch notwendig',
                sort_order      INT UNSIGNED  NOT NULL DEFAULT 0,
                is_active       TINYINT(1)    NOT NULL DEFAULT 1,
                locale          VARCHAR(10)   NOT NULL DEFAULT 'de',
                created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_key_locale (category_key, locale),
                INDEX idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}legal_consent_categories");
    },
];
