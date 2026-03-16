<?php
declare(strict_types=1);

// Twig lint & render smoke tests
// Usage: php scripts/twig_lint_and_smoke.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Chamy\Core\Bootstrap;

// Bootstrap kernel
$kernel = Bootstrap::init(dirname(__DIR__));
echo "Kernel booted. Running render smoke tests...\n";

$themeManager = $kernel->themes();

$tests = [
    ['area' => 'admin', 'template' => 'base.twig'],
    ['area' => 'frontend', 'template' => 'base.twig'],
    ['area' => 'admin', 'template' => 'errors/404.twig'],
    ['area' => 'frontend', 'template' => 'errors/404.twig'],
    ['area' => 'admin', 'template' => 'errors/403.twig'],
    ['area' => 'frontend', 'template' => 'errors/403.twig'],
    ['area' => 'admin', 'template' => 'errors/500.twig'],
    ['area' => 'frontend', 'template' => 'errors/500.twig'],
    ['area' => 'admin', 'template' => 'settings.twig'],
];

$context = [
    'app_locale' => 'de',
    'current_route' => 'dashboard',
    'content_types' => [],
    'user' => ['username' => 'tester', 'display_name' => 'Tester', 'email' => 'tester@example.com', 'theme' => 'dark'],
    'flash_messages' => [],
    'head_styles' => [],
    'footer_scripts' => [],
];

$results = [];
foreach ($tests as $t) {
    $area = $t['area'];
    $tpl = $t['template'];
    echo "Rendering {$area}:{$tpl} ... ";
    try {
        $out = $themeManager->render($tpl, $context, $area);
        // basic check: template produced non-empty HTML
        if (is_string($out) && strlen(trim($out)) > 0) {
            echo "OK\n";
            $results[] = [ 'area' => $area, 'template' => $tpl, 'status' => 'ok' ];
        } else {
            echo "Empty output\n";
            $results[] = [ 'area' => $area, 'template' => $tpl, 'status' => 'empty' ];
        }
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $results[] = [ 'area' => $area, 'template' => $tpl, 'status' => 'error', 'message' => $e->getMessage() ];
    }
}

// Run php -l on core files as a quick sanity check
echo "\nRunning php -l on core PHP files...\n";
$coreFiles = glob(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . '*.php');
foreach ($coreFiles as $cf) {
    $cmd = 'php -l ' . escapeshellarg($cf);
    $out = [];
    $ret = 0;
    exec($cmd, $out, $ret);
    echo basename($cf) . ': ' . implode(' ', $out) . "\n";
}

echo "\nSummary:\n";
foreach ($results as $r) {
    echo " - {$r['area']}:{$r['template']} => {$r['status']}";
    if (isset($r['message'])) echo " ({$r['message']})";
    echo "\n";
}

exit(0);
