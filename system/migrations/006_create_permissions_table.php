<?php
/**
 * Migration 006 – Create roles, permissions and role_permissions tables.
 *
 * Replaces the old flat permissions table (role, permission, granted)
 * with the new normalized schema used by LiveDataProvider.
 */

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        // Drop old schema if it exists (had columns: role, permission, granted)
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}permissions");

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                uuid VARCHAR(64) NOT NULL,
                `key` VARCHAR(100) NOT NULL UNIQUE,
                `name` VARCHAR(255) NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(150) NOT NULL UNIQUE,
                description TEXT,
                `group` VARCHAR(100) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS {$prefix}role_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT NOT NULL,
                permission_key VARCHAR(150) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (role_id),
                INDEX (permission_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed default roles
        $roles = [
            ['admin', 'Administrator', 'Volle Rechte'],
            ['editor', 'Redakteur', 'Inhalte erstellen und bearbeiten'],
            ['viewer', 'Betrachter', 'Inhalte ansehen'],
        ];

        $stmt = $db->getPdo()->prepare(
            "INSERT IGNORE INTO {$prefix}roles (uuid, `key`, `name`, description) VALUES (UUID(), ?, ?, ?)"
        );
        foreach ($roles as [$key, $name, $desc]) {
            $stmt->execute([$key, $name, $desc]);
        }

        // Seed default permissions
        $perms = [
            ['admin.access', 'Admin-Bereich aufrufen', 'admin'],
            ['admin.dashboard', 'Dashboard anzeigen', 'admin'],
            ['content.list', 'Inhalte auflisten', 'content'],
            ['content.create', 'Inhalte erstellen', 'content'],
            ['content.edit', 'Inhalte bearbeiten', 'content'],
            ['content.delete', 'Inhalte löschen', 'content'],
            ['content.publish', 'Inhalte veröffentlichen', 'content'],
            // old keys removed: use `system.mods` and `system.themes`
            ['system.mods', 'Benutzer darf Mods installieren, verwalten, bearbeiten, konfigurieren', 'system'],
            ['system.themes', 'Benutzer darf Themes installieren, verwalten, bearbeiten, konfigurieren', 'system'],
            ['users.manage', 'Benutzer verwalten', 'users'],
            ['roles.manage', 'Rollen verwalten', 'users'],
            ['permissions.manage', 'Berechtigungen verwalten', 'users'],
            ['system.manage', 'Einstellungen verwalten', 'settings'],
        ];

        $stmt = $db->getPdo()->prepare(
            "INSERT IGNORE INTO {$prefix}permissions (`key`, description, `group`) VALUES (?, ?, ?)"
        );
        foreach ($perms as [$key, $desc, $group]) {
            $stmt->execute([$key, $desc, $group]);
        }
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}role_permissions");
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}permissions");
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}roles");
    },
];
