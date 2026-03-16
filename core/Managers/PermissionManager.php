<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Database\Connection;

final class PermissionManager implements ManagerInterface
{
    private ?Connection $db;

    /** @var array<string, array<string>> */
    private array $rolePermissions = [];

    /** @var array<string, array{label: string, group: string}> */
    private array $registeredPermissions = [];

    public function __construct(?Connection $db = null)
    {
        $this->db = $db;
    }

    public function getName(): string
    {
        return 'permission';
    }

    public function boot(): void
    {
        $this->registerDefaults();
        $this->loadDatabasePermissions();
    }

    public function definePermission(string $permission, string $label, string $group = 'system'): void
    {
        $this->registeredPermissions[$permission] = [
            'label' => $label,
            'group' => $group,
        ];
    }

    public function grantToRole(string $role, string $permission): void
    {
        if (!isset($this->rolePermissions[$role])) {
            $this->rolePermissions[$role] = [];
        }

        if (!in_array($permission, $this->rolePermissions[$role], true)) {
            $this->rolePermissions[$role][] = $permission;
        }
    }

    public function revokeFromRole(string $role, string $permission): void
    {
        if (!isset($this->rolePermissions[$role])) {
            return;
        }

        $this->rolePermissions[$role] = array_values(
            array_filter($this->rolePermissions[$role], fn(string $p) => $p !== $permission)
        );
    }

    public function roleHas(string $role, string $permission): bool
    {
        if ($role === 'admin') {
            return true;
        }

        return in_array($permission, $this->rolePermissions[$role] ?? [], true);
    }

    public function userCan(array $user, string $permission): bool
    {
        // Support multiple roles on user record: 'roles' => array of role keys
        $roles = [];
        if (isset($user['roles']) && is_array($user['roles'])) {
            $roles = $user['roles'];
        } elseif (!empty($user['role'])) {
            $roles = [$user['role']];
        }

        foreach ($roles as $r) {
            if ($this->roleHas((string)$r, $permission)) return true;
        }

        return false;
    }

    public function getPermissionsForRole(string $role): array
    {
        if ($role === 'admin') {
            return array_keys($this->registeredPermissions);
        }

        return $this->rolePermissions[$role] ?? [];
    }

    public function getAllPermissions(): array
    {
        return $this->registeredPermissions;
    }

    public function getPermissionsByGroup(): array
    {
        $grouped = [];

        foreach ($this->registeredPermissions as $permission => $meta) {
            $grouped[$meta['group']][$permission] = $meta['label'];
        }

        return $grouped;
    }

    // ------------------------------------------------------------------

    private function registerDefaults(): void
    {
        $this->definePermission('admin.access', 'Admin-Bereich aufrufen', 'admin');
        $this->definePermission('admin.dashboard', 'Dashboard anzeigen', 'admin');

        $this->definePermission('content.list', 'Inhalte auflisten', 'content');
        $this->definePermission('content.create', 'Inhalte erstellen', 'content');
        $this->definePermission('content.edit', 'Inhalte bearbeiten', 'content');
        $this->definePermission('content.delete', 'Inhalte löschen', 'content');
        $this->definePermission('content.publish', 'Inhalte veröffentlichen', 'content');

        $this->definePermission('system.mods', 'Benutzer darf Mods installieren, verwalten, bearbeiten, konfigurieren', 'system');
        $this->definePermission('system.themes', 'Benutzer darf Themes installieren, verwalten, bearbeiten, konfigurieren', 'system');
        $this->definePermission('system.icons.manage', 'Icon-Manager verwalten', 'settings');
        $this->definePermission('system.fonts.manage', 'Font-Manager verwalten', 'settings');
        $this->definePermission('users.manage', 'Benutzer verwalten', 'users');
        $this->definePermission('system.manage', 'Einstellungen verwalten', 'settings');

        // Default role permissions
        $editorPerms = ['admin.access', 'admin.dashboard', 'content.list', 'content.create', 'content.edit', 'content.publish'];
        foreach ($editorPerms as $perm) {
            $this->grantToRole('editor', $perm);
        }

        $viewerPerms = ['admin.access', 'admin.dashboard', 'content.list'];
        foreach ($viewerPerms as $perm) {
            $this->grantToRole('viewer', $perm);
        }
    }

    private function loadDatabasePermissions(): void
    {
        if ($this->db === null) {
            return;
        }

        try {
            $permissions = $this->db->fetchAll(
                'SELECT `key`, description, `group` FROM ' . $this->db->table('permissions') . ' ORDER BY id ASC'
            );

            foreach ($permissions as $permission) {
                $this->definePermission(
                    (string) $permission['key'],
                    (string) ($permission['description'] ?? $permission['key']),
                    (string) ($permission['group'] ?? 'system')
                );
            }

            $rows = $this->db->fetchAll(
                'SELECT r.`key` AS role_key, rp.permission_key'
                . ' FROM ' . $this->db->table('role_permissions') . ' rp'
                . ' INNER JOIN ' . $this->db->table('roles') . ' r ON r.id = rp.role_id'
            );

            $this->rolePermissions = [];

            foreach ($rows as $row) {
                $roleKey = (string) ($row['role_key'] ?? '');
                $permissionKey = (string) ($row['permission_key'] ?? '');

                if ($roleKey === '' || $permissionKey === '') {
                    continue;
                }

                $this->grantToRole($roleKey, $permissionKey);
            }
        } catch (\Throwable $e) {
            // Fall back to in-memory defaults when DB tables are not available yet.
        }
    }
}
