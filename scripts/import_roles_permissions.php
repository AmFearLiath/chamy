<?php
// scripts/import_roles_permissions.php
// Import roles, permissions and mappings from data/mock into the live database.

declare(strict_types=1);

use Chamy\Core\Bootstrap;
use Chamy\Core\Database\Connection;

require_once __DIR__ . '/../vendor/autoload.php';

$kernel = Bootstrap::init(dirname(__DIR__));
$db = $kernel->db();

$seedPath = dirname(__DIR__) . '/data/mock';

function loadSeed(string $path, string $file): array
{
    $p = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $file;
    return file_exists($p) ? (require $p) : [];
}

$roles = loadSeed($seedPath, 'roles.php');
$permissions = loadSeed($seedPath, 'permissions.php');
$rolePermissions = loadSeed($seedPath, 'role_permissions.php');

// Helper: check table exists
function tableExists($db, string $tableName): bool
{
    try {
        $pref = $db->getPrefix();
        $res = $db->fetchOne('SELECT 1 FROM ' . $pref . $tableName . ' LIMIT 1');
        return $res !== null;
    } catch (Throwable $e) {
        return false;
    }
}

echo "Import roles & permissions\n";

// Helper: check if a column exists in a table
function columnExists(Connection $db, string $table, string $column): bool
{
    try {
        $pref = $db->getPrefix();
        $stmt = $db->getPdo()->query("SHOW COLUMNS FROM {$pref}{$table} LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

// Create tables if missing (simple schema compatible with LiveDataProvider)
try {
    if (!tableExists($db, 'roles')) {
        echo "- Creating table: roles\n";
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$db->getPrefix()}roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(64) NOT NULL,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL;
        $db->getPdo()->exec($sql);
    }

    // Fix permissions table: if it has old schema (role, permission, granted) from migration 006,
    // drop it and recreate with correct schema (key, description, group)
    if (tableExists($db, 'permissions') && !columnExists($db, 'permissions', 'key')) {
        echo "- Dropping old permissions table (wrong schema from migration 006)\n";
        $db->getPdo()->exec("DROP TABLE IF EXISTS {$db->getPrefix()}permissions");
    }

    if (!tableExists($db, 'permissions')) {
        echo "- Creating table: permissions\n";
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$db->getPrefix()}permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(150) NOT NULL UNIQUE,
  description TEXT,
  `group` VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL;
        $db->getPdo()->exec($sql);
    }

    if (!tableExists($db, 'role_permissions')) {
        echo "- Creating table: role_permissions\n";
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$db->getPrefix()}role_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  permission_key VARCHAR(150) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (role_id),
  INDEX (permission_key)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL;
        $db->getPdo()->exec($sql);
    }
} catch (Throwable $e) {
    echo "Failed to create tables: " . $e->getMessage() . "\n";
    exit(1);
}

// Insert roles
foreach ($roles as $r) {
    $exists = $db->fetchOne('SELECT id FROM ' . $db->getPrefix() . "roles WHERE `key` = ?", [$r['key']]);
    if ($exists) {
        echo "- Role exists: {$r['key']}\n";
        continue;
    }
    $data = [
        'uuid' => $r['uuid'] ?? bin2hex(random_bytes(8)),
        'key' => $r['key'],
        'name' => $r['name'] ?? $r['key'],
        'description' => $r['description'] ?? null,
        'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => $r['updated_at'] ?? date('Y-m-d H:i:s'),
    ];
    $id = $db->insert('roles', $data);
    echo "- Inserted role: {$r['key']} (id={$id})\n";
}

// Insert permissions
foreach ($permissions as $p) {
    $exists = $db->fetchOne('SELECT id FROM ' . $db->getPrefix() . "permissions WHERE `key` = ?", [$p['key']]);
    if ($exists) {
        echo "- Permission exists: {$p['key']}\n";
        continue;
    }
    $data = [
        'key' => $p['key'],
        'description' => $p['description'] ?? null,
        'group' => $p['group'] ?? null,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $id = $db->insert('permissions', $data);
    echo "- Inserted permission: {$p['key']} (id={$id})\n";
}

// Insert role_permissions
foreach ($rolePermissions as $rp) {
    // find role id by key from roles table (mock role_id might not match live id)
    $roleSeed = null;
    foreach ($roles as $r) {
        if ((int)$r['id'] === (int)$rp['role_id']) { $roleSeed = $r; break; }
    }
    if (!$roleSeed) continue;
    $roleRow = $db->fetchOne('SELECT id FROM ' . $db->getPrefix() . "roles WHERE `key` = ?", [$roleSeed['key']]);
    if (!$roleRow) continue;
    $roleId = (int)$roleRow['id'];

    // check mapping exists
    $exists = $db->fetchOne('SELECT id FROM ' . $db->getPrefix() . "role_permissions WHERE role_id = ? AND permission_key = ?", [$roleId, $rp['permission_key']]);
    if ($exists) {
        echo "- Mapping exists: role={$roleSeed['key']} -> {$rp['permission_key']}\n";
        continue;
    }
    $data = [
        'role_id' => $roleId,
        'permission_key' => $rp['permission_key'],
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $db->insert('role_permissions', $data);
    echo "- Mapped: role={$roleSeed['key']} -> {$rp['permission_key']}\n";
}

echo "Import finished.\n";
