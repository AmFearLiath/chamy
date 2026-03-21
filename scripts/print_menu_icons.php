<?php
require __DIR__ . '/../vendor/autoload.php';
use Chamy\Core\Bootstrap;
$kernel = Bootstrap::init(dirname(__DIR__));
$mm = $kernel->menus();
$resolved = $mm->resolveTree('admin-sidebar', null, '/admin');
$cats = $resolved['categories'] ?? [];
foreach ($cats as $cat) {
    foreach ($cat['items'] as $item) {
        $meta = $item['meta'] ?? [];
        echo ($item['key'] ?? '') . ' -> icon=' . ($item['icon'] ?? '') . ' meta.icon_set=' . ($meta['icon_set'] ?? '') . ' meta.icon_mode=' . ($meta['icon_mode'] ?? '') . "\n";
        if (!empty($item['children'])) {
            foreach ($item['children'] as $c) {
                $m2 = $c['meta'] ?? [];
                echo '  - ' . ($c['key'] ?? '') . ' -> icon=' . ($c['icon'] ?? '') . ' meta.icon_mode=' . ($m2['icon_mode'] ?? '') . "\n";
            }
        }
    }
}
