<?php
require __DIR__ . '/../vendor/autoload.php';
\Chamy\Core\Bootstrap::init(dirname(__DIR__));
$kernel = \Chamy\Core\Kernel::getInstance();
$mods = $kernel->modules();
echo "Installed modules:\n"; print_r(array_keys($mods->getInstalled()));
echo "\nActive modules:\n"; print_r(array_keys($mods->getActive()));
