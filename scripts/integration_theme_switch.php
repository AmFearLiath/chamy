<?php
declare(strict_types=1);

// Integration test: Theme switch for admin and frontend
// Usage: php scripts/integration_theme_switch.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Chamy\Core\Bootstrap;

$kernel = Bootstrap::init(dirname(__DIR__));
$themeManager = $kernel->themes();

echo "Starting theme-switch integration test...\n";

$parentAdmin = 'default';
$parentFrontend = 'default';
$childAdmin = 'itest_admin_' . time();
$childFrontend = 'itest_front_' . time();

// Create child themes
echo "Creating child themes: {$childAdmin}, {$childFrontend}\n";
$resA = $themeManager->createChildTheme('admin', $parentAdmin, $childAdmin, 'Integration Test Admin');
$resF = $themeManager->createChildTheme('frontend', $parentFrontend, $childFrontend, 'Integration Test Frontend');

if ($resA === false || $resF === false) {
    echo "Failed to create child themes. Aborting.\n";
    exit(1);
}

// Switch themes
echo "Setting active admin theme to {$childAdmin}\n";
$okA = $themeManager->setAdminThemeId($childAdmin);
echo "Admin set result: " . ($okA ? 'ok' : 'failed') . "\n";

echo "Setting active frontend theme to {$childFrontend}\n";
$okF = $themeManager->setFrontendThemeId($childFrontend);
echo "Frontend set result: " . ($okF ? 'ok' : 'failed') . "\n";

$ctx = [
    'app_locale' => 'de',
    'current_route' => 'dashboard',
    'content_types' => [],
    'user' => ['username' => 'itest', 'display_name' => 'ITest', 'email' => 'itest@example.com', 'theme' => 'dark'],
    'flash_messages' => [],
    'head_styles' => [],
    'footer_scripts' => [],
];

// Render admin/base.twig and check asset URL
echo "Rendering admin:base.twig... ";
$outA = $themeManager->render('base.twig', $ctx, 'admin');
if (strpos($outA, '/themes/admin/' . $childAdmin . '/assets/') !== false) {
    echo "OK (asset path present)\n";
} else {
    echo "FAIL (asset path missing)\n";
}

// Render frontend/base.twig and check asset URL
echo "Rendering frontend:base.twig... ";
$outF = $themeManager->render('base.twig', $ctx, 'frontend');
if (strpos($outF, '/themes/frontend/' . $childFrontend . '/assets/') !== false) {
    echo "OK (asset path present)\n";
} else {
    echo "FAIL (asset path missing)\n";
}

// Revert to default and remove test themes
echo "Reverting themes to default and cleaning up...\n";
$themeManager->setAdminThemeId('default');
$themeManager->setFrontendThemeId('default');

// Uninstall child themes
$u1 = $themeManager->uninstallTheme('admin', $childAdmin);
$u2 = $themeManager->uninstallTheme('frontend', $childFrontend);

echo "Uninstall results: admin=" . (is_array($u1) ? 'ok' : 'failed') . ", frontend=" . (is_array($u2) ? 'ok' : 'failed') . "\n";

echo "Integration test complete.\n";

exit(0);
