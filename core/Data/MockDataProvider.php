<?php

declare(strict_types=1);

namespace Chamy\Core\Data;

/**
 * MockDataProvider – Array-basierter Provider, der Seed-Dateien
 * aus data/mock/ lädt. Struktur identisch zur Live-Datenbank.
 *
 * Schreiboperationen arbeiten auf dem In-Memory-Array (Session-Dauer).
 */
final class MockDataProvider implements DataProviderInterface
{
    private string $seedPath;

    private array $contentEntries;
    private array $users;
    private array $settings;
    private array $roles;
    private array $permissions;
    private array $rolePermissions;

    private int $nextContentId;
    private int $nextUserId;
    private int $nextRoleId;

    public function __construct(string $seedPath)
    {
        $this->seedPath = rtrim($seedPath, '/\\');
        $this->loadSeeds();
    }

    private function loadSeeds(): void
    {
        $this->contentEntries = $this->loadFile('content_entries.php');
        $this->users          = $this->loadFile('users.php');
        $this->settings       = $this->loadFile('settings.php');
        $this->roles           = $this->loadFile('roles.php');
        $this->permissions     = $this->loadFile('permissions.php');
        $this->rolePermissions = $this->loadFile('role_permissions.php');

        $this->nextContentId = $this->maxId($this->contentEntries) + 1;
        $this->nextUserId    = $this->maxId($this->users) + 1;
        $this->nextRoleId    = $this->maxId($this->roles) + 1;
    }

    private function loadFile(string $file): array
    {
        $path = $this->seedPath . DIRECTORY_SEPARATOR . $file;
        return file_exists($path) ? (require $path) : [];
    }

    private function maxId(array $items): int
    {
        return array_reduce($items, fn(int $carry, array $item) => max($carry, (int)($item['id'] ?? 0)), 0);
    }

    /* ════════════════ Content ════════════════ */

    public function getContentEntries(string $type, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->contentEntries, function (array $e) use ($type, $status) {
            if ($e['content_type'] !== $type) return false;
            if ($status !== null) return $e['status'] === $status;
            return $e['status'] !== 'deleted';
        });

