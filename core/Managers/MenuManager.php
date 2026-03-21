<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Database\Connection;
use Chamy\Core\Interfaces\ManagerInterface;

/**
 * MenuManager – Central menu system for Chamy CMS.
 *
 * Manages locations, categories, items, translations, overrides,
 * registrations from Core/Modules/Themes, and the full tree resolution.
 *
 * Registered as 'menu' in ManagerRegistry, accessible via $kernel->menus().
 */
final class MenuManager implements ManagerInterface
{
    private Connection $db;
    private string $prefix;
    private string $locale;

    /** @var array<string, array> Runtime-registered items from modules/themes (not yet persisted) */
    private array $pendingRegistrations = [];

    /** @var array<string, array> Resolved menu trees, keyed by location/user/path */
    private array $resolvedCache = [];

    /** @var bool Whether initial sync has been performed this request */
    private bool $synced = false;

    public function __construct(Connection $db, string $locale = 'de')
    {
        $this->db = $db;
        $this->prefix = $db->getPrefix();
        $this->locale = $locale;
    }

    public function getName(): string
    {
        return 'menu';
    }

    public function boot(): void
    {
        // Boot is called after all managers are registered.
        // Sync pending registrations from modules/themes into the database.
        // During initial setup/migrations the menu tables might not yet exist.
        // Wrap sync in try/catch so migrations can run without fatal errors.
        try {
            $this->syncRegistrations();
        } catch (\Throwable $e) {
            // Non-fatal: database might not be ready yet. Defer sync to next boot.
            error_log('MenuManager: syncRegistrations skipped during boot — ' . $e->getMessage());
            $this->synced = false;
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // Location CRUD
    // ──────────────────────────────────────────────────────────────────

    /** @return array<int, array> */
    public function getLocations(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->prefix}menu_locations ORDER BY sort_order, id"
        );
    }

