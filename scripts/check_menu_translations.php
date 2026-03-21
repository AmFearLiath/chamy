<?php
// Checks for presence of specific menu.* keys in de.php and en.php
$base = __DIR__ . '/../languages/';
$de = include $base . 'de.php';
$en = include $base . 'en.php';

$keys = [
    'location_edit','field_key','key_hint','field_label','field_description','field_sort_order',
    'field_active','categories','items','manage','structure','save_order','category_create',
    'item_create','field_collapsible','field_label_de','field_label_en','field_icon','select_item'
];

function hasKey(array $arr, string $key): bool {
    return isset($arr['menu'][$key]);
}

$missing = ['de'=>[], 'en'=>[]];
foreach ($keys as $k) {
    if (!hasKey($de,$k)) $missing['de'][] = $k;
    if (!hasKey($en,$k)) $missing['en'][] = $k;
}

echo "Missing keys:\n";
echo "DE: " . (empty($missing['de']) ? 'none' : implode(', ', $missing['de'])) . "\n";
echo "EN: " . (empty($missing['en']) ? 'none' : implode(', ', $missing['en'])) . "\n";

return 0;