        // Sort by updated_at DESC
        usort($filtered, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));

        $sliced = array_slice($filtered, $offset, $limit);
        return array_map(fn(array $r) => $this->hydrateContent($r), $sliced);
    }

    public function getContentById(int $id): ?array
    {
        foreach ($this->contentEntries as $e) {
            if ((int)$e['id'] === $id) {
                return $this->hydrateContent($e);
            }
        }
        return null;
    }

    public function getContentBySlug(string $type, string $slug): ?array
    {
        foreach ($this->contentEntries as $e) {
            if ($e['content_type'] !== $type || $e['status'] !== 'published') continue;

            $decoded = is_string($e['data']) ? json_decode($e['data'], true) : ($e['data'] ?? []);
            if (($decoded['slug'] ?? '') === $slug) {
                return $this->hydrateContent($e);
            }
        }
        return null;
    }

    public function countContent(string $type, ?string $status = null): int
    {
        return count(array_filter($this->contentEntries, function (array $e) use ($type, $status) {
            if ($e['content_type'] !== $type) return false;
            if ($status !== null) return $e['status'] === $status;
            return $e['status'] !== 'deleted';
        }));
    }

    public function createContent(string $type, array $data, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $entry = [
            'id'           => $this->nextContentId++,
            'uuid'         => $this->uuid(),
            'content_type' => $type,
            'locale'       => $data['_locale'] ?? 'de',
            'status'       => 'draft',
            'version'      => 1,
            'data'         => json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_by'   => $userId,
            'updated_by'   => $userId,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        $this->contentEntries[] = $entry;
        return $entry;
    }

    public function updateContent(int $id, array $data, ?int $userId = null): bool
    {
        foreach ($this->contentEntries as &$e) {
            if ((int)$e['id'] === $id) {
                $e['data']       = json_encode($data, JSON_UNESCAPED_UNICODE);
                $e['updated_at'] = date('Y-m-d H:i:s');
                $e['updated_by'] = $userId;
                return true;
            }
        }
        return false;
    }

    public function deleteContent(int $id): bool
    {
        foreach ($this->contentEntries as &$e) {
            if ((int)$e['id'] === $id) {
                $e['status']     = 'deleted';
                $e['updated_at'] = date('Y-m-d H:i:s');
                return true;
            }
        }
        return false;
    }

    /* ════════════════ Users ════════════════ */

    public function getUsers(): array
    {
        return array_map(function (array $u) {
            // Identische Spalten wie Live (ohne password_hash)
            $roles = [];
            if (isset($u['roles']) && is_array($u['roles'])) {
                $roles = $u['roles'];
            } elseif (!empty($u['role'])) {
                $roles = [$u['role']];
            }
            return [
                'id'            => $u['id'],
                'uuid'          => $u['uuid'],
                'username'      => $u['username'],
                'email'         => $u['email'],
                'display_name'  => $u['display_name'],
                'role'          => $u['role'] ?? ($roles[0] ?? ''),
                'roles'         => $roles,
                'is_active'     => $u['is_active'],
                'last_login_at' => $u['last_login_at'],
                'created_at'    => $u['created_at'],
            ];
        }, $this->users);
    }

    public function getUserById(int $id): ?array
    {
        foreach ($this->users as $u) {
            if ((int)$u['id'] === $id) return $u;
        }
        return null;
    }

    public function getUserByUsername(string $username): ?array
    {
        foreach ($this->users as $u) {
            if ($u['username'] === $username) return $u;
        }
        return null;
    }

    public function createUser(array $data): int
    {
        $id = $this->nextUserId++;
        // allow 'roles' array; keep legacy 'role' for compatibility
        $roles = [];
        if (isset($data['roles'])) {
            $roles = is_array($data['roles']) ? $data['roles'] : array_filter(array_map('trim', explode(',', (string)$data['roles'])));
            unset($data['roles']);
        }

        $data['id']         = $id;
        $data['uuid']       = $this->uuid();
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        if (!empty($roles)) {
            $data['roles'] = $roles;
            $data['role'] = $roles[0] ?? ($data['role'] ?? '');
        }
        $this->users[]      = $data;
        return $id;
    }

    public function updateUser(int $id, array $data): bool
    {
        foreach ($this->users as &$u) {
            if ((int)$u['id'] === $id) {
                if (isset($data['roles'])) {
                    $roles = is_array($data['roles']) ? $data['roles'] : array_filter(array_map('trim', explode(',', (string)$data['roles'])));
                    $u['roles'] = $roles;
                    $u['role'] = $roles[0] ?? ($u['role'] ?? '');
                    unset($data['roles']);
                }
                $u = array_merge($u, $data);
                return true;
            }
        }
        return false;
    }

    public function deleteUser(int $id): bool
    {
        $this->users = array_values(array_filter(
            $this->users,
            fn(array $u) => (int)$u['id'] !== $id
        ));
        return true;
    }

    /* ════════════════ Roles & Permissions ════════════════ */

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getRoleById(int $id): ?array
    {
        foreach ($this->roles as $r) {
            if ((int)$r['id'] === $id) return $r;
        }
        return null;
    }

    public function createRole(array $data): int
    {
        $id = $this->nextRoleId++;
        $data['id']         = $id;
        $data['uuid']       = $this->uuid();
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->roles[]      = $data;
        return $id;
    }

    public function updateRole(int $id, array $data): bool
    {
        foreach ($this->roles as &$r) {
            if ((int)$r['id'] === $id) {
                $r = array_merge($r, $data);
                return true;
            }
        }
        return false;
    }

    public function deleteRole(int $id): bool
    {
        $this->roles = array_values(array_filter(
            $this->roles,
            fn(array $r) => (int)$r['id'] !== $id
        ));

        // remove role_permissions entries
        $this->rolePermissions = array_values(array_filter(
            $this->rolePermissions,
            fn(array $rp) => (int)$rp['role_id'] !== $id
        ));

        return true;
    }

    public function roleExistsByKey(string $key, ?int $excludeId = null): bool
    {
        foreach ($this->roles as $role) {
            if ($role['key'] !== $key) {
                continue;
            }

            if ($excludeId !== null && (int) $role['id'] === $excludeId) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function countUsersByRole(string $roleKey): int
    {
        return count(array_filter(
            $this->users,
            fn(array $user) => in_array($roleKey, (array)($user['roles'] ?? [$user['role'] ?? '']), true)
        ));
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPermissionById(int $id): ?array
    {
        foreach ($this->permissions as $permission) {
            if ((int) $permission['id'] === $id) {
                return $permission;
            }
        }

        return null;
    }

    public function createPermission(array $data): int
    {
        $nextId = empty($this->permissions)
            ? 1
            : (max(array_map(fn(array $permission): int => (int) $permission['id'], $this->permissions)) + 1);

        $data['id'] = $nextId;
        $this->permissions[] = $data;

        return $nextId;
    }

    public function updatePermission(int $id, array $data): bool
    {
        foreach ($this->permissions as &$permission) {
            if ((int) $permission['id'] === $id) {
                $permission = array_merge($permission, $data);
                return true;
            }
        }

        return false;
    }

    public function deletePermission(int $id): bool
    {
        $permission = $this->getPermissionById($id);
        if ($permission === null) {
            return false;
        }

        $this->permissions = array_values(array_filter(
            $this->permissions,
            fn(array $item) => (int) $item['id'] !== $id
        ));

        $this->rolePermissions = array_values(array_filter(
            $this->rolePermissions,
            fn(array $rp) => $rp['permission_key'] !== $permission['key']
        ));

        return true;
    }

    public function permissionExistsByKey(string $key, ?int $excludeId = null): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission['key'] !== $key) {
                continue;
            }

            if ($excludeId !== null && (int) $permission['id'] === $excludeId) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getRolePermissions(int $roleId): array
    {
        $out = [];
        foreach ($this->rolePermissions as $rp) {
            if ((int)$rp['role_id'] === $roleId) $out[] = $rp['permission_key'];
        }
        return $out;
    }

    public function updateRolePermissions(int $roleId, array $permissions): bool
    {
        // remove existing
        $this->rolePermissions = array_values(array_filter(
            $this->rolePermissions,
            fn(array $rp) => (int)$rp['role_id'] !== $roleId
        ));

        // add new
        foreach ($permissions as $p) {
            $this->rolePermissions[] = [
                'role_id'        => $roleId,
                'permission_key' => $p,
            ];
        }

        return true;
    }

    /* ════════════════ Settings ════════════════ */

    public function getSettings(): array
    {
        $grouped = [];
        foreach ($this->settings as $s) {
            $grouped[$s['group']][$s['key']] = $s;
        }
        return $grouped;
    }

    public function getSettingsByGroup(string $group): array
    {
        return array_values(array_filter(
            $this->settings,
            fn(array $s) => $s['group'] === $group
        ));
    }

    public function updateSetting(int $id, string $value): bool
    {
        foreach ($this->settings as &$s) {
            if ((int)$s['id'] === $id) {
                $s['value']      = $value;
                $s['updated_at'] = date('Y-m-d H:i:s');
                return true;
            }
        }
        return false;
    }

    /* ════════════════ Dashboard ════════════════ */

    public function getDashboardStats(): array
    {
        $pages = $articles = $drafts = 0;
        foreach ($this->contentEntries as $e) {
            if ($e['status'] === 'deleted') continue;
            if ($e['content_type'] === 'page')    $pages++;
            if ($e['content_type'] === 'article') $articles++;
            if ($e['status'] === 'draft')         $drafts++;
        }
        return compact('pages', 'articles', 'drafts');
    }

    public function getRecentEntries(int $limit = 10): array
    {
        $active = array_filter($this->contentEntries, fn(array $e) => $e['status'] !== 'deleted');
        usort($active, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));
        $sliced = array_slice($active, 0, $limit);
        return array_map(fn(array $r) => $this->hydrateContent($r), $sliced);
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
}
