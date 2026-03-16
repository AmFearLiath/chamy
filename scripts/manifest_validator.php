<?php
declare(strict_types=1);

// Simple manifest and dependency validator for modules and themes
// Usage: php scripts/manifest_validator.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

function scanDirForManifests(string $base, string $manifestName): array
{
    $found = [];
    if (!is_dir($base)) return $found;
    $dirs = glob($base . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($dirs as $d) {
        $mf = $d . DIRECTORY_SEPARATOR . $manifestName;
        if (is_file($mf)) $found[$d] = $mf;
    }
    return $found;
}

function loadJson(string $file): ?array
{
    $c = @file_get_contents($file);
    if ($c === false) return null;
    $j = json_decode($c, true);
    return is_array($j) ? $j : null;
}

function isSemver(string $v): bool
{
    return (bool) preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z-.]+)?(?:\+[0-9A-Za-z-.]+)?$/', $v);
}

function validateManifest(array $m): array
{
    $errors = [];
    if (empty($m['id']) || !preg_match('/^[a-z0-9_\-]+$/', $m['id'])) {
        $errors[] = 'Missing or invalid "id" (slug format expected)';
    }
    if (empty($m['name'])) $errors[] = 'Missing "name"';
    if (empty($m['version']) || !is_string($m['version']) || !isSemver($m['version'])) {
        $errors[] = 'Missing or invalid "version" (semver expected)';
    }
    if (isset($m['requires']) && !is_array($m['requires'])) {
        $errors[] = 'Field "requires" must be an array of dependency strings';
    } elseif (!empty($m['requires'])) {
        foreach ($m['requires'] as $dep) {
            if (!is_string($dep) || !preg_match('/^[a-z0-9_\-]+(@[\^~]?\d+\.\d+\.\d+)?$/', $dep)) {
                $errors[] = 'Dependency "' . json_encode($dep) . '" has invalid format. Use "id" or "id@^1.2.3"';
            }
        }
    }
    return $errors;
}

$base = dirname(__DIR__);

echo "Scanning manifests...\n";

$moduleBase = $base . DIRECTORY_SEPARATOR . 'modules';
$themeAdminBase = $base . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'admin';
$themeFrontendBase = $base . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'frontend';

$moduleManifests = scanDirForManifests($moduleBase, 'manifest.json');
$themeManifests = array_merge(
    scanDirForManifests($themeAdminBase, 'theme.json'),
    scanDirForManifests($themeFrontendBase, 'theme.json')
);

echo "Found modules: " . count($moduleManifests) . ", themes: " . count($themeManifests) . "\n\n";

$failures = 0;

foreach ($moduleManifests as $dir => $mf) {
    echo "Module: " . basename($dir) . " -> {$mf}\n";
    $m = loadJson($mf);
    if ($m === null) { echo "  ERROR: invalid JSON\n"; $failures++; continue; }
    $errors = validateManifest($m);
    if (!empty($errors)) {
        foreach ($errors as $e) echo "  - " . $e . "\n";
        $failures += count($errors);
    } else {
        echo "  OK\n";
    }
}

foreach ($themeManifests as $dir => $mf) {
    echo "Theme: " . basename(dirname($dir)) . ":" . basename($dir) . " -> {$mf}\n";
    $m = loadJson($mf);
    if ($m === null) { echo "  ERROR: invalid JSON\n"; $failures++; continue; }
    // For themes, require id, name, version optional, parent optional
    $errors = [];
    if (empty($m['id']) || !preg_match('/^[a-z0-9_\-]+$/', $m['id'])) $errors[] = 'Missing or invalid "id"';
    if (empty($m['name'])) $errors[] = 'Missing "name"';
    if (!empty($m['version']) && !isSemver((string)$m['version'])) $errors[] = 'Invalid "version" (semver expected)';
    if (isset($m['parent']) && !preg_match('/^[a-z0-9_\-]+$/', $m['parent'])) $errors[] = 'Invalid "parent" theme id';
    if (!empty($errors)) { foreach ($errors as $e) echo "  - " . $e . "\n"; $failures += count($errors); } else { echo "  OK\n"; }
}

echo "\nValidation complete. Failures: {$failures}\n";

if ($failures > 0) exit(2);
exit(0);
