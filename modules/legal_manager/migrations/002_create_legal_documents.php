<?php

/**
 * Legal Manager – Migration 002
 *
 * Tabellen für Rechtsdokumente und deren modulare Blöcke.
 * Dokumente = Datenschutz, Impressum, etc. mit Versionierung.
 * Blöcke = einzelne Abschnitte innerhalb eines Dokuments.
 */

return [
    'up' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();

        // Dokumentversionen (Veröffentlichungsstände)
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}legal_documents (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                document_type   VARCHAR(50)   NOT NULL COMMENT 'privacy, imprint',
                locale          VARCHAR(10)   NOT NULL DEFAULT 'de',
                version         INT UNSIGNED  NOT NULL DEFAULT 1,
                status          VARCHAR(20)   NOT NULL DEFAULT 'draft' COMMENT 'draft, published, archived',
                content_snapshot LONGTEXT     NULL COMMENT 'HTML-Snapshot bei Veröffentlichung',
                change_note     TEXT          NULL,
                published_at    TIMESTAMP     NULL,
                published_by    INT UNSIGNED  NULL,
                created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                updated_by      INT UNSIGNED  NULL,
                INDEX idx_doctype_locale (document_type, locale),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Dokumentblöcke (einzelne Abschnitte)
        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}legal_document_blocks (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                document_type   VARCHAR(50)   NOT NULL COMMENT 'privacy, imprint',
                block_key       VARCHAR(100)  NOT NULL,
                locale          VARCHAR(10)   NOT NULL DEFAULT 'de',
                title           VARCHAR(255)  NOT NULL DEFAULT '',
                content         TEXT          NOT NULL DEFAULT '',
                sort_order      INT UNSIGNED  NOT NULL DEFAULT 0,
                is_active       TINYINT(1)    NOT NULL DEFAULT 1,
                is_system       TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = vom System erstellt',
                source_module   VARCHAR(100)  NULL COMMENT 'registrierendes Modul',
                created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_doctype_locale_sort (document_type, locale, sort_order),
                UNIQUE KEY uq_block (document_type, block_key, locale)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\Chamy\Core\Database\Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}legal_document_blocks");
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}legal_documents");
    },
];
