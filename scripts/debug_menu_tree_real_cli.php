<?php

require __DIR__ . '/../vendor/autoload.php';
\Chamy\Core\Bootstrap::init(dirname(__DIR__));
$kernel = \Chamy\Core\Kernel::getInstance();

$menu = $kernel->menus();
$sessionUserId = $kernel->session()->get('user_id', null);
$user = $sessionUserId ? $kernel->data()->getUserById((int)$sessionUserId) : null;
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

echo "\n-- Resolved (with module checker) --\n";
print_r($resolved['categories']);

echo "\nDone.\n";
