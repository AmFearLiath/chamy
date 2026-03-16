<?php
use Chamy\Core\Bootstrap;
require_once __DIR__ . '/../vendor/autoload.php';
$kernel = Bootstrap::init(dirname(__DIR__));
$assetLib = $kernel->assetLibrary();
$sets = $assetLib->listIconSets();
if (!is_array($sets) || $sets === []) {
    echo "No icon sets found\n";
    exit(0);
}
foreach ($sets as $set) {
    $id = $set['id'] ?? '';
    $name = $set['name'] ?? '';
    $localCss = $set['local_css'] ?? '';
    echo "Set: $id ($name)\n";
    if ($localCss === '') {
        echo "  - no local_css set\n\n";
        continue;
    }
    $cssPath = rtrim($kernel->getBasePath(), "\\/") . $localCss;
    // local_css is like /assets/icon-sets/{id}/icons.css
    $fullPath = $kernel->path('public') . DIRECTORY_SEPARATOR . ltrim($localCss, '/');
    if (!is_file($fullPath)) {
        echo "  - local css missing: $fullPath\n\n";
        continue;
    }
    $css = file_get_contents($fullPath);
    if (preg_match_all('/url\(([^)]+)\)/i', $css, $m)) {
        $refs = $m[1];
        foreach ($refs as $r) {
            $clean = trim($r, " \t\n\r\0\x0B\"'");
            if ($clean === '' || str_starts_with($clean, 'data:')) continue;
            // resolve relative to css folder
            $cssDir = dirname($fullPath);
            $resolved = realpath($cssDir . DIRECTORY_SEPARATOR . str_replace(['./','/'], ['','DIRECTORY_REPLACE'], $clean));
            // simple fallback: check relative path
            $candidate = $cssDir . DIRECTORY_SEPARATOR . ltrim($clean, './');
            echo "  - reference: $clean -> ";
            if (is_file($candidate)) {
                echo "OK (exists: $candidate)\n";
            } else {
                echo "MISSING (expected: $candidate)\n";
            }
        }
    } else {
        echo "  - no url(...) references found in CSS\n";
    }
    echo "\n";
}
