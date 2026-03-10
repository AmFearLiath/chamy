<?php

declare(strict_types=1);

namespace Chamy\Core\Data;

/**
 * DataProviderInterface – Abstrahiert den Datenzugriff für alle Bereiche.
 *
 * Sowohl LiveDataProvider (MySQL) als auch MockDataProvider implementieren
 * dieses Interface mit identischer Signatur, sodass ein Umschalten
 * per DATA_SOURCE=mock|live in .env genügt.
 */
interface DataProviderInterface
{
    /* ─── Content ─── */
    public function getContentEntries(string $type, ?string $status = null, int $limit = 50, int $offset = 0): array;
    public function getContentById(int $id): ?array;
    public function getContentBySlug(string $type, string $slug): ?array;
    public function countContent(string $type, ?string $status = null): int;
    public function createContent(string $type, array $data, ?int $userId = null): array;
    public function updateContent(int $id, array $data, ?int $userId = null): bool;
    public function deleteContent(int $id): bool;

    /* ─── Users ─── */
    public function getUsers(): array;
    public function getUserById(int $id): ?array;
    public function getUserByUsername(string $username): ?array;
    public function createUser(array $data): int;
    public function updateUser(int $id, array $data): bool;
    public function deleteUser(int $id): bool;

    /* ─── Settings ─── */
    public function getSettings(): array;
    public function getSettingsByGroup(string $group): array;
    public function updateSetting(int $id, string $value): bool;

    /* ─── Stats / Dashboard ─── */
    public function getDashboardStats(): array;
    public function getRecentEntries(int $limit = 10): array;

    /* ─── Roles & Permissions ─── */
    public function getRoles(): array;
    public function getRoleById(int $id): ?array;
    public function createRole(array $data): int;
    public function updateRole(int $id, array $data): bool;
    public function deleteRole(int $id): bool;
    public function roleExistsByKey(string $key, ?int $excludeId = null): bool;
    public function countUsersByRole(string $roleKey): int;

    public function getPermissions(): array;
    public function getPermissionById(int $id): ?array;
    public function createPermission(array $data): int;
    public function updatePermission(int $id, array $data): bool;
    public function deletePermission(int $id): bool;
    public function permissionExistsByKey(string $key, ?int $excludeId = null): bool;
    public function getRolePermissions(int $roleId): array;
    public function updateRolePermissions(int $roleId, array $permissions): bool;
}
