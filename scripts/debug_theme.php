<?php
declare(strict_types=1);
$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';
$kernel = Chamy\Core\Bootstrap::init($basePath);
echo "Frontend theme path: " . $kernel->themes()->getFrontendThemePath() . PHP_EOL;
echo "Config FRONTEND_THEME: " . $kernel->config()->get('FRONTEND_THEME', 'NOT SET') . PHP_EOL;

// Check settings table
$db = $kernel->db();
$prefix = $db->getPrefix();
try {
    $settings = $db->fetchAll("SELECT * FROM {$prefix}settings WHERE `group` IN ('theme','appearance')");
    echo "Settings table theme/appearance entries: " . count($settings) . PHP_EOL;
    foreach ($settings as $s) {
        echo "  [{$s['group']}] {$s['key']} = {$s['value']}" . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo "Settings query error: " . $e->getMessage() . PHP_EOL;
}
