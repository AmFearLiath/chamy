<?php
/**
 * Migration 007 – Create user_roles table and backfill from users.role
 */

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS {$prefix}user_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Backfill existing users.role values into user_roles when possible
        try {
            $pdo = $db->getPdo();
            // Find users with non-empty role value
            $users = $pdo->query("SELECT id, role FROM {$prefix}users WHERE role IS NOT NULL AND role != ''")->fetchAll(PDO::FETCH_ASSOC);
            if ($users) {
                $roleStmt = $pdo->prepare("SELECT id FROM {$prefix}roles WHERE `key` = ? LIMIT 1");
                $insStmt = $pdo->prepare("INSERT IGNORE INTO {$prefix}user_roles (user_id, role_id) VALUES (?, ?)");
                foreach ($users as $u) {
                    $roleKey = $u['role'] ?? '';
                    if ($roleKey === '') continue;
                    $roleStmt->execute([$roleKey]);
                    $rid = $roleStmt->fetchColumn();
                    if ($rid) {
                        $insStmt->execute([(int)$u['id'], (int)$rid]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore backfill errors
        }
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$prefix}user_roles");
    },
];
