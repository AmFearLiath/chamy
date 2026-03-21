<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

/**
 * MenuController – Admin CRUD for the MenuManager.
 *
 * All routes are prefixed /admin/menus and require 'system.manage' permission.
 */
final class MenuController
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    // ──────────────────────────────────────────────────────────────────
    // Auth helpers (same pattern as AdminController)
    // ──────────────────────────────────────────────────────────────────

    private function requirePermission(string $permission): ?Response
    {
        $userId = $this->kernel->session()->get('user_id');
        if (!$userId) {
            return Response::redirect('/admin/login');
        }
        $user = $this->kernel->data()->getUserById($userId);
        if (!$user || !$this->kernel->permissions()->userCan($user, $permission)) {
            return Response::html(
                $this->kernel->themes()->render('errors/403.twig', $this->baseData()),
                403
            );
        }
        return null;
    }

    private function currentUser(): ?array
    {
        $userId = $this->kernel->session()->get('user_id');
        return $userId ? $this->kernel->data()->getUserById($userId) : null;
    }

    private function baseData(): array
    {
        $types = $this->kernel->contentTypes()->getAllTypes();
        $settings = $this->kernel->data()->getSettings();

        return [
            'user'           => $this->currentUser(),
            'content_types'  => $types,
            'content_labels' => [
                'article'       => 'Artikel',
                'page'          => 'Seiten',
                'media_entry'   => 'Medien',
                'documentation' => 'Dokumentationen',
            ],
            'app_locale'     => $this->kernel->config()->get('APP_LOCALE', 'de'),
            'app_version'    => '1.0.0',
            'php_version'    => PHP_VERSION,
            'current_theme'  => 'Neon Dark',
            'flash_messages' => $this->kernel->session()->getAllFlash(),
            'admin_icon_css' => [],
            'admin_font_css' => [],
            'sidebar_icon_mode' => 'tabler',
            'sidebar_icons'  => [],
        ];
    }

    private function render(string $template, array $data = []): Response
    {
        return Response::html(
            $this->kernel->themes()->render($template, array_merge($this->baseData(), $data))
        );
    }

    private function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    // ──────────────────────────────────────────────────────────────────
    // Dashboard / Overview
    // ──────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $mm = $this->kernel->menus();
        $locations = $mm->getLocations();

        // Enrich locations with category + item counts
        foreach ($locations as &$loc) {
            $cats = $mm->getCategories((int) $loc['id']);
            $loc['category_count'] = count($cats);
            $itemCount = 0;
            foreach ($cats as $cat) {
                $itemCount += count($mm->getItems((int) $cat['id']));
            }
            $loc['item_count'] = $itemCount;
        }

        return $this->render('menus/index.twig', [
            'page_title'     => $this->kernel->lang()->translate('menu.title'),
            'current_route'  => 'menus',
            'locations'      => $locations,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Location Detail – Tree Editor (main Drag & Drop page)
    // ──────────────────────────────────────────────────────────────────

    public function locationDetail(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $id = (int) $request->getRouteParam('id');
        $mm = $this->kernel->menus();
        $location = $mm->getLocation($id);

        if (!$location) {
            $this->kernel->session()->flash('error', $this->kernel->lang()->translate('menu.location_not_found'));
            return Response::redirect('/admin/menus');
        }

        $categories = $mm->getCategories($id);
        $tree = [];
        foreach ($categories as $cat) {
            $items = $mm->getItems((int) $cat['id']);
            $catTrans = $mm->getCategoryTranslations((int) $cat['id']);
            $cat['translations'] = $catTrans;
            $cat['display_label'] = $catTrans[$this->kernel->config()->get('APP_LOCALE', 'de')] ?? $cat['key'];
            $tree[] = [
                'category' => $cat,
                'items'    => $this->nestItems($items),
            ];
        }

        return $this->render('menus/location.twig', [
            'page_title'    => $location['label'],
            'current_route' => 'menus',
            'location'      => $location,
            'tree'          => $tree,
            'locales'       => ['de', 'en'],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Location CRUD
    // ──────────────────────────────────────────────────────────────────

    public function locationCreate(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        return $this->render('menus/location_form.twig', [
            'page_title'    => $this->kernel->lang()->translate('menu.location_create'),
            'current_route' => 'menus',
            'location'      => null,
            'mode'          => 'create',
        ]);
    }

    public function locationStore(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $data = $request->getPost();
        $key = trim((string) ($data['key'] ?? ''));
        $label = trim((string) ($data['label'] ?? ''));

        if ($key === '' || $label === '') {
            $this->kernel->session()->flash('error', $this->kernel->lang()->translate('menu.validation_required'));
            return Response::redirect('/admin/menus/locations/create');
        }

        // Validate key format
        if (!preg_match('/^[a-z0-9][a-z0-9\-_]*$/', $key)) {
            $this->kernel->session()->flash('error', $this->kernel->lang()->translate('menu.validation_key_format'));
            return Response::redirect('/admin/menus/locations/create');
        }

        $mm = $this->kernel->menus();
        $existing = $mm->getLocationByKey($key);
        if ($existing) {
            $this->kernel->session()->flash('error', $this->kernel->lang()->translate('menu.validation_key_exists'));
            return Response::redirect('/admin/menus/locations/create');
        }

        $mm->createLocation(
            $key,
            $label,
            trim((string) ($data['description'] ?? '')),
            (int) ($data['sort_order'] ?? 0)
        );

        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.location_created'));
        return Response::redirect('/admin/menus');
    }

    public function locationEdit(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $id = (int) $request->getRouteParam('id');
        $location = $this->kernel->menus()->getLocation($id);
        if (!$location) {
            return Response::redirect('/admin/menus');
        }

        return $this->render('menus/location_form.twig', [
            'page_title'    => $this->kernel->lang()->translate('menu.location_edit'),
            'current_route' => 'menus',
            'location'      => $location,
            'mode'          => 'edit',
        ]);
    }

    public function locationUpdate(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $id = (int) $request->getRouteParam('id');
        $data = $request->getPost();

        $this->kernel->menus()->updateLocation($id, [
            'label'       => trim((string) ($data['label'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active'   => isset($data['is_active']) ? 1 : 0,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ]);

        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.location_updated'));
        return Response::redirect('/admin/menus');
    }

    public function locationDelete(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $id = (int) $request->getRouteParam('id');
        $this->kernel->menus()->deleteLocation($id);
        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.location_deleted'));
        return Response::redirect('/admin/menus');
    }

    // ──────────────────────────────────────────────────────────────────
    // Category CRUD
    // ──────────────────────────────────────────────────────────────────

    public function categoryCreate(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $locId = (int) $request->getRouteParam('location_id');
        $location = $this->kernel->menus()->getLocation($locId);
        if (!$location) {
            return Response::redirect('/admin/menus');
        }

        return $this->render('menus/category_form.twig', [
            'page_title'    => $this->kernel->lang()->translate('menu.category_create'),
            'current_route' => 'menus',
            'location'      => $location,
            'category'      => null,
            'mode'          => 'create',
            'locales'       => ['de', 'en'],
        ]);
    }

    public function categoryStore(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $locId = (int) $request->getRouteParam('location_id');
        $data = $request->getPost();
        $key = trim((string) ($data['key'] ?? ''));

        if ($key === '') {
            $this->kernel->session()->flash('error', $this->kernel->lang()->translate('menu.validation_required'));
            return Response::redirect("/admin/menus/locations/{$locId}/categories/create");
        }

        $labels = [];
        foreach (['de', 'en'] as $locale) {
            $val = trim((string) ($data["label_{$locale}"] ?? ''));
            if ($val !== '') {
                $labels[$locale] = $val;
            }
        }

        $this->kernel->menus()->createCategory(
            $locId,
            $key,
            $labels,
            trim((string) ($data['icon'] ?? '')),
            (int) ($data['sort_order'] ?? 0),
            isset($data['is_collapsible'])
        );

        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.category_created'));
        return Response::redirect("/admin/menus/locations/{$locId}");
    }

    public function categoryEdit(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $catId = (int) $request->getRouteParam('id');
        $mm = $this->kernel->menus();
        $cat = $mm->getCategory($catId);
        if (!$cat) {
            return Response::redirect('/admin/menus');
        }
        $location = $mm->getLocation((int) $cat['location_id']);
        $cat['translations'] = $mm->getCategoryTranslations($catId);

        return $this->render('menus/category_form.twig', [
            'page_title'    => $this->kernel->lang()->translate('menu.category_edit'),
            'current_route' => 'menus',
            'location'      => $location,
            'category'      => $cat,
            'mode'          => 'edit',
            'locales'       => ['de', 'en'],
        ]);
    }

    public function categoryUpdate(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $catId = (int) $request->getRouteParam('id');
        $cat = $this->kernel->menus()->getCategory($catId);
        if (!$cat) {
            return Response::redirect('/admin/menus');
        }

        $data = $request->getPost();
        $labels = [];
        foreach (['de', 'en'] as $locale) {
            $val = trim((string) ($data["label_{$locale}"] ?? ''));
            if ($val !== '') {
                $labels[$locale] = $val;
            }
        }

        $this->kernel->menus()->updateCategory($catId, [
            'icon'           => trim((string) ($data['icon'] ?? '')),
            'is_active'      => isset($data['is_active']) ? 1 : 0,
            'is_collapsible' => isset($data['is_collapsible']) ? 1 : 0,
            'sort_order'     => (int) ($data['sort_order'] ?? 0),
            'labels'         => $labels,
        ]);

        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.category_updated'));
        return Response::redirect("/admin/menus/locations/{$cat['location_id']}");
    }

    public function categoryDelete(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $catId = (int) $request->getRouteParam('id');
        $cat = $this->kernel->menus()->getCategory($catId);
        $locId = $cat ? $cat['location_id'] : 0;
        $this->kernel->menus()->deleteCategory($catId);
        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.category_deleted'));
        return Response::redirect("/admin/menus/locations/{$locId}");
    }

    // ──────────────────────────────────────────────────────────────────
    // Item CRUD
    // ──────────────────────────────────────────────────────────────────

    public function itemCreate(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $locId = (int) $request->getRouteParam('location_id');
        $mm = $this->kernel->menus();
        $location = $mm->getLocation($locId);
        if (!$location) return Response::redirect('/admin/menus');

        $categories = $mm->getCategories($locId);

        // Get all existing items for parent selection
        $allItems = [];
        foreach ($categories as $cat) {
            $items = $mm->getItems((int) $cat['id']);
            foreach ($items as $item) {
                $allItems[] = $item;
            }
        }

        // Provide available icon sets for the form
        $iconSets = $this->kernel->assetLibrary()->listIconSets();

        return $this->render('menus/item_form.twig', [
            'page_title'       => $this->kernel->lang()->translate('menu.item_create'),
            'current_route'    => 'menus',
            'location'         => $location,
            'categories'       => $categories,
            'parent_items'     => $allItems,
            'item'             => null,
            'mode'             => 'create',
            'locales'          => ['de', 'en'],
            'target_types'     => ['route', 'url', 'content', 'separator', 'heading', 'container', 'action'],
            'visibility_rules' => ['all', 'authenticated', 'guest', 'permission', 'role'],
            'icon_sets'        => $iconSets,
        ]);
    }

    public function itemStore(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $locId = (int) $request->getRouteParam('location_id');
        $data = $request->getPost();

        $key = trim((string) ($data['key'] ?? ''));
        if ($key === '') {
            $this->kernel->session()->flash('error', $this->kernel->lang()->translate('menu.validation_required'));
            return Response::redirect("/admin/menus/locations/{$locId}/items/create");
        }

        $translations = [];
        foreach (['de', 'en'] as $locale) {
            $label = trim((string) ($data["label_{$locale}"] ?? ''));
            if ($label !== '') {
                $translations[$locale] = ['label' => $label, 'tooltip' => trim((string) ($data["tooltip_{$locale}"] ?? ''))];
            }
        }

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        // Handle icon set / meta normalization so rendering knows the selected package mode
        $iconSet = trim((string) ($data['icon_set'] ?? ''));
        $meta = [];
        if ($iconSet !== '') {
            $meta['icon_set'] = $iconSet;
            // Determine simple mode (e.g. 'tabler') from installed icon sets
            $mode = 'classic';
            foreach ($this->kernel->assetLibrary()->listIconSets() as $set) {
                $setId = strtolower((string) ($set['id'] ?? ''));
                $setName = strtolower((string) ($set['name'] ?? ''));
                if ($setId === strtolower($iconSet) || str_contains($setId, 'tabler') || str_contains($setName, 'tabler')) {
                    $mode = 'tabler';
                    break;
                }
            }
            $meta['icon_mode'] = $mode;
        }

        $this->kernel->menus()->createItem([
            'category_id'     => (int) ($data['category_id'] ?? 0),
            'parent_id'       => $parentId,
            'key'             => $key,
            'source'          => 'manual',
            'target_type'     => $data['target_type'] ?? 'route',
            'target_value'    => trim((string) ($data['target_value'] ?? '')),
            'icon'            => trim((string) ($data['icon'] ?? '')),
            'css_class'       => trim((string) ($data['css_class'] ?? '')),
            'badge'           => trim((string) ($data['badge'] ?? '')) ?: null,
            'is_active'       => isset($data['is_active']) ? 1 : 0,
            'is_visible'      => isset($data['is_visible']) ? 1 : 0,
            'is_collapsible'  => isset($data['is_collapsible']) ? 1 : 0,
            'open_in_new_tab' => isset($data['open_in_new_tab']) ? 1 : 0,
            'permission'      => trim((string) ($data['permission'] ?? '')) ?: null,
            'visibility_rule' => $data['visibility_rule'] ?? 'all',
            'visibility_value'=> trim((string) ($data['visibility_value'] ?? '')) ?: null,
            'sort_order'      => (int) ($data['sort_order'] ?? 0),
            'is_manual'       => 1,
            'meta'            => !empty($meta) ? json_encode($meta) : null,
        ], $translations);

        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.item_created'));
        return Response::redirect("/admin/menus/locations/{$locId}");
    }

    public function itemEdit(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $itemId = (int) $request->getRouteParam('id');
        $mm = $this->kernel->menus();
        $item = $mm->getItem($itemId);
        if (!$item) {
            return Response::redirect('/admin/menus');
        }

        $cat = $mm->getCategory((int) $item['category_id']);
        $location = $cat ? $mm->getLocation((int) $cat['location_id']) : null;
        if (!$location) {
            return Response::redirect('/admin/menus');
        }

        $categories = $mm->getCategories((int) $location['id']);
        $allItems = [];
        foreach ($categories as $c) {
            foreach ($mm->getItems((int) $c['id']) as $i) {
                if ((int) $i['id'] !== $itemId) {
                    $allItems[] = $i;
                }
            }
        }

        // decode meta if present so template can read meta.icon_set
        if (!empty($item['meta']) && is_string($item['meta'])) {
            $decoded = json_decode($item['meta'], true) ?: [];
            $item['meta'] = $decoded;
        }

        $iconSets = $this->kernel->assetLibrary()->listIconSets();

        return $this->render('menus/item_form.twig', [
            'page_title'       => $this->kernel->lang()->translate('menu.item_edit'),
            'current_route'    => 'menus',
            'location'         => $location,
            'categories'       => $categories,
            'parent_items'     => $allItems,
            'item'             => $item,
            'mode'             => 'edit',
            'locales'          => ['de', 'en'],
            'target_types'     => ['route', 'url', 'content', 'separator', 'heading', 'container', 'action'],
            'visibility_rules' => ['all', 'authenticated', 'guest', 'permission', 'role'],
            'icon_sets'        => $iconSets,
        ]);
    }

    public function itemUpdate(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $itemId = (int) $request->getRouteParam('id');
        $data = $request->getPost();
        $mm = $this->kernel->menus();
        $item = $mm->getItem($itemId);
        if (!$item) {
            return Response::redirect('/admin/menus');
        }

        $cat = $mm->getCategory((int) $item['category_id']);
        $locId = $cat ? $cat['location_id'] : 0;

        $translations = [];
        foreach (['de', 'en'] as $locale) {
            $label = trim((string) ($data["label_{$locale}"] ?? ''));
            if ($label !== '') {
                $translations[$locale] = ['label' => $label, 'tooltip' => trim((string) ($data["tooltip_{$locale}"] ?? ''))];
            }
        }

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        // Handle icon_set/meta normalization for updates
        $iconSet = trim((string) ($data['icon_set'] ?? ''));
        $meta = [];
        if ($iconSet !== '') {
            $meta['icon_set'] = $iconSet;
            $mode = 'classic';
            foreach ($this->kernel->assetLibrary()->listIconSets() as $set) {
                $setId = strtolower((string) ($set['id'] ?? ''));
                $setName = strtolower((string) ($set['name'] ?? ''));
                if ($setId === strtolower($iconSet) || str_contains($setId, 'tabler') || str_contains($setName, 'tabler')) {
                    $mode = 'tabler';
                    break;
                }
            }
            $meta['icon_mode'] = $mode;
        }

        $mm->updateItem($itemId, [
            'category_id'     => (int) ($data['category_id'] ?? $item['category_id']),
            'parent_id'       => $parentId,
            'target_type'     => $data['target_type'] ?? $item['target_type'],
            'target_value'    => trim((string) ($data['target_value'] ?? '')),
            'icon'            => trim((string) ($data['icon'] ?? '')),
            'css_class'       => trim((string) ($data['css_class'] ?? '')),
            'badge'           => trim((string) ($data['badge'] ?? '')) ?: null,
            'is_active'       => isset($data['is_active']) ? 1 : 0,
            'is_visible'      => isset($data['is_visible']) ? 1 : 0,
            'is_collapsible'  => isset($data['is_collapsible']) ? 1 : 0,
            'open_in_new_tab' => isset($data['open_in_new_tab']) ? 1 : 0,
            'permission'      => trim((string) ($data['permission'] ?? '')) ?: null,
            'visibility_rule' => $data['visibility_rule'] ?? 'all',
            'visibility_value'=> trim((string) ($data['visibility_value'] ?? '')) ?: null,
            'sort_order'      => (int) ($data['sort_order'] ?? 0),
            'meta'            => !empty($meta) ? json_encode($meta) : null,
        ], $translations);

        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.item_updated'));
        return Response::redirect("/admin/menus/locations/{$locId}");
    }

    public function itemDelete(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $itemId = (int) $request->getRouteParam('id');
        $mm = $this->kernel->menus();
        $item = $mm->getItem($itemId);
        $cat = $item ? $mm->getCategory((int) $item['category_id']) : null;
        $locId = $cat ? $cat['location_id'] : 0;

        $mm->deleteItem($itemId);
        $this->kernel->session()->flash('success', $this->kernel->lang()->translate('menu.item_deleted'));
        return Response::redirect("/admin/menus/locations/{$locId}");
    }

    public function itemToggleVisibility(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $itemId = (int) $request->getRouteParam('id');
        $mm = $this->kernel->menus();
        $item = $mm->getItem($itemId);
        if (!$item) {
            return $this->json(['error' => 'Not found'], 404);
        }

        if ($item['is_hidden']) {
            $mm->showItem($itemId);
        } else {
            $mm->hideItem($itemId);
        }

        return $this->json(['ok' => true, 'hidden' => !$item['is_hidden']]);
    }

    // ──────────────────────────────────────────────────────────────────
    // JSON API: Drag & Drop reorder
    // ──────────────────────────────────────────────────────────────────

    public function apiReorder(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $this->json(['error' => 'Forbidden'], 403);

        $body = $request->getPost();
        $items = $body['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            return $this->json(['error' => 'No items provided'], 400);
        }

        $reorderData = [];
        foreach ($items as $entry) {
            $id = (int) ($entry['id'] ?? 0);
            if ($id < 1) continue;
            $reorderData[$id] = [
                'sort_order'  => (int) ($entry['sort_order'] ?? 0),
                'parent_id'   => isset($entry['parent_id']) && $entry['parent_id'] !== '' ? (int) $entry['parent_id'] : null,
                'category_id' => isset($entry['category_id']) && $entry['category_id'] !== '' ? (int) $entry['category_id'] : null,
            ];
        }

        $this->kernel->menus()->reorder($reorderData);
        return $this->json(['ok' => true, 'count' => count($reorderData)]);
    }

    // ──────────────────────────────────────────────────────────────────
    // JSON API: Tree data for frontend JS
    // ──────────────────────────────────────────────────────────────────

    public function apiTree(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $this->json(['error' => 'Forbidden'], 403);

        $locationId = (int) $request->getRouteParam('id');
        $mm = $this->kernel->menus();
        $location = $mm->getLocation($locationId);
        if (!$location) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $categories = $mm->getCategories($locationId);
        $tree = [];

        foreach ($categories as $cat) {
            $items = $mm->getItems((int) $cat['id']);
            $catTrans = $mm->getCategoryTranslations((int) $cat['id']);
            $tree[] = [
                'category' => [
                    'id'      => (int) $cat['id'],
                    'key'     => $cat['key'],
                    'label'   => $catTrans[$this->kernel->config()->get('APP_LOCALE', 'de')] ?? $cat['key'],
                    'sort_order' => (int) $cat['sort_order'],
                ],
                'items' => $this->nestItemsForApi($items),
            ];
        }

        return $this->json(['location' => $location, 'tree' => $tree]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Audit Log
    // ──────────────────────────────────────────────────────────────────

    public function auditLog(Request $request): Response
    {
        $deny = $this->requirePermission('system.manage');
        if ($deny) return $deny;

        $log = $this->kernel->menus()->getAuditLog(100);

        return $this->render('menus/audit.twig', [
            'page_title'    => $this->kernel->lang()->translate('menu.audit_log'),
            'current_route' => 'menus',
            'log'           => $log,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    /** Convert flat items into nested tree for template rendering */
    private function nestItems(array $items, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($items as $item) {
            $pid = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;
            if ($pid === $parentId) {
                $item['children'] = $this->nestItems($items, (int) $item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /** Nest items and strip heavy fields for API */
    private function nestItemsForApi(array $items, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($items as $item) {
            $pid = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;
            if ($pid === $parentId) {
                $node = [
                    'id'            => (int) $item['id'],
                    'key'           => $item['key'],
                    'label'         => $item['translated_label'] ?? $item['key'],
                    'icon'          => $item['icon'],
                    'target_type'   => $item['target_type'],
                    'target_value'  => $item['target_value'],
                    'source'        => $item['source'],
                    'source_ref'    => $item['source_ref'],
                    'is_active'     => (bool) $item['is_active'],
                    'is_hidden'     => (bool) $item['is_hidden'],
                    'is_manual'     => (bool) $item['is_manual'],
                    'is_collapsible'=> (bool) $item['is_collapsible'],
                    'sort_order'    => (int) $item['sort_order'],
                    'parent_id'     => $pid,
                    'category_id'   => (int) $item['category_id'],
                    'permission'    => $item['permission'],
                    'children'      => $this->nestItemsForApi($items, (int) $item['id']),
                ];
                $tree[] = $node;
            }
        }
        return $tree;
    }
}
