<?php
require __DIR__ . '/../vendor/autoload.php';
\Chamy\Core\Bootstrap::init(dirname(__DIR__));
$kernel = \Chamy\Core\Kernel::getInstance();
$menu = $kernel->menus();

$locationKey = 'frontend-main';
$loc = $menu->getLocationByKey($locationKey);
if (!$loc) {
    $id = $menu->createLocation($locationKey, 'Frontend Main', 'Hauptnavigation für das Frontend', 10);
    echo "Created location '{$locationKey}' (id={$id})\n";
    $loc = $menu->getLocation($id);
} else {
    echo "Location '{$locationKey}' exists (id={$loc['id']})\n";
}

$locationId = (int)$loc['id'];

// Ensure a primary category exists
$primaryKey = 'primary';
$cat = $menu->getCategoryByKey($locationKey, $primaryKey);
if (!$cat) {
    $catId = $menu->createCategory($locationId, $primaryKey, ['de' => 'Hauptnavigation', 'en' => 'Main'], '', 10, false);
    echo "Created category '{$primaryKey}' (id={$catId})\n";
} else {
    echo "Category '{$primaryKey}' exists (id={$cat['id']})\n";
    $catId = (int)$cat['id'];
}

// Mirror frontend fallback navigation into admin-managed menu (upsert)
$items = [
    ['key' => 'frontend.home', 'labels' => ['de' => 'Start', 'en' => 'Home'], 'target_type' => 'url', 'target_value' => '/', 'sort_order' => 10],
    ['key' => 'frontend.services', 'labels' => ['de' => 'Leistungen', 'en' => 'Services'], 'target_type' => 'url', 'target_value' => '/#leistungen', 'sort_order' => 20],
    ['key' => 'frontend.references', 'labels' => ['de' => 'Referenzen', 'en' => 'References'], 'target_type' => 'url', 'target_value' => '/referenzen', 'sort_order' => 30],
    ['key' => 'frontend.contact', 'labels' => ['de' => 'Kontakt', 'en' => 'Contact'], 'target_type' => 'url', 'target_value' => '/kontakt', 'sort_order' => 40],
    ['key' => 'frontend.imprint', 'labels' => ['de' => 'Impressum', 'en' => 'Imprint'], 'target_type' => 'url', 'target_value' => '/impressum', 'sort_order' => 50],
    ['key' => 'frontend.privacy', 'labels' => ['de' => 'Datenschutz', 'en' => 'Privacy'], 'target_type' => 'url', 'target_value' => '/datenschutz', 'sort_order' => 60],
];

foreach ($items as $it) {
    $existing = $menu->getItemByKey($it['key']);
    if ($existing) {
        $menu->updateItem((int)$existing['id'], [
            'category_id' => $catId,
            'target_type' => $it['target_type'],
            'target_value' => $it['target_value'],
            'is_active' => 1,
            'is_visible' => 1,
            'sort_order' => $it['sort_order'],
        ], [
            'de' => ['label' => $it['labels']['de']],
            'en' => ['label' => $it['labels']['en']],
        ]);
        echo "Updated item {$it['key']} (id={$existing['id']})\n";
        continue;
    }
    $data = [
        'category_id' => $catId,
        'key' => $it['key'],
        'target_type' => $it['target_type'],
        'target_value' => $it['target_value'],
        'is_active' => 1,
        'is_visible' => 1,
        'is_manual' => 1,
        'sort_order' => $it['sort_order'],
    ];
    $translations = [
        'de' => ['label' => $it['labels']['de']],
        'en' => ['label' => $it['labels']['en']],
    ];
    $id = $menu->createItem($data, $translations);
    echo "Created item {$it['key']} (id={$id})\n";
}

echo "Done. Refresh admin and frontend (clear twig cache if needed).\n";
