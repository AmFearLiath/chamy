<?php
declare(strict_types=1);

// Safe module lifecycle helper: dry-run by default, requires --confirm to apply
// Usage examples:
// php scripts/module_lifecycle.php --action=upgrade --module=contact_form --to=1.1.0 --dry-run
// php scripts/module_lifecycle.php --action=rollback --module=contact_form --confirm

function cli_arg(string $name, $default = null)
{
    global $argv;
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$name}=") === 0) return substr($arg, strlen("--{$name}="));
    }
    foreach ($argv as $arg) {
        if ($arg === "--{$name}") return true;
    }
    return $default;
}

$action = cli_arg('action');
$module = cli_arg('module');
$toVersion = cli_arg('to');
$dryRun = cli_arg('dry-run', false) !== false;
$confirm = cli_arg('confirm', false) !== false;
$dbBackupFlag = cli_arg('db-backup', false) !== false;
$applyMigrations = cli_arg('apply-migrations', false) !== false;

if (!$action || !$module) {
    echo "Usage: php scripts/module_lifecycle.php --action=upgrade|rollback --module=MODULE [--to=VERSION] [--dry-run] [--confirm]\n";
    exit(1);
}

$base = dirname(__DIR__);
$moduleDir = $base . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $module;
if (!is_dir($moduleDir)) { echo "Module not found: {$moduleDir}\n"; exit(2); }

function nowStamp(): string { return date('Ymd_His'); }

function copyDir(string $src, string $dst): bool
{
    if (!is_dir($src)) return false;
    @mkdir($dst, 0777, true);
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($items as $item) {
        $path = $item->getPathname();
        $target = $dst . DIRECTORY_SEPARATOR . substr($path, strlen($src) + 1);
        if ($item->isDir()) { @mkdir($target, 0777, true); } else { copy($path, $target); }
    }
    return true;
}

function zipDir(string $src, string $zipPath): bool
{
    if (!class_exists('ZipArchive')) return copyDir($src, $zipPath . '.copy');
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) return false;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relative = substr($filePath, strlen($src) + 1);
        $zip->addFile($filePath, $relative);
    }
    $zip->close();
    return true;
}

function readManifest(string $dir): ?array
{
    $mf = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'manifest.json';
    if (!is_file($mf)) return null;
    $c = @file_get_contents($mf);
    $j = json_decode($c, true);
    return is_array($j) ? $j : null;
}

function readDbConfig(string $base): ?array
{
    // try config/database.php
    $cfg = $base . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
    if (is_file($cfg)) {
        $arr = include $cfg;
        if (is_array($arr) && isset($arr['host'], $arr['user'], $arr['pass'], $arr['name'])) return [
            'host' => $arr['host'], 'user' => $arr['user'], 'pass' => $arr['pass'], 'name' => $arr['name']
        ];
    }
    // try environment variables
    $host = getenv('DB_HOST'); $user = getenv('DB_USER'); $pass = getenv('DB_PASS'); $name = getenv('DB_NAME');
    if ($host && $user && $name) return ['host'=>$host,'user'=>$user,'pass'=>$pass,'name'=>$name];
    return null;
}

function dbDump(array $dbConfig, string $outPath): bool
{
    // prefer mysqldump
    $cmd = sprintf('mysqldump --host=%s --user=%s --password=%s %s > %s', escapeshellarg($dbConfig['host']), escapeshellarg($dbConfig['user']), escapeshellarg($dbConfig['pass']), escapeshellarg($dbConfig['name']), escapeshellarg($outPath));
    exec('where mysqldump >NUL 2>&1', $o, $r);
    if ($r !== 0) {
        // try unix path
        exec('which mysqldump >/dev/null 2>&1', $o2, $r2);
        if ($r2 !== 0) return false;
    }
    // run command
    exec($cmd, $out, $ret);
    return $ret === 0;
}

