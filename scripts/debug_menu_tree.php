<?php
// Debug script: show raw and post-filtered menu tree for admin-sidebar
require_once __DIR__ . '/../core/Bootstrap.php';

use Chamy\Core\Bootstrap;

$base = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
$kernel = Bootstrap::init($base);

function print_tree(array $resolved, string $prefix = ''): void
{
    foreach ($resolved['categories'] as $group) {
        $cat = $group['category'];
        echo PHP_EOL . $prefix . "Category: " . ($cat['display_label'] ?? $cat['key']) . " (key=" . ($cat['key'] ?? '') . ")" . PHP_EOL;
        foreach ($group['items'] as $item) {
            print_item($item, $prefix . '  ');
        }
    }
}

function print_item(array $item, string $pad = ''): void
{
    $flags = [];
    if (!empty($item['is_visible']) === false) $flags[] = 'is_visible=0';
    if (!empty($item['is_hidden'])) $flags[] = 'is_hidden=1';
    if (!empty($item['is_collapsible'])) $flags[] = 'collapsible';
    if (!empty($item['requires_module'])) $flags[] = 'requires_module=' . $item['requires_module'];
    if (!empty($item['permission'])) $flags[] = 'permission=' . $item['permission'];
    if (!empty($item['visibility_rule'])) $flags[] = 'visibility_rule=' . $item['visibility_rule'];

    echo $pad . sprintf("- %s (key=%s) [%s]\n", $item['display_label'] ?? ($item['translated_label'] ?? $item['key']), $item['key'] ?? '', implode(', ', $flags));

    foreach ($item['children'] ?? [] as $child) {
        print_item($child, $pad . '  ');
    }
}

function filter_resolved(array $resolved, $kernel, $user): array
{
    $filterItem = null;
    $filterItem = function (array $item) use (&$filterItem, $kernel, $user): ?array {
        if (!($item['is_visible'] ?? true) || ($item['is_hidden'] ?? false)) {
            return null;
        }

        if (!empty($item['requires_module'])) {
            if (!method_exists($kernel->modules(), 'isActive') || !$kernel->modules()->isActive($item['requires_module'])) {
                return null;
            }
        }

        // Enforce permission checks when a permission is declared on the item.
        if (!empty($item['permission'])) {
            $perm = $item['permission'];
            if ($perm !== '') {
                if (!$user || !$kernel->permissions()->userCan($user, $perm)) {
                    return null;
                }
            }
        }

        $children = [];
        foreach ($item['children'] ?? [] as $child) {
            $c = $filterItem($child);
            if ($c !== null) $children[] = $c;
        }
        $item['children'] = $children;

        // If container with no children and is_collapsible, still keep if it has a route
        if (empty($item['children']) && ($item['is_collapsible'] ?? false) && empty($item['target_value'])) {
            return null;
        }

        return $item;
    };

    foreach ($resolved['categories'] as $ci => $group) {
        $out = [];
        foreach ($group['items'] as $item) {
            $f = $filterItem($item);
            if ($f !== null) $out[] = $f;
        }
        $resolved['categories'][$ci]['items'] = $out;
        $resolved['categories'][$ci]['has_items'] = !empty($out);
    }

    return $resolved;
}

echo "Debug: resolveTree('admin-sidebar')\n";

$sessionUserId = $kernel->session()->get('user_id', null);
$sessionUser = $sessionUserId ? $kernel->data()->getUserById((int)$sessionUserId) : null;

$users = [
    'session_user' => $sessionUser,
    'guest' => null,
];

$maybeUser1 = $kernel->data()->getUserById(1);
if ($maybeUser1) $users['user_1'] = $maybeUser1;

foreach ($users as $label => $user) {
    echo PHP_EOL . "--- Context: $label " . ($user ? '(id=' . ($user['id'] ?? '?') . ')' : '(guest)') . " ---\n";

    $paths = ['/admin', '/admin/legal', '/admin/legal/base-data', '/admin/legal/privacy', '/admin/legal/imprint', '/admin/legal/consent', '/admin/legal/settings'];
    foreach ($paths as $p) {
        echo PHP_EOL . "-- Testing path: $p --\n";
        $resolved = $kernel->menus()->resolveTree('admin-sidebar', $user, $p);
        echo "Raw resolved categories: " . count($resolved['categories']) . PHP_EOL;
        print_tree($resolved);

        echo PHP_EOL . "Applying render-time filters (permissions, module activity) ...\n";
        $filtered = filter_resolved($resolved, $kernel, $user);
        echo "Filtered categories: " . count($filtered['categories']) . PHP_EOL;
        print_tree($filtered);

    }

    // Also print a flattened list of items with permission checks for the last filtered set
    echo PHP_EOL . "Flattened items and permission checks (for last tested path):\n";
    foreach ($filtered['categories'] as $group) {
        foreach ($group['items'] as $item) {
            $list = [];
            $stack = [$item];
            while ($stack) {
                $it = array_shift($stack);
                $perm = $it['permission'] ?? '';
                $can = $perm && $user ? ($kernel->permissions()->userCan($user, $perm) ? 'YES' : 'NO') : ($perm ? 'N/A' : '-');
                echo sprintf("* %s (key=%s) perm=%s can=%s visible=%s hidden=%s requires_module=%s is_current=%s is_open=%s\n",
                    $it['display_label'] ?? ($it['translated_label'] ?? $it['key']),
                    $it['key'] ?? '', $perm ?: '-', $can,
                    ($it['is_visible'] ?? true) ? '1' : '0', ($it['is_hidden'] ?? false) ? '1' : '0', $it['requires_module'] ?? '-',
                    !empty($it['is_current']) ? '1' : '0', !empty($it['is_open']) ? '1' : '0'
                );
                foreach ($it['children'] ?? [] as $c) $stack[] = $c;
            }
        }
    }
}

echo PHP_EOL . "Done.\n";
