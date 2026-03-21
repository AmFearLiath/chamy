<?php

require __DIR__ . '/../vendor/autoload.php';
\Chamy\Core\Bootstrap::init(dirname(__DIR__));
$kernel = \Chamy\Core\Kernel::getInstance();

$menu = $kernel->menus();
$res = $menu->resolveTree('admin-sidebar', null, $_SERVER['argv'][1] ?? null, null, null);

echo "\n-- Resolved categories and nested items --\n";
print_r($res['categories']);

echo "\n-- Flat items for location (raw) --\n";
$flat = $menu->getItemsByLocation('admin-sidebar');
foreach ($flat as $it) {
    echo sprintf("id=%s key=%s parent_id=%s category=%s label=%s\n", $it['id'], $it['key'], $it['parent_id'] ?? 'NULL', $it['category_key'], $it['translated_label'] ?? $it['key']);
}

echo "\nDone.\n";
