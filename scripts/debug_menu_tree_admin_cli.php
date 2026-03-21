<?php
require __DIR__ . '/../vendor/autoload.php';
\Chamy\Core\Bootstrap::init(dirname(__DIR__));
$kernel = \Chamy\Core\Kernel::getInstance();
$menu = $kernel->menus();

$user = $kernel->data()->getUserById(1);
$currentPath = '/admin';

$resolved = $menu->resolveTree(
    'admin-sidebar',
    $user,
    $currentPath,
    static function (?array $currentUser, string $permission) use ($kernel): bool {
        return $currentUser !== null && $kernel->permissions()->userCan($currentUser, $permission);
    },
    static function (string $moduleKey) use ($kernel): bool {
        return method_exists($kernel->modules(), 'isActive')
            && $kernel->modules()->isActive($moduleKey);
    }
);

echo "\n-- Resolved for user id=1 (admin) --\n";
print_r($resolved['categories']);

echo "\nDone.\n";
