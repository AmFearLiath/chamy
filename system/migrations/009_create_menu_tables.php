<?php

/**
 * Migration 009 – Create menu system tables.
 *
 * Establishes the core MenuManager schema:
 *   - menu_locations  : Named rendering slots (admin-sidebar, frontend-main, etc.)
 *   - menu_categories : Grouping/section headers within locations
 *   - menu_items      : Individual menu entries (tree structure via parent_id)
 *   - menu_item_translations : Locale-specific labels
 *   - menu_overrides   : Manual admin edits that survive re-sync
 *   - menu_audit_log   : Change tracking for portierung, sync, manual edits
 */

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $p = $db->getPrefix();
        $pdo = $db->getPdo();

        // ── Locations / Slots ──────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$p}menu_locations (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `key`       VARCHAR(100) NOT NULL UNIQUE    COMMENT 'Stable tech key, e.g. admin-sidebar',
                label       VARCHAR(255) NOT NULL           COMMENT 'Human-readable name',
                description TEXT                            COMMENT 'Purpose description',
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                sort_order  INT NOT NULL DEFAULT 0,
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Categories / Sections ──────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$p}menu_categories (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                location_id INT UNSIGNED NOT NULL           COMMENT 'FK → menu_locations',
                `key`       VARCHAR(100) NOT NULL           COMMENT 'Stable tech key, e.g. main, content, system',
                icon        VARCHAR(255) DEFAULT NULL       COMMENT 'Icon identifier or SVG',
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                is_collapsible TINYINT(1) NOT NULL DEFAULT 0,
                sort_order  INT NOT NULL DEFAULT 0,
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_location_key (location_id, `key`),
                CONSTRAINT fk_menucat_location FOREIGN KEY (location_id)
                    REFERENCES {$p}menu_locations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Menu Items (tree via parent_id) ────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$p}menu_items (
                id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uuid            VARCHAR(36) NOT NULL UNIQUE     COMMENT 'Global stable identifier',
                category_id     INT UNSIGNED NOT NULL            COMMENT 'FK → menu_categories',
                parent_id       INT UNSIGNED DEFAULT NULL        COMMENT 'Self-ref for nesting',
                `key`           VARCHAR(150) NOT NULL            COMMENT 'Stable tech key, globally unique',

                -- Source tracking
                source          ENUM('core','module','theme','manual','import') NOT NULL DEFAULT 'manual',
                source_ref      VARCHAR(150) DEFAULT NULL        COMMENT 'e.g. module ID or theme ID',

                -- Target
                target_type     ENUM('route','url','content','separator','heading','container','action') NOT NULL DEFAULT 'route',
                target_value    VARCHAR(500) DEFAULT NULL         COMMENT 'Route name, URL, or content ref',
                target_params   JSON DEFAULT NULL                 COMMENT 'Route params as JSON',

                -- Display
                icon            VARCHAR(500) DEFAULT NULL         COMMENT 'Icon CSS class, SVG, or identifier',
                css_class       VARCHAR(255) DEFAULT NULL         COMMENT 'Extra CSS classes',
                badge           VARCHAR(100) DEFAULT NULL         COMMENT 'Optional badge text/count',

                -- Behaviour
                is_active       TINYINT(1) NOT NULL DEFAULT 1,
                is_visible      TINYINT(1) NOT NULL DEFAULT 1,
                is_collapsible  TINYINT(1) NOT NULL DEFAULT 0    COMMENT 'For parent nodes',
                open_in_new_tab TINYINT(1) NOT NULL DEFAULT 0,

                -- Permissions
                permission      VARCHAR(150) DEFAULT NULL         COMMENT 'Required permission key',
                visibility_rule ENUM('all','authenticated','guest','permission','role') NOT NULL DEFAULT 'all',
                visibility_value VARCHAR(255) DEFAULT NULL         COMMENT 'Role name or extra param',

                -- Override tracking
                is_manual       TINYINT(1) NOT NULL DEFAULT 0     COMMENT '1 = created or changed manually',
                is_synced       TINYINT(1) NOT NULL DEFAULT 1     COMMENT '0 = not yet synced from source',
                override_fields JSON DEFAULT NULL                  COMMENT 'List of manually overridden field names',
                is_hidden       TINYINT(1) NOT NULL DEFAULT 0     COMMENT '1 = intentionally hidden by admin',

                -- Ordering
                sort_order      INT NOT NULL DEFAULT 0,

                -- Condition
                requires_module VARCHAR(100) DEFAULT NULL          COMMENT 'Only show when module is active',
                requires_feature VARCHAR(100) DEFAULT NULL         COMMENT 'Feature flag dependency',

                -- Meta
                meta            JSON DEFAULT NULL                  COMMENT 'Arbitrary extra data',

                created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                UNIQUE KEY uq_item_key (`key`),
                INDEX idx_category (category_id),
                INDEX idx_parent (parent_id),
                INDEX idx_source (source, source_ref),
                INDEX idx_sort (category_id, sort_order),
                CONSTRAINT fk_menuitem_category FOREIGN KEY (category_id)
                    REFERENCES {$p}menu_categories(id) ON DELETE CASCADE,
                CONSTRAINT fk_menuitem_parent FOREIGN KEY (parent_id)
                    REFERENCES {$p}menu_items(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Translations ───────────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$p}menu_item_translations (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                item_id     INT UNSIGNED NOT NULL,
                locale      VARCHAR(10) NOT NULL DEFAULT 'de',
                label       VARCHAR(255) NOT NULL,
                tooltip     VARCHAR(500) DEFAULT NULL,
                UNIQUE KEY uq_item_locale (item_id, locale),
                CONSTRAINT fk_menutrans_item FOREIGN KEY (item_id)
                    REFERENCES {$p}menu_items(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Category translations ──────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$p}menu_category_translations (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_id INT UNSIGNED NOT NULL,
                locale      VARCHAR(10) NOT NULL DEFAULT 'de',
                label       VARCHAR(255) NOT NULL,
                UNIQUE KEY uq_cat_locale (category_id, locale),
                CONSTRAINT fk_menucattrans_cat FOREIGN KEY (category_id)
                    REFERENCES {$p}menu_categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Audit Log ──────────────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS {$p}menu_audit_log (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                item_id     INT UNSIGNED DEFAULT NULL        COMMENT 'FK or NULL for global ops',
                action      VARCHAR(50) NOT NULL             COMMENT 'created, updated, deleted, synced, imported, reordered',
                actor       VARCHAR(100) DEFAULT NULL        COMMENT 'User or system identifier',
                details     JSON DEFAULT NULL                COMMENT 'Before/after diff or description',
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_item (item_id),
                INDEX idx_action (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Seed default locations ─────────────────────────────────────
        $stmt = $pdo->prepare("INSERT IGNORE INTO {$p}menu_locations (`key`, label, description, sort_order) VALUES (?, ?, ?, ?)");
        $locations = [
            ['admin-sidebar',    'Admin Sidebar',        'Main admin navigation sidebar',  0],
            ['admin-topbar',     'Admin Topbar',         'Top bar quick-links',            1],
            ['frontend-main',    'Frontend Main Nav',    'Primary frontend navigation',    2],
            ['frontend-footer',  'Frontend Footer',      'Footer navigation links',        3],
            ['frontend-account', 'Account Menu',         'User account dropdown',          4],
        ];
        foreach ($locations as $loc) {
            $stmt->execute($loc);
        }
    },

    'down' => function (Connection $db): void {
        $p = $db->getPrefix();
        $pdo = $db->getPdo();
        $pdo->exec("DROP TABLE IF EXISTS {$p}menu_audit_log");
        $pdo->exec("DROP TABLE IF EXISTS {$p}menu_category_translations");
        $pdo->exec("DROP TABLE IF EXISTS {$p}menu_item_translations");
        $pdo->exec("DROP TABLE IF EXISTS {$p}menu_items");
        $pdo->exec("DROP TABLE IF EXISTS {$p}menu_categories");
        $pdo->exec("DROP TABLE IF EXISTS {$p}menu_locations");
    },
];
