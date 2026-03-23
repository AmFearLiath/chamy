<?php
declare(strict_types=1);
$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';
$kernel = Chamy\Core\Bootstrap::init($basePath);
$db = $kernel->db();
$prefix = $db->getPrefix();
$db->query("UPDATE {$prefix}settings SET value = 'elektro-keilitz' WHERE `group` = 'theme' AND `key` = 'frontend_theme'");
echo "Frontend theme setting updated to 'elektro-keilitz'.\n";

// Verify
$row = $db->fetchOne("SELECT * FROM {$prefix}settings WHERE `group` = 'theme' AND `key` = 'frontend_theme'");
echo "Verified: {$row['key']} = {$row['value']}\n";
