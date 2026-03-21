<?php

/**
 * Legal Manager – Migration 006
 *
 * Tabelle für datenschutzkonforme Zugriffs-/Interaktionsstatistik.
 * IP und User-Agent werden nur als Hash gespeichert.
 */

return [
    'up' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}legal_stats (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                page_type       VARCHAR(50)   NOT NULL COMMENT 'privacy, imprint, consent',
                event_type      VARCHAR(50)   NOT NULL DEFAULT 'view' COMMENT 'view, consent_accept, consent_reject, consent_customize',
                locale          VARCHAR(10)   NOT NULL DEFAULT 'de',
                ip_hash         VARCHAR(64)   NULL COMMENT 'SHA-256 Hash, anonymisiert',
                user_agent_hash VARCHAR(64)   NULL,
                created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_page_event (page_type, event_type),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}legal_stats");
    },
];
