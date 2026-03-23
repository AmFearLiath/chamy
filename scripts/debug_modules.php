<?php
declare(strict_types=1);
$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';
$kernel = Chamy\Core\Bootstrap::init($basePath);
$modules = $kernel->modules()->getActive();
echo "Active modules: " . count($modules) . PHP_EOL;
foreach ($modules as $id => $m) {
    echo "  - $id" . PHP_EOL;
}
