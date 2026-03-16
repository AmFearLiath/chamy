<?php

declare(strict_types=1);

namespace Chamy\Core\Data;

use Chamy\Core\Database\Connection;

/**
 * LiveDataProvider – Greift auf die echte MySQL-Datenbank zu.
 *
 * Alle Queries verwenden die gleiche Prefixed-Table-Logik wie
 * die bisherigen Manager und Controller.
 */
final class LiveDataProvider implements DataProviderInterface
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    private function t(string $name): string
    {
        return $this->db->getPrefix() . $name;
    }

    /* ════════════════ Content ════════════════ */

    public function getContentEntries(string $type, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $t = $this->t('content_entries');
        $sql = "SELECT * FROM {$t} WHERE content_type = ?";
        $params = [$type];

        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND status != 'deleted'";
        }

        $sql .= " ORDER BY updated_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->fetchAll($sql, $params);

        return array_map(fn(array $r) => $this->hydrateContent($r), $rows);
    }

    public function getContentById(int $id): ?array
    {
        $t   = $this->t('content_entries');
        $row = $this->db->fetchOne("SELECT * FROM {$t} WHERE id = ?", [$id]);
        return $row ? $this->hydrateContent($row) : null;
    }

    public function getContentBySlug(string $type, string $slug): ?array
    {
        $entries = $this->getContentEntries($type, 'published', 500);
        foreach ($entries as $entry) {
            if (($entry['_data']['slug'] ?? '') === $slug) {
                return $entry;
            }
        }
        return null;
    }

    public function countContent(string $type, ?string $status = null): int
    {
        $t = $this->t('content_entries');
        $sql = "SELECT COUNT(*) as c FROM {$t} WHERE content_type = ?";
        $params = [$type];

        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND status != 'deleted'";
        }

        return (int) ($this->db->fetchOne($sql, $params)['c'] ?? 0);
    }

    public function createContent(string $type, array $data, ?int $userId = null): array
    {
        $now  = date('Y-m-d H:i:s');
        $uuid = $this->uuid();

        $entry = [
            'uuid'         => $uuid,
            'content_type' => $type,
            'locale'       => $data['_locale'] ?? 'de',
            'status'       => 'draft',
            'version'      => 1,
            'data'         => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_by'   => $userId,
            'updated_by'   => $userId,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        unset($data['_created_by'], $data['_locale']);

        $id = $this->db->insert('content_entries', $entry);
        $entry['id'] = $id;
        return $entry;
    }

    public function updateContent(int $id, array $data, ?int $userId = null): bool
    {
        $update = [
            'data'       => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ];

        return $this->db->update('content_entries', $update, 'id = :id', ['id' => $id]) > 0;
    }

    public function deleteContent(int $id): bool
    {
        return $this->db->update(
            'content_entries',
            ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $id]
        ) > 0;
    }

    /* ════════════════ Users ════════════════ */

    public function getUsers(): array
    {
        $t = $this->t('users');
        $rows = $this->db->fetchAll("SELECT id, uuid, username, email, display_name, role, is_active, last_login_at, created_at FROM {$t} ORDER BY id ASC");
        // enrich with roles array (try to read from user_roles, fallback to legacy role column)
        foreach ($rows as &$r) {
            $r['roles'] = [];
            try {
                $ur = $this->db->fetchAll(
                    "SELECT r.`key` FROM " . $this->t('user_roles') . " ur JOIN " . $this->t('roles') . " r ON r.id = ur.role_id WHERE ur.user_id = ?",
                    [(int)$r['id']]
                );
                if (!empty($ur)) {
                    $r['roles'] = array_map(fn($x) => $x['key'], $ur);
                }
            } catch (\Throwable $e) {
                // ignore, leave roles empty and fall back to role
            }
            if (empty($r['roles']) && !empty($r['role'])) {
                $r['roles'] = [$r['role']];
            }
        }
        return $rows;
    }

    public function getUserById(int $id): ?array
    {
        $t = $this->t('users');
        $row = $this->db->fetchOne("SELECT * FROM {$t} WHERE id = ?", [$id]);
        if (!$row) return null;
        // attach roles array
        $row['roles'] = [];
        try {
            $ur = $this->db->fetchAll(
                "SELECT r.`key` FROM " . $this->t('user_roles') . " ur JOIN " . $this->t('roles') . " r ON r.id = ur.role_id WHERE ur.user_id = ?",
                [$id]
            );
            if (!empty($ur)) $row['roles'] = array_map(fn($x) => $x['key'], $ur);
        } catch (\Throwable $e) {
            // ignore
        }
        if (empty($row['roles']) && !empty($row['role'])) {
            $row['roles'] = [$row['role']];
        }
        return $row;
    }

    public function getUserByUsername(string $username): ?array
    {
        $t = $this->t('users');
        return $this->db->fetchOne("SELECT * FROM {$t} WHERE username = ? LIMIT 1", [$username]);
    }

    public function createUser(array $data): int
    {
        // Accept optional 'roles' => array of role keys
        $roles = [];
        if (isset($data['roles'])) {
            $roles = is_array($data['roles']) ? $data['roles'] : array_filter(array_map('trim', explode(',', (string)$data['roles'])));
            unset($data['roles']);
        }

        // Ensure a UUID exists for the user record
        if (empty($data['uuid'])) {
            $data['uuid'] = $this->uuid();
        }

        $id = (int) $this->db->insert('users', $data);

        if (!empty($roles)) {
            $pdo = $this->db->getPdo();
            $stmt = $pdo->prepare("SELECT id FROM " . $this->t('roles') . " WHERE `key` = ? LIMIT 1");
            $ins = $pdo->prepare("INSERT IGNORE INTO " . $this->t('user_roles') . " (user_id, role_id) VALUES (?, ?)");
            foreach ($roles as $rk) {
                $stmt->execute([$rk]);
                $rid = $stmt->fetchColumn();
                if ($rid) $ins->execute([$id, (int)$rid]);
            }
        }

        return $id;
    }

    public function updateUser(int $id, array $data): bool
    {
        $roles = null;
        if (array_key_exists('roles', $data)) {
            $roles = $data['roles'];
            unset($data['roles']);
        }

        $t = $this->t('users');
        $ok = $this->db->update("{$t}", $data, 'id = :id', ['id' => $id]) > 0;

        if ($roles !== null) {
            $roleKeys = is_array($roles) ? $roles : array_filter(array_map('trim', explode(',', (string)$roles)));
            $pdo = $this->db->getPdo();
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM " . $this->t('user_roles') . " WHERE user_id = ?")->execute([$id]);
                $stmt = $pdo->prepare("SELECT id FROM " . $this->t('roles') . " WHERE `key` = ? LIMIT 1");
                $ins = $pdo->prepare("INSERT IGNORE INTO " . $this->t('user_roles') . " (user_id, role_id) VALUES (?, ?)");
                foreach ($roleKeys as $rk) {
                    $stmt->execute([$rk]);
                    $rid = $stmt->fetchColumn();
                    if ($rid) $ins->execute([$id, (int)$rid]);
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
            }
        }

        return $ok;
    }

    public function deleteUser(int $id): bool
    {
        $t = $this->t('users');
        $this->db->getPdo()->prepare("DELETE FROM {$t} WHERE id = ?")->execute([$id]);
        return true;
    }

    /* ════════════════ Settings ════════════════ */

    public function getSettings(): array
    {
        $t    = $this->t('settings');
        $rows = $this->db->fetchAll("SELECT * FROM {$t} ORDER BY `group`, `key`");

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['group']][$row['key']] = $row;
        }
        return $settings;
    }

    public function getSettingsByGroup(string $group): array
    {
        $t = $this->t('settings');
        return $this->db->fetchAll("SELECT * FROM {$t} WHERE `group` = ? ORDER BY `key`", [$group]);
    }

    public function updateSetting(int $id, string $value): bool
    {
        $t = $this->t('settings');
        return $this->db->update("{$t}", [
            'value'      => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]) > 0;
    }

    /* ════════════════ Dashboard ════════════════ */

    public function getDashboardStats(): array
    {
        $t = $this->t('content_entries');
        return [
            'pages'    => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM {$t} WHERE content_type = 'page'")['c'] ?? 0),
            'articles' => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM {$t} WHERE content_type = 'article'")['c'] ?? 0),
            'drafts'   => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM {$t} WHERE status = 'draft'")['c'] ?? 0),
        ];
    }

    public function getRecentEntries(int $limit = 10): array
    {
        $t    = $this->t('content_entries');
        $rows = $this->db->fetchAll(
            "SELECT id, content_type, status, data, created_at, updated_at FROM {$t} WHERE status != 'deleted' ORDER BY updated_at DESC LIMIT ?",
            [$limit]
        );

        return array_map(fn(array $r) => $this->hydrateContent($r), $rows);
    }

    /* ════════════════ Helpers ════════════════ */

    private function hydrateContent(array $row): array
    {
        $decoded = is_string($row['data'] ?? null)
            ? json_decode($row['data'], true)
            : ($row['data'] ?? []);

        $row['_data']  = $decoded;
        $row['state']  = $row['status'];

        // Flatten data fields onto entry for template access
        foreach ($decoded as $k => $v) {
            if (!isset($row[$k])) {
                $row[$k] = $v;
            }
        }

        return $row;
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }

    /* ════════════════ Roles & Permissions ════════════════ */

    public function getRoles(): array
    {
        $t = $this->t('roles');
        try {
            return $this->db->fetchAll("SELECT * FROM {$t} ORDER BY id ASC");
        } catch (\Throwable $e) {
            // Table missing or other DB error — return empty list to avoid fatal error in templates
            return [];
        }
    }

    public function getRoleById(int $id): ?array
    {
        $t = $this->t('roles');
        try {
            return $this->db->fetchOne("SELECT * FROM {$t} WHERE id = ?", [$id]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function createRole(array $data): int
    {
        return (int) $this->db->insert('roles', $data);
    }

    public function updateRole(int $id, array $data): bool
    {
        $t = $this->t('roles');
        return $this->db->update("{$t}", $data, 'id = :id', ['id' => $id]) > 0;
    }

    public function deleteRole(int $id): bool
    {
        $this->db->transaction(function () use ($id): void {
            $this->db->delete('role_permissions', 'role_id = :role_id', ['role_id' => $id]);
            $this->db->delete('roles', 'id = :id', ['id' => $id]);
        });
        return true;
    }

    public function roleExistsByKey(string $key, ?int $excludeId = null): bool
    {
        $t = $this->t('roles');
        $sql = "SELECT id FROM {$t} WHERE `key` = ?";
        $params = [$key];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return $this->db->fetchOne($sql, $params) !== null;
    }

    public function countUsersByRole(string $roleKey): int
    {
        // Prefer counting by user_roles mapping when available
        try {
            $sql = "SELECT COUNT(DISTINCT u.id) as c FROM " . $this->t('users') . " u "
                . "JOIN " . $this->t('user_roles') . " ur ON ur.user_id = u.id "
                . "JOIN " . $this->t('roles') . " r ON r.id = ur.role_id WHERE r.`key` = ?";
            return (int) ($this->db->fetchOne($sql, [$roleKey])['c'] ?? 0);
        } catch (\Throwable $e) {
            // Fallback to legacy column
            try {
                $t = $this->t('users');
                return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM {$t} WHERE role = ?", [$roleKey]);
            } catch (\Throwable $e) {
                return 0;
            }
        }
    }

    public function getPermissions(): array
    {
        $t = $this->t('permissions');
        try {
            return $this->db->fetchAll("SELECT * FROM {$t} ORDER BY id ASC");
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getPermissionById(int $id): ?array
    {
        $t = $this->t('permissions');
        try {
            return $this->db->fetchOne("SELECT * FROM {$t} WHERE id = ?", [$id]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function createPermission(array $data): int
    {
        return (int) $this->db->insert('permissions', $data);
    }

    public function updatePermission(int $id, array $data): bool
    {
        return $this->db->update('permissions', $data, 'id = :id', ['id' => $id]) > 0;
    }

    public function deletePermission(int $id): bool
    {
        $permission = $this->getPermissionById($id);
        if ($permission === null) {
            return false;
        }

        $this->db->transaction(function () use ($id, $permission): void {
            $this->db->delete('role_permissions', 'permission_key = :permission_key', [
                'permission_key' => $permission['key'],
            ]);
            $this->db->delete('permissions', 'id = :id', ['id' => $id]);
        });

        return true;
    }

    public function permissionExistsByKey(string $key, ?int $excludeId = null): bool
    {
        $t = $this->t('permissions');
        $sql = "SELECT id FROM {$t} WHERE `key` = ?";
        $params = [$key];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return $this->db->fetchOne($sql, $params) !== null;
    }

    public function getRolePermissions(int $roleId): array
    {
        $t = $this->t('role_permissions');
        try {
            $rows = $this->db->fetchAll("SELECT permission_key FROM {$t} WHERE role_id = ?", [$roleId]);
            return array_map(fn($r) => $r['permission_key'], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function updateRolePermissions(int $roleId, array $permissions): bool
    {
        $pdo = $this->db->getPdo();
        $t    = $this->t('role_permissions');

        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM {$t} WHERE role_id = ?")->execute([$roleId]);

            $stmt = $pdo->prepare("INSERT INTO {$t} (role_id, permission_key) VALUES (?, ?)");
            foreach ($permissions as $p) {
                $stmt->execute([$roleId, $p]);
            }

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }
}
