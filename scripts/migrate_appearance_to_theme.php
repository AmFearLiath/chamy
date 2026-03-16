<?php
declare(strict_types=1);

// Migration: Move settings from group `appearance` -> `theme`
// Usage: php scripts/migrate_appearance_to_theme.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Chamy\Core\Bootstrap;

// Bootstrap kernel (loads .env)
$kernel = Bootstrap::init(dirname(__DIR__));
$db = $kernel->db();

echo "Starting migration: appearance -> theme\n";

$now = date('Y-m-d H:i:s');

// Backup existing appearance settings to storage
$rows = $db->fetchAll("SELECT * FROM " . $db->table('settings') . " WHERE `group` = ?", ['appearance']);
$backupDir = $kernel->path('storage', 'backups');
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
$backupFile = $backupDir . DIRECTORY_SEPARATOR . 'appearance_settings_backup_' . date('Ymd_His') . '.json';
file_put_contents($backupFile, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Wrote backup: {$backupFile}\n";

// Read existing theme keys
$themeRows = $db->fetchAll("SELECT `key` FROM " . $db->table('settings') . " WHERE `group` = ?", ['theme']);
$themeKeys = array_map(fn($r) => $r['key'], $themeRows);

$migrated = 0;
$skipped = 0;

// Perform migration inside transaction
try {
    $db->beginTransaction();

    foreach ($rows as $r) {
        $id = (int) ($r['id'] ?? 0);
        $key = (string) ($r['key'] ?? '');
        $value = $r['value'] ?? null;

        if (in_array($key, $themeKeys, true)) {
            $skipped++;
            continue;
        }

        // Update the group to 'theme'
        $affected = $db->update('settings', ['group' => 'theme', 'updated_at' => $now], 'id = :id', ['id' => $id]);
        if ($affected > 0) $migrated++;
    }

    $db->commit();

    echo "Migration complete. Migrated: {$migrated}, Skipped (already exists in theme): {$skipped}\n";
    echo "If you want to reverse, restore from backup: {$backupFile}\n";
} catch (Throwable $e) {
    if (method_exists($db, 'rollBack')) {
        try { $db->rollBack(); } catch (Throwable $_) {}
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;
