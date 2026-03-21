<?php

/**
 * Migration 010 – Seed default admin sidebar menu structure.
 *
 * Ports all existing hard-coded sidebar entries from base.twig
 * into the MenuManager database tables so they become centrally
 * managed. Also creates the "Erweiterungen" category for modules.
 */

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $p = $db->getPrefix();
        $pdo = $db->getPdo();

        // ── Get admin-sidebar location ID ──────────────────────────────
        $loc = $db->fetchOne("SELECT id FROM {$p}menu_locations WHERE `key` = 'admin-sidebar'");
        if (!$loc) {
            return;
        }
        $locId = (int) $loc['id'];

        // Helper: create category + translations, return ID
        $createCat = function (string $key, array $labels, int $sort, bool $collapsible = false) use ($db, $p, $locId): int {
            $db->insert('menu_categories', [
                'location_id'    => $locId,
                'key'            => $key,
                'is_collapsible' => $collapsible ? 1 : 0,
                'sort_order'     => $sort,
            ]);
            $catId = (int) $pdo = $db->getPdo()->lastInsertId();
            foreach ($labels as $locale => $label) {
                $db->insert('menu_category_translations', [
                    'category_id' => $catId,
                    'locale'      => $locale,
                    'label'       => $label,
                ]);
            }
            return $catId;
        };

        // Helper: create item + translations, return ID
        $createItem = function (array $data, array $translations) use ($db, $p): int {
            if (empty($data['uuid'])) {
                $data['uuid'] = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            }
            $db->insert('menu_items', $data);
            $itemId = (int) $db->getPdo()->lastInsertId();
            foreach ($translations as $locale => $label) {
                $db->insert('menu_item_translations', [
                    'item_id' => $itemId,
                    'locale'  => $locale,
                    'label'   => $label,
                ]);
            }
            return $itemId;
        };

        // ── 1. "Hauptmenü" category ────────────────────────────────────
        $catMain = $createCat('main', ['de' => 'Hauptmenü', 'en' => 'Main Menu'], 0);

        $createItem([
            'category_id'  => $catMain,
            'key'          => 'admin.dashboard',
            'source'       => 'core',
            'source_ref'   => 'system',
            'target_type'  => 'route',
            'target_value' => '/admin',
            'icon'         => 'layout-dashboard',
            'sort_order'   => 0,
            'permission'   => 'admin.access',
        ], ['de' => 'Dashboard', 'en' => 'Dashboard']);

        // ── 2. "Inhalte" category ──────────────────────────────────────
        $catContent = $createCat('content', ['de' => 'Inhalte', 'en' => 'Content'], 10);

        $contentTypes = [
            ['admin.content.article', '/admin/content/article', 'file-text', 0, 'Artikel', 'Articles'],
            ['admin.content.page',    '/admin/content/page',    'file-text', 10, 'Seiten', 'Pages'],
            ['admin.content.media',   '/admin/content/media_entry', 'photo', 20, 'Medien', 'Media'],
            ['admin.content.docs',    '/admin/content/documentation', 'book', 30, 'Dokumentationen', 'Documentation'],
        ];
        foreach ($contentTypes as [$key, $target, $icon, $sort, $de, $en]) {
            $createItem([
                'category_id'  => $catContent,
                'key'          => $key,
                'source'       => 'core',
                'source_ref'   => 'system',
                'target_type'  => 'route',
                'target_value' => $target,
                'icon'         => $icon,
                'sort_order'   => $sort,
                'permission'   => 'content.list',
            ], ['de' => $de, 'en' => $en]);
        }

        $createItem([
            'category_id'  => $catContent,
            'key'          => 'admin.editor',
            'source'       => 'core',
            'source_ref'   => 'system',
            'target_type'  => 'route',
            'target_value' => '/admin/editor',
            'icon'         => 'brush',
            'sort_order'   => 40,
            'permission'   => 'content.edit',
        ], ['de' => 'Editor', 'en' => 'Editor']);

        // ── 3. "Erweiterungen" category (for modules) ─────────────────
        $createCat('extensions', ['de' => 'Erweiterungen', 'en' => 'Extensions'], 50, true);

        // ── 4. "System" category ───────────────────────────────────────
        $catSystem = $createCat('system', ['de' => 'System', 'en' => 'System'], 90);

        $systemItems = [
            ['admin.modules',  '/admin/modules',  'box',      0,  'Module',        'Modules',  'system.mods'],
            ['admin.themes',   '/admin/themes',    'palette',  10, 'Themes',        'Themes',   'system.themes'],
            ['admin.menus',    '/admin/menus',     'menu-2',   20, 'Menüs',         'Menus',    'system.manage'],
            ['admin.users',    '/admin/users',     'users',    30, 'Benutzer',      'Users',    'users.manage'],
            ['admin.settings', '/admin/settings',  'settings', 40, 'Einstellungen', 'Settings', 'system.manage'],
            ['admin.trash',    '/admin/trash',     'trash',    50, 'Papierkorb',    'Trash',    'admin.access'],
        ];
        foreach ($systemItems as [$key, $target, $icon, $sort, $de, $en, $perm]) {
            $createItem([
                'category_id'  => $catSystem,
                'key'          => $key,
                'source'       => 'core',
                'source_ref'   => 'system',
                'target_type'  => 'route',
                'target_value' => $target,
                'icon'         => $icon,
                'sort_order'   => $sort,
                'permission'   => $perm,
            ], ['de' => $de, 'en' => $en]);
        }

        // ── Audit log entry ────────────────────────────────────────────
        $db->insert('menu_audit_log', [
            'item_id' => null,
            'action'  => 'imported',
            'actor'   => 'migration:010',
            'details' => json_encode([
                'description' => 'Initial seed of admin sidebar menu structure from base.twig',
                'categories'  => 4,
                'items'       => count($contentTypes) + count($systemItems) + 2,
            ]),
        ]);
    },

    'down' => function (Connection $db): void {
        $p = $db->getPrefix();
        // Remove all seeded core items
        $db->query("DELETE FROM {$p}menu_items WHERE source = 'core' AND source_ref = 'system'");
        // Remove seeded categories for admin-sidebar
        $loc = $db->fetchOne("SELECT id FROM {$p}menu_locations WHERE `key` = 'admin-sidebar'");
        if ($loc) {
            $db->query("DELETE FROM {$p}menu_categories WHERE location_id = ?", [$loc['id']]);
        }
    },
];
