<?php
// scripts/db_cleanup.php
// Safe database cleanup for Chamy: keeps tables with configured prefix,
// drops all other tables in the same database, then (optionally) removes
// the prefix from table names and from `.env`.
//
// Usage (dry-run):
//   php scripts/db_cleanup.php
// To perform actions:
//   php scripts/db_cleanup.php --apply
// WARNING: destructive. The script will ask for confirmation when --apply is used.

declare(strict_types=1);

use Chamy\Core\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';

$kernel = Bootstrap::init(dirname(__DIR__));
$db = $kernel->db();
$prefix = (string) $kernel->config()->get('DB_PREFIX', '');
$driver = (string) $kernel->config()->get('DB_DRIVER', '');

if (strtolower($driver) !== 'mysql') {
    echo "This cleanup script currently supports only MySQL. DB_DRIVER={$driver}\n";
    exit(1);
}

$pdo = $db->getPdo();
$database = $db->getDatabase();

// fetch all tables for this database
$stmt = $pdo->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db');
$stmt->execute(['db' => $database]);
$all = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

$kept = [];
$remove = [];

if ($prefix === '') {
    // nothing to do: no prefix configured — nothing to keep
    echo "No DB prefix configured (DB_PREFIX is empty).\n";
    echo "Aborting — set DB_PREFIX in .env to the prefix you want to keep, or run a custom cleanup.\n";
    exit(1);
}

foreach ($all as $t) {
    if (str_starts_with($t, $prefix)) {
        $kept[] = $t;
    } else {
        $remove[] = $t;
    }
}

echo "Database: {$database}\n";
echo "Configured prefix: '{$prefix}'\n\n";
echo "Tables keeping (prefix matches):\n";
foreach ($kept as $k) echo "  - {$k}\n";

echo "\nTables to remove (will be DROPPED):\n";
foreach ($remove as $r) echo "  - {$r}\n";

$doApply = in_array('--apply', $argv, true);
if (!$doApply) {
    echo "\nDry-run. To actually perform the cleanup run with: php scripts/db_cleanup.php --apply\n";
    exit(0);
}

// Confirm
fwrite(STDOUT, "\nYou are about to DROP " . count($remove) . " tables and then rename " . count($kept) . " tables to remove the prefix. Type 'yes' to proceed: ");
$confirm = trim(fgets(STDIN));
if ($confirm !== 'yes') {
    echo "Aborted by user. No changes made.\n";
    exit(0);
}

// Start destructive operations
try {
    // Drop non-prefixed tables
    // Temporarily disable foreign key checks to allow dropping in presence of FK constraints
    echo "Disabling foreign key checks...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($remove as $t) {
        echo "Dropping table: {$t}... ";
        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
        echo "OK\n";
    }
    echo "Re-enabling foreign key checks...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    // Rename kept tables to remove prefix
    foreach ($kept as $t) {
        $new = substr($t, strlen($prefix));
        if ($new === '' ) {
            echo "Skipping rename for {$t} (resulting name empty)\n";
            continue;
        }
        // check collision
        $exists = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :name');
        $exists->execute(['db' => $database, 'name' => $new]);
        $coll = $exists->fetchColumn();
        if ($coll) {
            throw new RuntimeException("Target table name '{$new}' already exists — aborting to avoid collision.");
        }
        echo "Renaming {$t} -> {$new}... ";
        $pdo->exec(sprintf('RENAME TABLE `%s` TO `%s`', $t, $new));
        echo "OK\n";
    }

    // Backup .env and remove DB_PREFIX value
    $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    $bak = $envFile . '.bak.' . date('YmdHis');
    if (file_exists($envFile)) {
        copy($envFile, $bak);
        $contents = file_get_contents($envFile);
        // remove DB_PREFIX line or set empty
        $contents = preg_replace('/^DB_PREFIX=.*$/m', 'DB_PREFIX=', $contents);
        file_put_contents($envFile, $contents);
        echo "Updated .env (backup: {$bak})\n";
    } else {
        echo "No .env file found to update.\n";
    }

    echo "Cleanup completed successfully.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
