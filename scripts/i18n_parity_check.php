<?php
declare(strict_types=1);

// Simple i18n parity checker for languages/en vs languages/de
// Usage: php scripts/i18n_parity_check.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

function collect_lang_files(string $dir): array
{
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $f) {
        if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
            $files[] = $f->getPathname();
        }
    }
    return $files;
}

function load_lang_array(string $file): array
{
    try {
        $arr = include $file;
        if (!is_array($arr)) return [];
        return $arr;
    } catch (Throwable $e) {
        return [];
    }
}

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

$base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'languages';
if (!is_dir($base)) {
    echo "languages directory not found.\n";
    exit(2);
}

$locales = [];
foreach (scandir($base) as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $path = $base . DIRECTORY_SEPARATOR . $entry;
    if (is_dir($path)) $locales[] = $entry;
    elseif (is_file($path) && preg_match('/^([a-z]{2})(?:_[A-Z]{2})?\.php$/', $entry, $m)) {
        $locales[] = $m[1];
    }
}

// Ensure en and de exist
if (!in_array('en', $locales, true) || !in_array('de', $locales, true)) {
    echo "Both 'en' and 'de' locales must exist under languages/. Found: " . implode(',', $locales) . "\n";
}

// Load all php files under each locale dir
function collect_locale_arrays(string $base, string $locale): array
{
    $dir = $base . DIRECTORY_SEPARATOR . $locale;
    $collected = [];
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
                $arr = load_lang_array($path);
                $collected = array_merge_recursive($collected, $arr);
            }
        }
    }
    // also check top-level file like languages/de.php
    $top = $base . DIRECTORY_SEPARATOR . $locale . '.php';
    if (is_file($top)) {
        $collected = array_merge_recursive($collected, load_lang_array($top));
    }
    return $collected;
}

$en = collect_locale_arrays($base, 'en');
$de = collect_locale_arrays($base, 'de');

$fe_en = flatten($en);
$fe_de = flatten($de);

$keys_en = array_keys($fe_en);
$keys_de = array_keys($fe_de);

$missing_in_en = array_diff($keys_de, $keys_en);
$missing_in_de = array_diff($keys_en, $keys_de);

echo "i18n parity check:\n";
echo "Total EN keys: " . count($keys_en) . "\n";
echo "Total DE keys: " . count($keys_de) . "\n\n";

if (empty($missing_in_en) && empty($missing_in_de)) {
    echo "OK: Keys match between en and de.\n";
    exit(0);
}

if (!empty($missing_in_en)) {
    echo "Keys present in DE but missing in EN (count: " . count($missing_in_en) . "):\n";
    foreach ($missing_in_en as $k) echo "  - {$k}\n";
    echo "\n";
}

if (!empty($missing_in_de)) {
    echo "Keys present in EN but missing in DE (count: " . count($missing_in_de) . "):\n";
    foreach ($missing_in_de as $k) echo "  - {$k}\n";
    echo "\n";
}

exit(1);