    public function getLocation(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->prefix}menu_locations WHERE id = ?",
            [$id]
        );
    }

    public function getLocationByKey(string $key): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->prefix}menu_locations WHERE `key` = ?",
            [$key]
        );
    }

    public function createLocation(string $key, string $label, string $description = '', int $sortOrder = 0): int
    {
        return $this->db->insert('menu_locations', [
            'key'         => $key,
            'label'       => $label,
            'description' => $description,
            'sort_order'  => $sortOrder,
        ]);
    }

    public function updateLocation(int $id, array $data): void
    {
        $allowed = ['key', 'label', 'description', 'is_active', 'sort_order'];
        $set = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $set[] = "`{$col}` = ?";
                $params[] = $data[$col];
            }
        }
        if (empty($set)) {
            return;
        }
        $params[] = $id;
        $this->db->query(
            "UPDATE {$this->prefix}menu_locations SET " . implode(', ', $set) . " WHERE id = ?",
            $params
        );
    }

    public function deleteLocation(int $id): void
    {
        $this->db->query("DELETE FROM {$this->prefix}menu_locations WHERE id = ?", [$id]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Category CRUD
    // ──────────────────────────────────────────────────────────────────

    /** @return array<int, array> Categories with translations */
    public function getCategories(int $locationId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT c.*, ct.label AS translated_label
             FROM {$this->prefix}menu_categories c
             LEFT JOIN {$this->prefix}menu_category_translations ct
                ON ct.category_id = c.id AND ct.locale = ?
             WHERE c.location_id = ?
             ORDER BY c.sort_order, c.id",
            [$this->locale, $locationId]
        );
        foreach ($rows as &$row) {
            $row['display_label'] = $row['translated_label'] ?? $row['key'];
        }
        return $rows;
    }

    public function getCategory(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->prefix}menu_categories WHERE id = ?",
            [$id]
        );
    }

    public function getCategoryByKey(string $locationKey, string $categoryKey): ?array
    {
        return $this->db->fetchOne(
            "SELECT c.* FROM {$this->prefix}menu_categories c
             JOIN {$this->prefix}menu_locations l ON l.id = c.location_id
             WHERE l.`key` = ? AND c.`key` = ?",
            [$locationKey, $categoryKey]
        );
    }

    public function createCategory(int $locationId, string $key, array $labels = [], string $icon = '', int $sortOrder = 0, bool $collapsible = false): int
    {
        $catId = $this->db->insert('menu_categories', [
            'location_id'    => $locationId,
            'key'            => $key,
            'icon'           => $icon ?: null,
            'is_collapsible' => $collapsible ? 1 : 0,
            'sort_order'     => $sortOrder,
        ]);

        foreach ($labels as $locale => $label) {
            $this->db->insert('menu_category_translations', [
                'category_id' => $catId,
                'locale'      => $locale,
                'label'       => $label,
            ]);
        }

        return $catId;
    }

    public function updateCategory(int $id, array $data): void
    {
        $allowed = ['key', 'icon', 'is_active', 'is_collapsible', 'sort_order'];
        $set = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $set[] = "`{$col}` = ?";
                $params[] = $data[$col];
            }
        }
        if (!empty($set)) {
            $params[] = $id;
            $this->db->query(
                "UPDATE {$this->prefix}menu_categories SET " . implode(', ', $set) . " WHERE id = ?",
                $params
            );
        }

        // Update translations if provided
        if (isset($data['labels']) && is_array($data['labels'])) {
            foreach ($data['labels'] as $locale => $label) {
                $this->upsertCategoryTranslation($id, $locale, $label);
            }
        }
    }

    public function deleteCategory(int $id): void
    {
        $this->db->query("DELETE FROM {$this->prefix}menu_categories WHERE id = ?", [$id]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Item CRUD
    // ──────────────────────────────────────────────────────────────────

    /** @return array<int, array> Flat list of items for a category */
    public function getItems(int $categoryId): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, t.label AS translated_label, t.tooltip
             FROM {$this->prefix}menu_items i
             LEFT JOIN {$this->prefix}menu_item_translations t
                ON t.item_id = i.id AND t.locale = ?
             WHERE i.category_id = ?
             ORDER BY i.sort_order, i.id",
            [$this->locale, $categoryId]
        );
    }

    /** @return array<int, array> All items for a location (flat list) */
    public function getItemsByLocation(string $locationKey): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, t.label AS translated_label, t.tooltip, c.`key` AS category_key
             FROM {$this->prefix}menu_items i
             JOIN {$this->prefix}menu_categories c ON c.id = i.category_id
             JOIN {$this->prefix}menu_locations l ON l.id = c.location_id
             LEFT JOIN {$this->prefix}menu_item_translations t
                ON t.item_id = i.id AND t.locale = ?
             WHERE l.`key` = ? AND i.is_active = 1 AND c.is_active = 1
             ORDER BY c.sort_order, c.id, i.sort_order, i.id",
            [$this->locale, $locationKey]
        );
    }

    public function getItem(int $id): ?array
    {
        $item = $this->db->fetchOne(
            "SELECT * FROM {$this->prefix}menu_items WHERE id = ?",
            [$id]
        );
        if ($item) {
            $item['translations'] = $this->getItemTranslations($id);
        }
        return $item;
    }

    public function getItemByKey(string $key): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->prefix}menu_items WHERE `key` = ?",
            [$key]
        );
    }

    /**
     * Create a menu item.
     *
     * @param array $data Item fields
     * @param array<string, array{label: string, tooltip?: string}> $translations Keyed by locale
     */
    public function createItem(array $data, array $translations = []): int
    {
        // Generate UUID if not provided
        if (empty($data['uuid'])) {
            $data['uuid'] = $this->generateUuid();
        }

        // Validate no duplicate key
        if (!empty($data['key'])) {
            $existing = $this->getItemByKey($data['key']);
            if ($existing) {
                throw new \RuntimeException("Menu item key '{$data['key']}' already exists.");
            }
        }

        // Validate no circular parent
        if (!empty($data['parent_id'])) {
            $this->validateParent((int) $data['parent_id'], null);
        }

        $allowed = [
            'uuid', 'category_id', 'parent_id', 'key', 'source', 'source_ref',
            'target_type', 'target_value', 'target_params', 'icon', 'css_class', 'badge',
            'is_active', 'is_visible', 'is_collapsible', 'open_in_new_tab',
            'permission', 'visibility_rule', 'visibility_value',
            'is_manual', 'is_synced', 'override_fields', 'is_hidden',
            'sort_order', 'requires_module', 'requires_feature', 'meta',
        ];

        $insert = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $insert[$col] = $data[$col];
            }
        }

        // If an icon_set or icon_class was provided separately, merge into meta
        if (isset($data['icon_set']) || isset($data['icon_class'])) {
            $meta = [];
            if (!empty($insert['meta'])) {
                $meta = is_string($insert['meta']) ? json_decode($insert['meta'], true) ?? [] : (array) $insert['meta'];
            }
            if (isset($data['icon_set'])) {
                $meta['icon_set'] = $data['icon_set'];
            }
            if (isset($data['icon_class'])) {
                $meta['icon_class'] = $data['icon_class'];
            }
            $insert['meta'] = json_encode($meta);
        }

        $itemId = $this->db->insert('menu_items', $insert);

        foreach ($translations as $locale => $trans) {
            $this->db->insert('menu_item_translations', [
                'item_id' => $itemId,
                'locale'  => $locale,
                'label'   => $trans['label'] ?? $trans,
                'tooltip' => $trans['tooltip'] ?? null,
            ]);
        }

        $this->auditLog($itemId, 'created', null, ['data' => $data]);
        $this->invalidateCache();

        return $itemId;
    }

    /**
     * Update a menu item. Marks the item as manually modified.
     */
    public function updateItem(int $id, array $data, array $translations = []): void
    {
        $before = $this->getItem($id);

        if (!empty($data['parent_id'])) {
            $this->validateParent((int) $data['parent_id'], $id);
        }

        $allowed = [
            'category_id', 'parent_id', 'key', 'target_type', 'target_value', 'target_params',
            'icon', 'css_class', 'badge', 'is_active', 'is_visible', 'is_collapsible',
            'open_in_new_tab', 'permission', 'visibility_rule', 'visibility_value',
            'is_manual', 'is_hidden', 'sort_order', 'requires_module', 'requires_feature', 'meta',
        ];

        $set = [];
        $params = [];
        $changedFields = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $set[] = "`{$col}` = ?";
                $params[] = $data[$col];
                $changedFields[] = $col;
            }
        }

        // Handle meta merging for icon_set/icon_class if provided
        $metaUpdated = false;
        $existingMeta = json_decode($before['meta'] ?? 'null', true) ?: [];
        $mergedMeta = $existingMeta;
        if (array_key_exists('icon_set', $data)) {
            $mergedMeta['icon_set'] = $data['icon_set'];
            $metaUpdated = true;
            if (!in_array('meta', $changedFields, true)) {
                $changedFields[] = 'meta';
            }
        }
        if (array_key_exists('icon_class', $data)) {
            $mergedMeta['icon_class'] = $data['icon_class'];
            $metaUpdated = true;
            if (!in_array('meta', $changedFields, true)) {
                $changedFields[] = 'meta';
            }
        }

        if ($metaUpdated) {
            $set[] = "`meta` = ?";
            $params[] = json_encode($mergedMeta);
        }

        // Mark as manually modified
        if (!empty($set)) {
            // Merge with existing override_fields
            $existingOverrides = json_decode($before['override_fields'] ?? '[]', true) ?: [];
            $mergedOverrides = array_unique(array_merge($existingOverrides, $changedFields));

            $set[] = "`is_manual` = 1";
            $set[] = "`override_fields` = ?";
            $params[] = json_encode($mergedOverrides);

            $params[] = $id;
            $this->db->query(
                "UPDATE {$this->prefix}menu_items SET " . implode(', ', $set) . " WHERE id = ?",
                $params
            );
        }

        // Update translations
        foreach ($translations as $locale => $trans) {
            $label = is_array($trans) ? ($trans['label'] ?? '') : (string) $trans;
            $tooltip = is_array($trans) ? ($trans['tooltip'] ?? null) : null;
            $this->upsertItemTranslation($id, $locale, $label, $tooltip);
        }

        $this->auditLog($id, 'updated', null, [
            'changed_fields' => $changedFields,
            'before'         => $before,
        ]);
        $this->invalidateCache();
    }

    public function deleteItem(int $id): void
    {
        $item = $this->getItem($id);
        $this->db->query("DELETE FROM {$this->prefix}menu_items WHERE id = ?", [$id]);
        $this->auditLog($id, 'deleted', null, ['item' => $item]);
        $this->invalidateCache();
    }

    /**
     * Soft-hide an item (intentionally hidden by admin, won't be re-created by sync).
     */
    public function hideItem(int $id): void
    {
        $this->db->query(
            "UPDATE {$this->prefix}menu_items SET is_hidden = 1, is_manual = 1 WHERE id = ?",
            [$id]
        );
        $this->auditLog($id, 'hidden');
        $this->invalidateCache();
    }

    /**
     * Restore a hidden item.
     */
    public function showItem(int $id): void
    {
        $this->db->query(
            "UPDATE {$this->prefix}menu_items SET is_hidden = 0 WHERE id = ?",
            [$id]
        );
        $this->auditLog($id, 'restored');
        $this->invalidateCache();
    }

    // ──────────────────────────────────────────────────────────────────
    // Reordering (for Drag & Drop)
    // ──────────────────────────────────────────────────────────────────

    /**
     * Bulk update sort order and parent/category assignments.
     *
     * @param array<int, array{sort_order: int, parent_id?: int|null, category_id?: int}> $items
     */
    public function reorder(array $items): void
    {
        $stmt = $this->db->getPdo()->prepare(
            "UPDATE {$this->prefix}menu_items
             SET sort_order = ?, parent_id = ?, category_id = COALESCE(?, category_id)
             WHERE id = ?"
        );
        foreach ($items as $id => $props) {
            $stmt->execute([
                (int) ($props['sort_order'] ?? 0),
                $props['parent_id'] ?? null,
                $props['category_id'] ?? null,
                (int) $id,
            ]);
        }
        $this->auditLog(null, 'reordered', null, ['count' => count($items)]);
        $this->invalidateCache();
    }

    // ──────────────────────────────────────────────────────────────────
    // Tree Resolution
    // ──────────────────────────────────────────────────────────────────

    /**
     * Resolve the full menu tree for a location, grouped by categories.
     *
     * @return array{categories: array, location: array}
     */
    public function resolveTree(
        string $locationKey,
        ?array $user = null,
        ?string $currentPath = null,
        ?callable $permissionChecker = null,
        ?callable $moduleChecker = null
    ): array
    {
        $cacheKey = $this->buildResolveCacheKey($locationKey, $user, $currentPath);
        if (isset($this->resolvedCache[$cacheKey])) {
            return $this->resolvedCache[$cacheKey];
        }

        $location = $this->getLocationByKey($locationKey);
        if (!$location || !$location['is_active']) {
            return ['categories' => [], 'location' => []];
        }

        $categories = $this->getCategories((int) $location['id']);
        $allItems = $this->getItemsByLocation($locationKey);

        // Build the tree
        $result = [];
        foreach ($categories as $cat) {
            $catKey = $cat['key'];
            $catItems = array_filter($allItems, fn($i) => $i['category_key'] === $catKey);

            // Filter by visibility and permissions
            $filtered = [];
            foreach ($catItems as $item) {
                if (!$this->isItemVisible($item, $user)) {
                    continue;
                }

                $permission = trim((string) ($item['permission'] ?? ''));
                if ($permission !== '' && $permissionChecker !== null) {
                    if (!(bool) $permissionChecker($user, $permission)) {
                        continue;
                    }
                }

                // Check module dependency
                if (!empty($item['requires_module'])) {
                    if ($moduleChecker !== null) {
                        if (!(bool) $moduleChecker((string) $item['requires_module'])) {
                            continue;
                        }
                    } else {
                        // Fallback marker if the caller defers module checks.
                        $item['_requires_module_check'] = true;
                    }
                }
                // Decode meta JSON so templates can read meta.icon_set / meta.icon_class
                if (!empty($item['meta']) && is_string($item['meta'])) {
                    $item['meta'] = json_decode($item['meta'], true) ?: [];
                }

                $item['display_label'] = $item['translated_label'] ?? $item['key'];
                $item['is_current'] = $currentPath !== null && $this->matchesPath($item, $currentPath);
                $filtered[] = $item;
            }

            // Build parent-child tree
            $tree = $this->buildTree($filtered);

            // If multiple sibling items match the current path due to prefix
            // matching (e.g. '/admin/legal' and '/admin/legal/privacy'), keep
            // only the most specific matches (longest target) as `is_current`.
            $this->pruneCurrentMatches($tree);

            // Mark parents as open if any child is current
            $this->markOpenParents($tree, $currentPath);

            $result[] = [
                'category'    => $cat,
                'items'       => $tree,
                'has_items'   => !empty($tree),
            ];
        }

        $resolved = ['categories' => $result, 'location' => $location];
        $this->resolvedCache[$cacheKey] = $resolved;
        return $resolved;
    }

    // ──────────────────────────────────────────────────────────────────
    // Registration API (for Modules and Themes)
    // ──────────────────────────────────────────────────────────────────

    /**
     * Register menu items from a module or theme.
     * These are queued and persisted during boot() → syncRegistrations().
     *
     * @param string $source      'core', 'module', or 'theme'
     * @param string $sourceRef   Module ID or theme ID
     * @param array  $items       Array of item definitions
     */
    public function register(string $source, string $sourceRef, array $items): void
    {
        $regKey = $source . ':' . $sourceRef;
        if (!isset($this->pendingRegistrations[$regKey])) {
            $this->pendingRegistrations[$regKey] = [];
        }
        $this->pendingRegistrations[$regKey] = array_merge(
            $this->pendingRegistrations[$regKey],
            $items
        );
    }

    /**
     * Convenience: Register a module's default nav under "Erweiterungen" category.
     *
     * @param string $moduleId   Module identifier
     * @param string $parentKey  The parent menu item key (created if missing)
     * @param array  $children   Sub-items [{key, labels, route/url, icon, permission, sort_order}]
     * @param array  $parentLabels  Labels for parent item {de: '...', en: '...'}
     * @param string $parentIcon    SVG or class for parent icon
     * @param string $parentPermission Permission required all items
     */
    public function registerModuleNav(
        string $moduleId,
        string $parentKey,
        array $children,
        array $parentLabels = [],
        string $parentIcon = '',
        string $parentPermission = ''
    ): void {
        $items = [];

        // Parent item (collapsible container)
        $items[] = [
            'key'            => $parentKey,
            'location'       => 'admin-sidebar',
            'category'       => 'extensions',
            'target_type'    => 'container',
            'is_collapsible' => true,
            'icon'           => $parentIcon,
            'labels'         => $parentLabels,
            'permission'     => $parentPermission,
            'sort_order'     => 0,
        ];

        // Children
        foreach ($children as $idx => $child) {
            $child['parent_key'] = $parentKey;
            $child['location']   = 'admin-sidebar';
            $child['category']   = 'extensions';
            $child['sort_order'] = $child['sort_order'] ?? (($idx + 1) * 10);
            $items[] = $child;
        }

        $this->register('module', $moduleId, $items);
    }

    // ──────────────────────────────────────────────────────────────────
    // Sync: Persist registered items into DB without destroying manual edits
    // ──────────────────────────────────────────────────────────────────

    public function syncRegistrations(): void
    {
        if ($this->synced || empty($this->pendingRegistrations)) {
            $this->synced = true;
            return;
        }

        foreach ($this->pendingRegistrations as $regKey => $items) {
            [$source, $sourceRef] = explode(':', $regKey, 2);
            foreach ($items as $itemDef) {
                $this->syncSingleItem($source, $sourceRef, $itemDef);
            }
        }

        $this->synced = true;
        $this->pendingRegistrations = [];
        $this->invalidateCache();
    }

    // ──────────────────────────────────────────────────────────────────
    // Portierung / Import
    // ──────────────────────────────────────────────────────────────────

    /**
     * Import a batch of legacy menu items. Used during initial portierung.
     *
     * @param array $items Items with keys matching createItem() format
     */
    public function importItems(array $items): array
    {
        $results = ['created' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($items as $item) {
            try {
                $existing = $this->getItemByKey($item['key'] ?? '');
                if ($existing) {
                    $results['skipped']++;
                    continue;
                }
                $item['source'] = $item['source'] ?? 'import';
                $this->createItem($item, $item['translations'] ?? []);
                $results['created']++;
            } catch (\Throwable $e) {
                $results['errors'][] = ['key' => $item['key'] ?? '?', 'error' => $e->getMessage()];
            }
        }

        $this->auditLog(null, 'imported', null, $results);
        return $results;
    }

    // ──────────────────────────────────────────────────────────────────
    // Translations
    // ──────────────────────────────────────────────────────────────────

    /** @return array<string, array{label: string, tooltip: ?string}> Keyed by locale */
    public function getItemTranslations(int $itemId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT locale, label, tooltip FROM {$this->prefix}menu_item_translations WHERE item_id = ?",
            [$itemId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['locale']] = ['label' => $row['label'], 'tooltip' => $row['tooltip']];
        }
        return $result;
    }

    public function getCategoryTranslations(int $categoryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT locale, label FROM {$this->prefix}menu_category_translations WHERE category_id = ?",
            [$categoryId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['locale']] = $row['label'];
        }
        return $result;
    }

    // ──────────────────────────────────────────────────────────────────
    // Audit Log
    // ──────────────────────────────────────────────────────────────────

    public function getAuditLog(int $limit = 50, ?int $itemId = null): array
    {
        if ($itemId !== null) {
            return $this->db->fetchAll(
                "SELECT * FROM {$this->prefix}menu_audit_log WHERE item_id = ? ORDER BY created_at DESC LIMIT " . (int) $limit,
                [$itemId]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM {$this->prefix}menu_audit_log ORDER BY created_at DESC LIMIT " . (int) $limit
        );
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->invalidateCache();
    }

    // ──────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────

    private function syncSingleItem(string $source, string $sourceRef, array $def): void
    {
        $key = $def['key'] ?? null;
        if (!$key) {
            return;
        }

        $existing = $this->getItemByKey($key);

        // If item was intentionally hidden or manually modified, don't overwrite protected fields
        if ($existing) {
            if ($existing['is_hidden']) {
                return; // Admin explicitly hid this item
            }

            // Only update fields that haven't been manually overridden
            $overriddenFields = json_decode($existing['override_fields'] ?? '[]', true) ?: [];
            $updateData = [];

            $syncableFields = ['icon', 'target_value', 'target_type', 'permission', 'requires_module'];
            foreach ($syncableFields as $field) {
                if (array_key_exists($field, $def) && !in_array($field, $overriddenFields, true)) {
                    $updateData[$field] = $def[$field];
                }
            }

            if (!empty($updateData)) {
                $updateData['is_synced'] = 1;
                $set = [];
                $params = [];
                foreach ($updateData as $col => $val) {
                    $set[] = "`{$col}` = ?";
                    $params[] = $val;
                }
                $params[] = $existing['id'];
                $this->db->query(
                    "UPDATE {$this->prefix}menu_items SET " . implode(', ', $set) . " WHERE id = ?",
                    $params
                );
            }

            // Update translations only for non-overridden locales
            if (!empty($def['labels']) && !in_array('label', $overriddenFields, true)) {
                foreach ($def['labels'] as $locale => $label) {
                    $labelText = is_array($label) ? ($label['label'] ?? '') : (string) $label;
                    $this->upsertItemTranslation((int) $existing['id'], $locale, $labelText);
                }
            }

            return;
        }

        // New item — ensure location and category exist
        $locationKey = $def['location'] ?? 'admin-sidebar';
        $categoryKey = $def['category'] ?? 'extensions';

        $location = $this->getLocationByKey($locationKey);
        if (!$location) {
            return; // Unknown location
        }

        $category = $this->getCategoryByKey($locationKey, $categoryKey);
        if (!$category) {
            // Auto-create category for modules
            $catLabels = [];
            if ($categoryKey === 'extensions') {
                $catLabels = ['de' => 'Erweiterungen', 'en' => 'Extensions'];
            }
            $catId = $this->createCategory(
                (int) $location['id'],
                $categoryKey,
                $catLabels,
                '',
                80 // After system categories
            );
            $category = $this->getCategory($catId);
        }

        // Resolve parent
        $parentId = null;
        if (!empty($def['parent_key'])) {
            $parent = $this->getItemByKey($def['parent_key']);
            if ($parent) {
                $parentId = (int) $parent['id'];
            }
        }

        $insertData = [
            'uuid'            => $this->generateUuid(),
            'category_id'     => (int) $category['id'],
            'parent_id'       => $parentId,
            'key'             => $key,
            'source'          => $source,
            'source_ref'      => $sourceRef,
            'target_type'     => $def['target_type'] ?? 'route',
            'target_value'    => $def['target_value'] ?? $def['route'] ?? $def['url'] ?? null,
            'target_params'   => isset($def['target_params']) ? json_encode($def['target_params']) : null,
            'icon'            => $def['icon'] ?? null,
            'is_active'       => 1,
            'is_visible'      => 1,
            'is_collapsible'  => !empty($def['is_collapsible']) ? 1 : 0,
            'permission'      => $def['permission'] ?? null,
            'visibility_rule' => $def['visibility_rule'] ?? 'all',
            'is_manual'       => 0,
            'is_synced'       => 1,
            'sort_order'      => (int) ($def['sort_order'] ?? 0),
            'requires_module' => $def['requires_module'] ?? ($source === 'module' ? $sourceRef : null),
        ];

        $translations = [];
        if (!empty($def['labels'])) {
            foreach ($def['labels'] as $locale => $label) {
                $translations[$locale] = is_array($label) ? $label : ['label' => (string) $label];
            }
        }

        $this->createItem($insertData, $translations);
    }

    private function upsertItemTranslation(int $itemId, string $locale, string $label, ?string $tooltip = null): void
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM {$this->prefix}menu_item_translations WHERE item_id = ? AND locale = ?",
            [$itemId, $locale]
        );
        if ($existing) {
            $this->db->query(
                "UPDATE {$this->prefix}menu_item_translations SET label = ?, tooltip = ? WHERE id = ?",
                [$label, $tooltip, $existing['id']]
            );
        } else {
            $this->db->insert('menu_item_translations', [
                'item_id' => $itemId,
                'locale'  => $locale,
                'label'   => $label,
                'tooltip' => $tooltip,
            ]);
        }
    }

    private function upsertCategoryTranslation(int $categoryId, string $locale, string $label): void
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM {$this->prefix}menu_category_translations WHERE category_id = ? AND locale = ?",
            [$categoryId, $locale]
        );
        if ($existing) {
            $this->db->query(
                "UPDATE {$this->prefix}menu_category_translations SET label = ? WHERE id = ?",
                [$label, $existing['id']]
            );
        } else {
            $this->db->insert('menu_category_translations', [
                'category_id' => $categoryId,
                'locale'      => $locale,
                'label'       => $label,
            ]);
        }
    }

    /** Build nested tree from flat items */
    private function buildTree(array $items, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($items as $item) {
            $itemParent = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;
            if ($itemParent === $parentId) {
                $item['children'] = $this->buildTree($items, (int) $item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /** Recursively mark parent items as 'open' if any descendant is current */
    private function markOpenParents(array &$tree, ?string $currentPath): bool
    {
        $anyOpen = false;
        foreach ($tree as &$item) {
            $childOpen = false;
            if (!empty($item['children'])) {
                $childOpen = $this->markOpenParents($item['children'], $currentPath);
            }
            if ($childOpen || ($item['is_current'] ?? false)) {
                $item['is_open'] = true;
                $anyOpen = true;
            }
        }
        return $anyOpen;
    }

    private function isItemVisible(array $item, ?array $user): bool
    {
        if ($item['is_hidden'] ?? false) {
            return false;
        }
        if (!($item['is_visible'] ?? true)) {
            return false;
        }

        $rule = $item['visibility_rule'] ?? 'all';
        switch ($rule) {
            case 'all':
                return true;
            case 'authenticated':
                return $user !== null;
            case 'guest':
                return $user === null;
            case 'permission':
                if (!$user) {
                    return false;
                }
                $perm = $item['permission'] ?? '';
                if ($perm === '') {
                    return true;
                }
                // Keep visibility rule checks lightweight; concrete permission checks
                // are done by resolveTree() when a permission checker is provided.
                return true;
            case 'role':
                if (!$user) {
                    return false;
                }
                $requiredRole = $item['visibility_value'] ?? '';
                if ($requiredRole === '') {
                    return true;
                }

                $userRoles = [];
                if (isset($user['roles']) && is_array($user['roles'])) {
                    $userRoles = array_map(static fn(mixed $r): string => strtolower(trim((string) $r)), $user['roles']);
                } elseif (!empty($user['role'])) {
                    $userRoles = [strtolower(trim((string) $user['role']))];
                }

                return in_array(strtolower(trim((string) $requiredRole)), $userRoles, true);
            default:
                return true;
        }
    }

    private function buildResolveCacheKey(string $locationKey, ?array $user, ?string $currentPath): string
    {
        $userPart = 'guest';
        if ($user !== null) {
            $roles = [];
            if (isset($user['roles']) && is_array($user['roles'])) {
                $roles = array_values(array_map(static fn(mixed $r): string => (string) $r, $user['roles']));
            } elseif (!empty($user['role'])) {
                $roles = [(string) $user['role']];
            }
            sort($roles);

            $userPart = 'u:' . (string) ($user['id'] ?? '0') . '|r:' . implode(',', $roles);
        }

        return implode('|', [
            'loc:' . $locationKey,
            'locale:' . $this->locale,
            'path:' . ($currentPath ?? ''),
            $userPart,
        ]);
    }

    private function matchesPath(array $item, string $currentPath): bool
    {
        $target = $item['target_value'] ?? '';
        if ($target === '' || $item['target_type'] === 'separator' || $item['target_type'] === 'heading') {
            return false;
        }
        // Exact match first
        if ($currentPath === $target) {
            return true;
        }

        // Avoid overly-broad matching for the admin root path.
        // Treat the top-level admin dashboard ('/admin') as an exact-only route
        // so it does not match unrelated sub-paths like '/admin/legal'.
        if ($target === '/admin') {
            return false;
        }

        // Allow prefix matching for nested routes (e.g. '/admin/content' -> '/admin/content/...')
        return str_starts_with($currentPath, $target . '/');
    }

    /**
     * For each group of sibling items, if multiple items are marked as
     * `is_current` (caused by prefix matching), keep only those with the
     * longest `target_value` (most specific). Recurses into children.
     *
     * @param array $items
     * @return void
     */
    private function pruneCurrentMatches(array &$items): void
    {
        foreach ($items as &$item) {
            if (!empty($item['children'])) {
                $this->pruneCurrentMatches($item['children']);
            }
        }

        // Collect current matches among immediate siblings
        $currentMatches = array_filter($items, fn($i) => !empty($i['is_current']));
        if (count($currentMatches) <= 1) {
            return;
        }

        // Determine longest target length among matches
        $maxLen = 0;
        foreach ($currentMatches as $m) {
            $t = $m['target_value'] ?? '';
            $len = strlen($t);
            if ($len > $maxLen) $maxLen = $len;
        }

        // Clear is_current on matches that are shorter than the max length
        foreach ($items as &$item) {
            if (!empty($item['is_current'])) {
                $t = $item['target_value'] ?? '';
                if (strlen($t) < $maxLen) {
                    $item['is_current'] = false;
                }
            }
        }
    }

    private function validateParent(int $parentId, ?int $selfId): void
    {
        if ($selfId !== null && $parentId === $selfId) {
            throw new \RuntimeException('A menu item cannot be its own parent.');
        }

        // Check for circular reference
        $visited = [$parentId];
        $current = $parentId;
        $maxDepth = 20;
        while ($maxDepth-- > 0) {
            $parent = $this->db->fetchOne(
                "SELECT parent_id FROM {$this->prefix}menu_items WHERE id = ?",
                [$current]
            );
            if (!$parent || $parent['parent_id'] === null) {
                break;
            }
            $current = (int) $parent['parent_id'];
            if ($selfId !== null && $current === $selfId) {
                throw new \RuntimeException('Circular parent-child reference detected.');
            }
            if (in_array($current, $visited, true)) {
                throw new \RuntimeException('Circular parent-child reference detected.');
            }
            $visited[] = $current;
        }
    }

    private function auditLog(?int $itemId, string $action, ?string $actor = null, ?array $details = null): void
    {
        try {
            $this->db->insert('menu_audit_log', [
                'item_id'    => $itemId,
                'action'     => $action,
                'actor'      => $actor ?? 'system',
                'details'    => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable) {
            // Audit logging should never break the app
        }
    }

    private function invalidateCache(): void
    {
        $this->resolvedCache = [];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
