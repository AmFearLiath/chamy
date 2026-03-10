<?php

declare(strict_types=1);

use Chamy\Core\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap kernel
$kernel = Bootstrap::init(dirname(__DIR__));
$db = $kernel->db();
$base = $kernel->getBasePath();

$mockDir = $base . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'mock' . DIRECTORY_SEPARATOR;
if (!is_dir($mockDir)) {
    echo "Mock data directory not found: {$mockDir}\n";
    exit(1);
}

$rolesFile = $mockDir . 'roles.php';
$permsFile = $mockDir . 'permissions.php';
$rpFile = $mockDir . 'role_permissions.php';

if (!file_exists($rolesFile) || !file_exists($permsFile) || !file_exists($rpFile)) {
    echo "One or more mock seed files missing.\n";
    exit(1);
}

$roles = require $rolesFile;
$perms = require $permsFile;
$rolePerms = require $rpFile;

try {
    $db->beginTransaction();

    // Live schema stores permissions per-role in the permissions table
    // Build mapping mockRoleId -> roleKey
    $mockRoleIdToKey = [];
    foreach ($roles as $r) {
        $mockRoleIdToKey[$r['id']] = $r['key'];
    }

    foreach ($rolePerms as $rp) {
        $mockRoleId = $rp['role_id'];
        $permKey = $rp['permission_key'];
        $roleKey = $mockRoleIdToKey[$mockRoleId] ?? null;
        if ($roleKey === null) {
            echo "Warning: unknown mock role id {$mockRoleId}\n";
            continue;
        }

        // remove existing grant to avoid duplicates
        $db->delete('permissions', '`role` = :role AND `permission` = :perm', ['role' => $roleKey, 'perm' => $permKey]);
        $db->insert('permissions', [
            'role' => $roleKey,
            'permission' => $permKey,
            'granted' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        echo "Assigned permission {$permKey} -> role {$roleKey}\n";
    }

    $db->commit();

    echo "Import completed successfully.\n";
    echo "Next: set DATA_SOURCE=live in .env to use live data on Users page.\n";

} catch (Throwable $e) {
    $db->rollBack();
    echo "Error during import: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;