function writeManifest(string $dir, array $data): bool
{
    $mf = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'manifest.json';
    return (bool) file_put_contents($mf, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

echo "Module lifecycle: action={$action}, module={$module}, to={$toVersion}, dryRun=" . ($dryRun? 'true':'false') . ", confirm=" . ($confirm? 'true':'false') . "\n";

$manifest = readManifest($moduleDir);
if ($manifest === null) { echo "No manifest.json found in module\n"; exit(3); }

if ($action === 'upgrade') {
    if (!$toVersion) { echo "Provide --to=VERSION for upgrade\n"; exit(4); }
    echo "Current version: " . ($manifest['version'] ?? 'unknown') . " -> target: {$toVersion}\n";
    $backupName = "module_{$module}_backup_" . nowStamp() . ".zip";
    $backupPath = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $backupName;
    echo "Planned backup: {$backupPath}\n";
    if ($dryRun) {
        echo "Dry-run: would create backup of module dir, validate package, update manifest to {$toVersion}.\n"; exit(0);
    }
    // optionally perform DB backup
    if ($dbBackupFlag) {
        $dbCfg = readDbConfig($base);
        if ($dbCfg === null) { echo "DB config not found; skipping DB backup.\n"; }
        else {
            $sqlPath = dirname($backupPath) . DIRECTORY_SEPARATOR . "module_{$module}_db_" . nowStamp() . ".sql";
            echo "Attempting DB dump to: {$sqlPath}\n";
            if (!dbDump($dbCfg, $sqlPath)) { echo "DB dump failed or mysqldump not available.\n"; }
            else { echo "DB dump created: {$sqlPath}\n"; }
        }
    }
    if (!$confirm) { echo "Not confirmed. Add --confirm to apply changes.\n"; exit(5); }
    @mkdir(dirname($backupPath), 0777, true);
    if (!zipDir($moduleDir, $backupPath)) { echo "Failed to create backup (zip)\n"; exit(6); }
    echo "Backup created: {$backupPath}\n";
    // Simulate upgrade by updating manifest version. Real upgrade should unpack package here.
    $manifest['version'] = $toVersion;
    if (!writeManifest($moduleDir, $manifest)) { echo "Failed to write manifest\n"; exit(7); }
    echo "Manifest updated to {$toVersion}\n";
    // optionally run migrations
    if ($applyMigrations) {
        $migrationsDir = $moduleDir . DIRECTORY_SEPARATOR . 'migrations';
        if (is_dir($migrationsDir)) {
            $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php');
            sort($files);
            foreach ($files as $f) {
                echo "Running migration: " . basename($f) . "\n";
                try {
                    $dry = false;
                    include $f; // migrations can use $dry to adapt behavior
                    echo "  OK\n";
                } catch (\Throwable $e) {
                    echo "  Migration failed: " . $e->getMessage() . "\n";
                }
            }
        } else { echo "No migrations directory found.\n"; }
    }
    echo "Upgrade complete (simulation). Run smoke tests.\n";
    exit(0);
}

if ($action === 'rollback') {
    // find latest backup for module
    $backupDir = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($backupDir)) { echo "No backups directory\n"; exit(8); }
    $pattern = $backupDir . DIRECTORY_SEPARATOR . "module_{$module}_backup_*";
    $files = glob($pattern . '.zip');
    if (empty($files)) $files = glob($pattern . '.zip.copy');
    if (empty($files)) { echo "No backup found for module {$module}\n"; exit(9); }
    rsort($files);
    $latest = $files[0];
    echo "Found backup: {$latest}\n";
    if ($dryRun) { echo "Dry-run: would restore backup {$latest} into {$moduleDir}\n"; exit(0); }
    if (!$confirm) { echo "Not confirmed. Add --confirm to apply rollback.\n"; exit(10); }
    // restore
    if (substr($latest, -5) === '.copy') {
        $srcDir = substr($latest, 0, -5);
        if (!is_dir($srcDir)) { echo "Backup copy dir missing: {$srcDir}\n"; exit(11); }
        // remove module dir and copy
        $tmp = $moduleDir . '_old_' . nowStamp();
        rename($moduleDir, $tmp);
        copyDir($srcDir, $moduleDir);
        echo "Restored from copy. Original moved to {$tmp}\n";
        exit(0);
    }
    $zip = new ZipArchive();
    if ($zip->open($latest) !== true) { echo "Failed to open backup zip\n"; exit(12); }
    // extract to temp, then swap
    $tmpDir = $base . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'restore_' . nowStamp();
    @mkdir($tmpDir, 0777, true);
    $zip->extractTo($tmpDir);
    $zip->close();
    $tmp = $moduleDir . '_old_' . nowStamp();
    rename($moduleDir, $tmp);
    copyDir($tmpDir, $moduleDir);
    echo "Restored backup into {$moduleDir}. Old moved to {$tmp}\n";
    exit(0);
}

echo "Unknown action: {$action}\n";
exit(20);
