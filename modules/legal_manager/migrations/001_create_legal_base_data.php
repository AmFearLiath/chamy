<?php

/**
 * Legal Manager – Migration 001
 *
 * Erstellt die Tabelle für rechtliche Stammdaten (Single Source of Truth).
 * Speichert Schlüssel-Wert-Paare pro Sprache.
 */

return [
    'up' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}legal_base_data (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                field_key   VARCHAR(100)  NOT NULL,
                field_value TEXT          NOT NULL DEFAULT '',
                locale      VARCHAR(10)   NOT NULL DEFAULT 'de',
                updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                updated_by  INT UNSIGNED  NULL,
                UNIQUE KEY uq_field_locale (field_key, locale)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}legal_base_data");
    },
];
