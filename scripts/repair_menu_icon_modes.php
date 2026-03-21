<?php
// Repair script: set meta.icon_mode for menu items based on installed icon sets or icon name
require __DIR__ . '/../vendor/autoload.php';
use Chamy\Core\Bootstrap;

$kernel = Bootstrap::init(dirname(__DIR__));
$mm = $kernel->menus();
// Use MenuManager public API to fetch items via categories
$locations = $mm->getLocations();
$updated = 0;
foreach ($locations as $loc) {
    $cats = $mm->getCategories((int)$loc['id']);
    foreach ($cats as $cat) {
        $items = $mm->getItems((int)$cat['id']);
        foreach ($items as $item) {
            $meta = [];
            if (!empty($item['meta']) && is_string($item['meta'])) {
                $meta = json_decode($item['meta'], true) ?: [];
            }
            if (isset($meta['icon_mode']) && $meta['icon_mode'] !== '') {
                continue;
            }
            $iconSet = $meta['icon_set'] ?? '';
            $mode = '';
            if ($iconSet) {
                foreach ($kernel->assetLibrary()->listIconSets() as $set) {
                    $setId = strtolower((string)($set['id'] ?? ''));
                    $setName = strtolower((string)($set['name'] ?? ''));
                    if ($setId === strtolower($iconSet) || str_contains($setId, 'tabler') || str_contains($setName, 'tabler')) {
                        $mode = 'tabler';
                        break;
                    }
                }
            }
            if ($mode === '') {
                // Fallback: if icon looks like tabler name (no prefix, dashed) assume tabler
                $icon = trim((string)($item['icon'] ?? ''));
                if ($icon !== '' && preg_match('/^[a-z0-9\-]+$/i', $icon)) {
                    $mode = 'tabler';
                }
            }
            if ($mode !== '') {
                $meta['icon_mode'] = $mode;
                $mm->updateItem((int)$item['id'], ['meta' => json_encode($meta)]);
                $updated++;
            }
        }
    }
}

echo "Updated $updated items\n";
