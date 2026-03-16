<?php
declare(strict_types=1);

// Synchronize and clean i18n files between 'de' and 'en'.
// - Backups existing language files
// - Removes numeric duplicate suffixes (key.0, key.1)
// - Ensures both locales have the same key set
// - Fills missing entries with counterpart text prefixed by TODO markers
// Usage: php scripts/i18n_sync_and_cleanup.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

function flatten(array $arr, string $prefix = ''): array
{
    $out = [];
    foreach ($arr as $k => $v) {
        $key = $prefix === '' ? $k : $prefix . '.' . $k;
        if (is_array($v)) {
            $out = array_merge($out, flatten($v, $key));
        } else {
            $out[$key] = $v;
        }
    }
    return $out;
}

function unflatten(array $flat): array
{
    $out = [];
    foreach ($flat as $k => $v) {
        $parts = explode('.', $k);
        $ref = &$out;
        foreach ($parts as $p) {
            if (!isset($ref[$p]) || !is_array($ref[$p])) $ref[$p] = [];
            $ref = &$ref[$p];
        }
        $ref = $v;
        unset($ref);
    }
    return $out;
}

function load_locale_arrays(string $base, string $locale): array
{
    $collected = [];
    $dir = $base . DIRECTORY_SEPARATOR . $locale;
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
                $arr = include $path;
                if (is_array($arr)) $collected = array_merge_recursive($collected, $arr);
            }
        }
    }
    $top = $base . DIRECTORY_SEPARATOR . $locale . '.php';
    if (is_file($top)) {
        $arr = include $top;
        if (is_array($arr)) $collected = array_merge_recursive($collected, $arr);
    }
    return $collected;
}

$base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'languages';
if (!is_dir($base)) {
    echo "languages directory not found.\n";
    exit(2);
}

$locales = ['de', 'en'];

// Backup languages directory
$backupDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
$zipFile = $backupDir . DIRECTORY_SEPARATOR . 'languages_backup_' . date('Ymd_His') . '.zip';
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
        foreach ($it as $file) {
            if ($file->isFile()) {
                $localName = substr($file->getPathname(), strlen($base) + 1);
                $zip->addFile($file->getPathname(), $localName);
            }
        }
        $zip->close();
        echo "Backup created: {$zipFile}\n";
    } else {
        echo "Warning: could not create backup zip.\n";
    }
} else {
    // fallback: copy language files to backup directory with timestamp
    $copyDir = $backupDir . DIRECTORY_SEPARATOR . 'languages_backup_' . date('Ymd_His');
    mkdir($copyDir, 0755, true);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $item) {
        $dest = $copyDir . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($base) + 1);
        if ($item->isDir()) {
            if (!is_dir($dest)) mkdir($dest, 0755, true);
        } else {
            copy($item->getPathname(), $dest);
        }
    }
    echo "Backup copy created: {$copyDir}\n";
}

$flat = [];
foreach ($locales as $loc) {
    $arr = load_locale_arrays($base, $loc);
    $f = flatten($arr);

    // remove numeric duplicate suffixes like key.0, key.1 by grouping base key
    $clean = [];
    $groups = [];
    foreach ($f as $k => $v) {
        // strip trailing .0/.1 etc only if pattern matches
        if (preg_match('/^(.*)\.(\d+)$/', $k, $m)) {
            $baseKey = $m[1];
            $groups[$baseKey][] = $v;
        } else {
            $groups[$k][] = $v;
        }
    }
    foreach ($groups as $key => $vals) {
        // pick first non-empty string
        $chosen = null;
        foreach ($vals as $c) {
            if ($c !== null && $c !== '') { $chosen = $c; break; }
        }
        $clean[$key] = $chosen ?? '';
    }

    $flat[$loc] = $clean;
    echo "Loaded locale {$loc}, keys: " . count($clean) . "\n";
}

// union of keys
$allKeys = array_unique(array_merge(array_keys($flat['de']), array_keys($flat['en'])));
sort($allKeys);

// fill missing keys per locale
foreach ($allKeys as $k) {
    foreach ($locales as $loc) {
        if (!array_key_exists($k, $flat[$loc]) || $flat[$loc][$k] === '') {
            $other = $loc === 'de' ? 'en' : 'de';
            $val = $flat[$other][$k] ?? '';
            if ($val === '') {
                $flat[$loc][$k] = ($loc === 'de' ? 'TODO: übersetzen: ' : 'TODO: translate: ') . $k;
            } else {
                // copy other value as placeholder
                $flat[$loc][$k] = ($loc === 'de' ? 'TODO: übersetzen: ' : 'TODO: translate: ') . $val;
            }
        }
    }
}

// write back to languages/{locale}.php as nested arrays, sorted
foreach ($locales as $loc) {
    ksort($flat[$loc]);
    $nested = unflatten($flat[$loc]);
    $outFile = $base . DIRECTORY_SEPARATOR . $loc . '.php';
    $export = var_export($nested, true);
    $content = "<?php\n\nreturn " . $export . ";\n";
    file_put_contents($outFile, $content);
    echo "Wrote cleaned file: {$outFile} (keys: " . count($flat[$loc]) . ")\n";
}

echo "i18n sync complete. Review TODO markers and translate as needed.\n";

exit(0);
