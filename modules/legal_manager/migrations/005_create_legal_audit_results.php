<?php

/**
 * Legal Manager – Migration 005
 *
 * Tabelle für Audit-Prüfergebnisse.
 */

return [
    'up' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}legal_audit_results (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                scan_id         VARCHAR(50)   NOT NULL COMMENT 'Gruppiert Ergebnisse eines Scans',
                resource_type   VARCHAR(50)   NOT NULL COMMENT 'script, stylesheet, font, image, iframe, api',
                resource_url    VARCHAR(500)  NOT NULL,
                is_external     TINYINT(1)    NOT NULL DEFAULT 0,
                is_declared     TINYINT(1)    NOT NULL DEFAULT 0,
                severity        VARCHAR(20)   NOT NULL DEFAULT 'info' COMMENT 'info, warning, critical',
                message         TEXT          NOT NULL DEFAULT '',
                source_file     VARCHAR(500)  NULL,
                source_area     VARCHAR(50)   NULL COMMENT 'admin, frontend, theme, module',
                created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_scan (scan_id),
                INDEX idx_severity (severity),
                INDEX idx_external (is_external)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}legal_audit_results");
    },
];
