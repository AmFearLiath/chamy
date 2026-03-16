<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Editor\DefinitionRegistry;
use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

/**
 * Admin Controller – Handles all /admin/* routes.
 */
final class AdminController
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /* ───────────────────────────────────────────────
     *  Auth helpers
     * ─────────────────────────────────────────────── */

    private function requireAuth(): ?Response
    {
        $userId = $this->kernel->session()->get('user_id');
        if (!$userId) {
            return Response::redirect('/admin/login');
        }
        return null;
    }

    private function requirePermission(string $permission): ?Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $user = $this->currentUser();
        if (!$user || !$this->kernel->permissions()->userCan($user, $permission)) {
            return Response::html(
                $this->kernel->themes()->render('errors/403.twig', array_merge($this->baseData(), [
                    'message' => 'Sie haben keine Berechtigung für diese Aktion.',
                ])),
                403
            );
        }
        return null;
    }

    private function requireAnyPermission(array $permissions): ?Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/admin/login');
        }

        foreach ($permissions as $permission) {
            if ($this->kernel->permissions()->userCan($user, $permission)) {
                return null;
            }
        }

        return Response::html(
            $this->kernel->themes()->render('errors/403.twig', array_merge($this->baseData(), [
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ])),
            403
        );
    }

    private function currentUser(): ?array
    {
        $userId = $this->kernel->session()->get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->kernel->data()->getUserById($userId);
    }

    private function baseData(): array
    {
        $types = $this->kernel->contentTypes()->getAllTypes();
        $settings = $this->kernel->data()->getSettings();
        $sidebarIconMode = $this->resolveSidebarIconMode($settings);
        $adminIconCss = $this->resolveAdminIconCss();
        $adminFontCss = $this->resolveAdminFontCss();
        $sidebarIcons = $this->buildSidebarIconMap($sidebarIconMode);

        return [
            'user'          => $this->currentUser(),
            'content_types' => $types,
            'content_labels' => [
                'article' => 'Artikel',
                'page' => 'Seiten',
                'media_entry' => 'Medien',
                'documentation' => 'Dokumentationen',
            ],
            'app_locale'    => $this->kernel->config()->get('APP_LOCALE', 'de'),
            'app_version'   => '1.0.0',
            'php_version'   => PHP_VERSION,
            'current_theme' => 'Neon Dark',
            'flash_messages'=> $this->kernel->session()->getAllFlash(),
            'admin_icon_css' => $adminIconCss,
            'admin_font_css' => $adminFontCss,
            'sidebar_icon_mode' => $sidebarIconMode,
            'sidebar_icons' => $sidebarIcons,
        ];
    }

    private function resolveSidebarIconMode(array $settings): string
    {
        $groups = ['system', 'appearance', 'theme', 'general'];
        $keys = [
            'admin_sidebar_icons',
            'sidebar_icons',
            'sidebar_icon_set',
            'admin_icon_set',
            'admin_nav_icons',
            'nav_icon_set',
        ];

        foreach ($groups as $group) {
            foreach ($keys as $key) {
                $value = $this->findSettingValue($settings, $group, $key);
                if ($value === null || $value === '') {
                    continue;
                }
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['tabler', 'ti', 'tabler-icons', 'tabler_icons'], true)) {
                    return 'tabler';
                }
                if (in_array($normalized, ['unicode', 'classic', 'emoji', 'text'], true)) {
                    return 'classic';
                }
            }
        }

        foreach ($this->kernel->assetLibrary()->listIconSets() as $set) {
            if (!is_array($set)) {
                continue;
            }
            $areas = $set['areas'] ?? [];
            $inAdmin = is_array($areas) ? in_array('admin', $areas, true) : true;
            if (!$inAdmin) {
                continue;
            }
            $needle = strtolower((string) (($set['id'] ?? '') . ' ' . ($set['name'] ?? '')));
            if (str_contains($needle, 'tabler')) {
                return 'tabler';
            }
        }

        return 'classic';
    }

    private function findSettingValue(array $settings, string $group, string $key): ?string
    {
        $groupRows = $settings[$group] ?? null;
        if (!is_array($groupRows)) {
            return null;
        }
        foreach ($groupRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((string) ($row['key'] ?? '') !== $key) {
                continue;
            }
            return trim((string) ($row['value'] ?? ''));
        }
        return null;
    }

    private function resolveAdminIconCss(): array
    {
        $css = [];
        foreach ($this->kernel->assetLibrary()->listIconSets() as $set) {
            if (!is_array($set)) {
                continue;
            }
            if ((string) ($set['status'] ?? 'active') !== 'active') {
                continue;
            }
            $areas = $set['areas'] ?? [];
            if (is_array($areas) && $areas !== [] && !in_array('admin', $areas, true)) {
                continue;
            }
            $localCss = trim((string) ($set['local_css'] ?? ''));
            if ($localCss !== '') {
                $css[] = $localCss;
            }
        }

        return array_values(array_unique($css));
    }

    private function resolveAdminFontCss(): array
    {
        $css = [];
        foreach ($this->kernel->assetLibrary()->listFontSets() as $set) {
            if (!is_array($set)) {
                continue;
            }
            if ((string) ($set['status'] ?? 'active') !== 'active') {
                continue;
            }
            $areas = $set['areas'] ?? [];
            if (is_array($areas) && $areas !== [] && !in_array('admin', $areas, true)) {
                continue;
            }
            $localCss = trim((string) ($set['local_css'] ?? ''));
            if ($localCss !== '') {
                $css[] = $localCss;
            }
        }

        return array_values(array_unique($css));
    }

    private function buildSidebarIconMap(string $mode): array
    {
        if ($mode === 'tabler') {
            return [
                'dashboard' => 'ti ti-layout-dashboard',
                'content' => 'ti ti-file-text',
                'modules' => 'ti ti-box',
                'themes' => 'ti ti-palette',
                'users' => 'ti ti-users',
                'settings' => 'ti ti-settings',
                'trash' => 'ti ti-trash',
            ];
        }

        return [
            'dashboard' => '⊞',
            'content' => '◉',
            'modules' => '⧉',
            'themes' => '◈',
            'users' => '◎',
            'settings' => '⚙',
            'trash' => '🗑',
        ];
    }

    private function managementAccess(): array
    {
        $user = $this->currentUser();

        return [
            'can_manage_users' => $user !== null && $this->kernel->permissions()->userCan($user, 'users.manage'),
            'can_manage_roles' => $user !== null && $this->kernel->permissions()->userCan($user, 'roles.manage'),
            'can_manage_permissions' => $user !== null && $this->kernel->permissions()->userCan($user, 'permissions.manage'),
        ];
    }

    private function defaultManagementTab(array $flags): string
    {
        if ($flags['can_manage_users']) {
            return 'users';
        }

        if ($flags['can_manage_roles']) {
            return 'roles';
        }

        if ($flags['can_manage_permissions']) {
            return 'permissions';
        }

        return 'users';
    }

    private function defaultRoles(): array
    {
        return [
            ['id' => 1, 'key' => 'admin', 'name' => 'Administrator', 'description' => 'Volle Rechte'],
            ['id' => 2, 'key' => 'editor', 'name' => 'Redakteur', 'description' => 'Inhalte erstellen und bearbeiten'],
            ['id' => 3, 'key' => 'viewer', 'name' => 'Betrachter', 'description' => 'Inhalte ansehen'],
        ];
    }

    private function getRoleRecords(): array
    {
        $roles = $this->kernel->data()->getRoles();
        return $roles !== [] ? $roles : $this->defaultRoles();
    }

    private function getRoleKeys(): array
    {
        return array_values(array_map(
            static fn(array $role): string => (string) $role['key'],
            $this->getRoleRecords()
        ));
    }

    private function userEmailExists(string $email, ?int $excludeId = null): bool
    {
        foreach ($this->kernel->data()->getUsers() as $user) {
            if (($user['email'] ?? '') !== $email) {
                continue;
            }

            if ($excludeId !== null && (int) ($user['id'] ?? 0) === $excludeId) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function normalizePermissionSelection(mixed $value): array
    {
        // Accept either an array of keys or a comma-separated string
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (!is_array($value)) {
            return [];
        }

        $permissions = array_values(array_unique(array_filter(array_map(
            static fn(mixed $item): string => trim((string) $item),
            $value
        ))));

        $allowed = array_column($this->kernel->data()->getPermissions(), 'key');

        return array_values(array_filter(
            $permissions,
            static fn(string $permission): bool => in_array($permission, $allowed, true)
        ));
    }

    private function synchronizePermissionRoles(string $permissionKey, mixed $roleIds): void
    {
        if (is_string($roleIds)) {
            $roleIds = array_filter(array_map('trim', explode(',', $roleIds)));
        }

        $selectedRoleIds = is_array($roleIds)
            ? array_map('intval', $roleIds)
            : [];

        foreach ($this->getRoleRecords() as $role) {
            $roleId = (int) ($role['id'] ?? 0);
            if ($roleId <= 0) {
                continue;
            }

            $permissions = $this->kernel->data()->getRolePermissions($roleId);
            $hasPermission = in_array($permissionKey, $permissions, true);
            $shouldHavePermission = in_array($roleId, $selectedRoleIds, true);

            if ($shouldHavePermission && !$hasPermission) {
                $permissions[] = $permissionKey;
            }

            if (!$shouldHavePermission && $hasPermission) {
                $permissions = array_values(array_filter(
                    $permissions,
                    static fn(string $item): bool => $item !== $permissionKey
                ));
            }

            $this->kernel->data()->updateRolePermissions($roleId, $permissions);
        }
    }

    private function coreRoleKeys(): array
    {
        return ['admin', 'editor', 'viewer'];
    }

    private function corePermissionKeys(): array
    {
        return [
            'admin.access',
            'admin.dashboard',
            'content.list',
            'content.create',
            'content.edit',
            'content.delete',
            'content.publish',
            'system.mods',
            'system.themes',
            'users.manage',
            'roles.manage',
            'permissions.manage',
            'system.icons.manage',
            'system.fonts.manage',
            'system.manage',
        ];
    }

    private function canManageThemes(): bool
    {
        $user = $this->currentUser();
        return $user !== null && $this->kernel->permissions()->userCan($user, 'system.themes');
    }

    private function splitThemesByArea(array $themes): array
    {
        $admin = [];
        $frontend = [];

        foreach ($themes as $key => $theme) {
            $area = (string) ($theme['_area'] ?? '');
            if ($area === 'admin') {
                $admin[$key] = $theme;
            }
            if ($area === 'frontend') {
                $frontend[$key] = $theme;
            }
        }

        return ['admin' => $admin, 'frontend' => $frontend];
    }

    private function marketplaceThemeCatalog(): array
    {
        return [
            [
                'id' => 'aurora-admin-pro',
                'name' => 'Aurora Admin Pro',
                'description' => 'Kontraststarkes Admin-Theme mit Fokusmodus und Dashboard-Widgets.',
                'area' => 'admin',
                'pricing' => 'paid',
                'price' => 59,
                'currency' => 'EUR',
                'rating' => 4.9,
                'downloads' => 1840,
                'preview_color' => '#1f8ef1',
                'tags' => ['dashboard', 'analytics', 'dark'],
                'manager' => ['Theme-Optionen', 'Widget-Layern', 'Farbpaletten'],
            ],
            [
                'id' => 'linen-editorial',
                'name' => 'Linen Editorial',
                'description' => 'Editorial Frontend-Theme für Magazine und Storytelling.',
                'area' => 'frontend',
                'pricing' => 'paid',
                'price' => 89,
                'currency' => 'EUR',
                'rating' => 4.8,
                'downloads' => 970,
                'preview_color' => '#d9a441',
                'tags' => ['magazine', 'blog', 'serif'],
                'manager' => ['Layout-Presets', 'Hero-Steuerung', 'Typography'],
            ],
            [
                'id' => 'studio-grid-free',
                'name' => 'Studio Grid Free',
                'description' => 'Kostenloses Frontend-Theme mit klaren Rasterlayouts.',
                'area' => 'frontend',
                'pricing' => 'free',
                'price' => 0,
                'currency' => 'EUR',
                'rating' => 4.5,
                'downloads' => 6520,
                'preview_color' => '#14c9a5',
                'tags' => ['portfolio', 'landing', 'minimal'],
                'manager' => ['Header-Varianten', 'Kartenraster', 'CTA-Blöcke'],
            ],
            [
                'id' => 'command-center-free',
                'name' => 'Command Center Free',
                'description' => 'Kostenloses Admin-Theme für technische Teams und Monitoring.',
                'area' => 'admin',
                'pricing' => 'free',
                'price' => 0,
                'currency' => 'EUR',
                'rating' => 4.4,
                'downloads' => 4310,
                'preview_color' => '#9a6bff',
                'tags' => ['ops', 'system', 'metrics'],
                'manager' => ['Panel-Layouts', 'Benachrichtigungen', 'Statusfarben'],
            ],
            [
                'id' => 'commerce-flow-pro',
                'name' => 'Commerce Flow Pro',
                'description' => 'Frontend-Theme für Shops mit Story-Katalog und Conversion-Fokus.',
                'area' => 'frontend',
                'pricing' => 'paid',
                'price' => 129,
                'currency' => 'EUR',
                'rating' => 4.7,
                'downloads' => 610,
                'preview_color' => '#ff6a00',
                'tags' => ['shop', 'ecommerce', 'checkout'],
                'manager' => ['Produktkarten', 'Checkout-Flows', 'Merkliste'],
            ],
            [
                'id' => 'atelier-admin-lite',
                'name' => 'Atelier Admin Lite',
                'description' => 'Helles Admin-Theme mit reduzierter Darstellung für Content-Teams.',
                'area' => 'admin',
                'pricing' => 'paid',
                'price' => 39,
                'currency' => 'EUR',
                'rating' => 4.6,
                'downloads' => 1210,
                'preview_color' => '#ff3c81',
                'tags' => ['light', 'editorial', 'content'],
                'manager' => ['Theme-Einstellungen', 'Tabellen-Stile', 'Navigation'],
            ],
        ];
    }

    private function buildThemeMarketplace(array $query): array
    {
        $catalog = $this->marketplaceThemeCatalog();

        $area = strtolower(trim((string) ($query['area'] ?? 'all')));
        if (!in_array($area, ['all', 'admin', 'frontend'], true)) {
            $area = 'all';
        }

        $pricing = strtolower(trim((string) ($query['pricing'] ?? 'all')));
        if (!in_array($pricing, ['all', 'free', 'paid'], true)) {
            $pricing = 'all';
        }

        $search = mb_strtolower(trim((string) ($query['q'] ?? '')));

        $sort = strtolower(trim((string) ($query['sort'] ?? 'popular')));
        if (!in_array($sort, ['popular', 'rating', 'price_asc', 'price_desc', 'name'], true)) {
            $sort = 'popular';
        }

        $filtered = array_values(array_filter($catalog, static function (array $theme) use ($area, $pricing, $search): bool {
            if ($area !== 'all' && ($theme['area'] ?? '') !== $area) {
                return false;
            }
            if ($pricing !== 'all' && ($theme['pricing'] ?? '') !== $pricing) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $haystack = mb_strtolower(
                implode(' ', [
                    (string) ($theme['id'] ?? ''),
                    (string) ($theme['name'] ?? ''),
                    (string) ($theme['description'] ?? ''),
                    implode(' ', $theme['tags'] ?? []),
                ])
            );

            return str_contains($haystack, $search);
        }));

        usort($filtered, static function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'rating' => (($b['rating'] ?? 0) <=> ($a['rating'] ?? 0)),
                'price_asc' => (($a['price'] ?? 0) <=> ($b['price'] ?? 0)),
                'price_desc' => (($b['price'] ?? 0) <=> ($a['price'] ?? 0)),
                'name' => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')),
                default => (($b['downloads'] ?? 0) <=> ($a['downloads'] ?? 0)),
            };
        });

        return [
            'items' => $filtered,
            'filters' => [
                'area' => $area,
                'pricing' => $pricing,
                'q' => (string) ($query['q'] ?? ''),
                'sort' => $sort,
            ],
            'counts' => [
                'all' => count($catalog),
                'admin' => count(array_filter($catalog, static fn(array $t): bool => ($t['area'] ?? '') === 'admin')),
                'frontend' => count(array_filter($catalog, static fn(array $t): bool => ($t['area'] ?? '') === 'frontend')),
                'free' => count(array_filter($catalog, static fn(array $t): bool => ($t['pricing'] ?? '') === 'free')),
                'paid' => count(array_filter($catalog, static fn(array $t): bool => ($t['pricing'] ?? '') === 'paid')),
            ],
        ];
    }

    /* ───────────────────────────────────────────────
     *  Login
     * ─────────────────────────────────────────────── */

    public function loginForm(Request $request): Response
    {
        if ($this->kernel->session()->get('user_id')) {
            return Response::redirect('/admin');
        }

        return Response::html(
            $this->kernel->themes()->render('login.twig', [
                'app_locale' => $this->kernel->config()->get('APP_LOCALE', 'de'),
            ])
        );
    }

    public function loginSubmit(Request $request): Response
    {
        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            return Response::html(
                $this->kernel->themes()->render('login.twig', [
                    'error'      => 'Ungültige Anfrage. Bitte erneut versuchen.',
                    'app_locale' => $this->kernel->config()->get('APP_LOCALE', 'de'),
                ])
            );
        }

        $username = trim($request->getPost('username', ''));
        $password = $request->getPost('password', '');

        $user = $this->kernel->data()->getUserByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return Response::html(
                $this->kernel->themes()->render('login.twig', [
                    'error'      => $this->kernel->lang()->t('validation.login_failed'),
                    'username'   => $username,
                    'app_locale' => $this->kernel->config()->get('APP_LOCALE', 'de'),
                ])
            );
        }

        $this->kernel->session()->set('user_id', $user['id']);
        // store roles array and a primary role for compatibility
        $userRoles = [];
        if (isset($user['roles']) && is_array($user['roles'])) {
            $userRoles = $user['roles'];
        } elseif (!empty($user['role'])) {
            $userRoles = [$user['role']];
        }
        $this->kernel->session()->set('user_roles', $userRoles);
        $this->kernel->session()->set('user_role', $userRoles[0] ?? ($user['role'] ?? ''));
        $this->kernel->session()->regenerate();

        return Response::redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        $this->kernel->session()->destroy();
        return Response::redirect('/admin/login');
    }

    /* ───────────────────────────────────────────────
     *  Dashboard
     * ─────────────────────────────────────────────── */

    public function dashboard(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $data = $this->kernel->data();
        $stats = $data->getDashboardStats();

        $modulesDir = $this->kernel->path('modules');
        $modulesCount = is_dir($modulesDir) ? count(array_filter(scandir($modulesDir), fn ($d) => $d !== '.' && $d !== '..')) : 0;
        $stats['modules'] = $modulesCount;

        $recentEntries = $data->getRecentEntries(10);

        return Response::html(
            $this->kernel->themes()->render('dashboard.twig', array_merge($this->baseData(), [
                'current_route'  => 'dashboard',
                'stats'          => $stats,
                'recent_entries' => $recentEntries,
            ]))
        );
    }

    /* ───────────────────────────────────────────────
     *  Content – List
     * ─────────────────────────────────────────────── */

    public function contentList(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $typeKey = $request->getRouteParam('type');
        $type    = $this->kernel->contentTypes()->getType($typeKey);

        if (!$type) {
            return Response::notFound('Content type not found.');
        }

        $page    = max(1, (int) $request->getQuery('page', '1'));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $data = $this->kernel->data();
        $total   = $data->countContent($typeKey);
        $entries = $data->getContentEntries($typeKey, null, $perPage, $offset);

        return Response::html(
            $this->kernel->themes()->render('content/list.twig', array_merge($this->baseData(), [
                'current_route' => 'content.' . $typeKey,
                'type_key'      => $typeKey,
                'type'          => $type,
                'entries'       => $entries,
                'entries_total' => $total,
                'current_page'  => $page,
                'pages'         => max(1, (int) ceil($total / $perPage)),
            ]))
        );
    }

    /* ───────────────────────────────────────────────
     *  Content – Create Form
     * ─────────────────────────────────────────────── */

    public function contentCreate(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $typeKey = $request->getRouteParam('type');
        $type    = $this->kernel->contentTypes()->getType($typeKey);

        if (!$type) {
            return Response::notFound('Content type not found.');
        }

        return Response::html(
            $this->kernel->themes()->render('content/edit.twig', array_merge($this->baseData(), [
                'current_route' => 'content.' . $typeKey,
                'type_key'      => $typeKey,
                'type'          => $type,
                'entry'         => [],
                'entry_data'    => [],
                'versions'      => [],
            ]))
        );
    }

    /* ───────────────────────────────────────────────
     *  Content – Store (POST)
     * ─────────────────────────────────────────────── */

    public function contentStore(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin');
        }

        $typeKey = $request->getRouteParam('type');
        $type    = $this->kernel->contentTypes()->getType($typeKey);
        if (!$type) {
            return Response::notFound('Content type not found.');
        }

        $data = $request->getPost('data', []);
        if (!is_array($data)) {
            $data = [];
        }

        $userId = $this->kernel->session()->get('user_id');

        $entry = $this->kernel->data()->createContent($typeKey, $data, $userId);

        $this->kernel->session()->flash('success', $this->kernel->lang()->t('admin.content_created'));
        return Response::redirect('/admin/content/' . $typeKey . '/' . $entry['id'] . '/edit');
    }

    /* ───────────────────────────────────────────────
     *  Content – Edit Form
     * ─────────────────────────────────────────────── */

    public function contentEdit(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $typeKey = $request->getRouteParam('type');
        $id      = (int) $request->getRouteParam('id');
        $type    = $this->kernel->contentTypes()->getType($typeKey);

        if (!$type) {
            return Response::notFound('Content type not found.');
        }

        $entry = $this->kernel->data()->getContentById($id);
        if (!$entry || $entry['content_type'] !== $typeKey) {
            return Response::notFound('Entry not found.');
        }

        $entryData = $entry['_data'] ?? (is_array($entry['data']) ? $entry['data'] : json_decode($entry['data'] ?? '{}', true));
        $entry['state'] = $entry['status'];
        $versions  = $this->kernel->versions()->getVersions($id);

        return Response::html(
            $this->kernel->themes()->render('content/edit.twig', array_merge($this->baseData(), [
                'current_route' => 'content.' . $typeKey,
                'type_key'      => $typeKey,
                'type'          => $type,
                'entry'         => $entry,
                'entry_data'    => $entryData,
                'versions'      => $versions,
            ]))
        );
    }

    /* ───────────────────────────────────────────────
     *  Content – Visual Editor (GET)
     * ─────────────────────────────────────────────── */

    public function contentEditor(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $typeKey = $request->getRouteParam('type');
        $id      = (int) $request->getRouteParam('id');
        $type    = $this->kernel->contentTypes()->getType($typeKey);

        if (!$type) {
            return Response::notFound('Content type not found.');
        }

        $entry = $this->kernel->data()->getContentById($id);
        if (!$entry || $entry['content_type'] !== $typeKey) {
            return Response::notFound('Entry not found.');
        }

        $entryData = $entry['_data'] ?? (is_array($entry['data']) ? $entry['data'] : json_decode($entry['data'] ?? '{}', true));
        $entry['state'] = $entry['status'];

        return Response::html(
            $this->kernel->themes()->render('content/editor.twig', [
                'user'          => $this->currentUser(),
                'app_locale'    => $this->kernel->config()->get('APP_LOCALE', 'de'),
                'current_route' => 'content.' . $typeKey,
                'type_key'      => $typeKey,
                'type'          => $type,
                'entry'         => $entry,
                'entry_data'    => $entryData,
                'content_id'    => $id,
                'csrf_token'    => $this->kernel->session()->get('csrf_token', ''),
                'admin_icon_css' => $this->resolveAdminIconCss(),
                'admin_font_css' => $this->resolveAdminFontCss(),
            ])
        );
    }

    /* ───────────────────────────────────────────────
     *  Editor Manager (System Elements)
     * ─────────────────────────────────────────────── */

    private function editorStorageDir(): string
    {
        return rtrim($this->kernel->config()->get('STORAGE_PATH', 'storage'), '/\\') . '/editor';
    }

    private function editorCustomDefinitionsFile(): string
    {
        return $this->editorStorageDir() . '/custom-definitions.json';
    }

    private function readJsonFile(string $filePath, array $fallback = []): array
    {
        if (!file_exists($filePath)) {
            return $fallback;
        }

        $data = json_decode((string) file_get_contents($filePath), true);
        return is_array($data) ? $data : $fallback;
    }

    /**
     * Accept both supported custom definition formats:
     * 1) keyed map: {"components": {"hero": {...}}}
     * 2) list format: {"components": [{"id":"hero", ...}]}
     */
    private function normalizeEditorDefinitions(array $definitions): array
    {
        $normalized = [
            'layouts' => [],
            'blocks' => [],
            'components' => [],
            'snippets' => [],
        ];

        foreach (array_keys($normalized) as $group) {
            $items = $definitions[$group] ?? [];
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $key => $def) {
                if (!is_array($def)) {
                    continue;
                }

                $id = (string) ($def['id'] ?? $key);
                $id = strtolower(trim($id));
                $id = preg_replace('/[^a-z0-9_\-]/', '_', $id);
                if ($id === '') {
                    continue;
                }

                unset($def['id']);
                $normalized[$group][$id] = $def;
            }
        }

        return $normalized;
    }

    private function loadEditorCustomDefinitions(): array
    {
        $raw = $this->readJsonFile($this->editorCustomDefinitionsFile(), [
            'layouts' => [],
            'blocks' => [],
            'components' => [],
            'snippets' => [],
        ]);

        return $this->normalizeEditorDefinitions($raw);
    }

    private function writeJsonFile(string $filePath, array $data): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function editorManager(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $registry = new DefinitionRegistry($this->kernel);
        $definitions = $registry->getAll();

        $customDefinitions = $this->loadEditorCustomDefinitions();

        $packagesDir = $this->editorStorageDir() . '/packages';
        $packages = [];
        if (is_dir($packagesDir)) {
            foreach (glob($packagesDir . '/*.json') ?: [] as $file) {
                $pkg = $this->readJsonFile($file, []);
                $packages[] = [
                    'file' => basename($file),
                    'name' => (string) ($pkg['name'] ?? basename($file, '.json')),
                    'version' => (string) ($pkg['version'] ?? '1.0.0'),
                    'updated_at' => date('Y-m-d H:i:s', (int) filemtime($file)),
                    'counts' => [
                        'layouts' => count($pkg['definitions']['layouts'] ?? []),
                        'blocks' => count($pkg['definitions']['blocks'] ?? []),
                        'components' => count($pkg['definitions']['components'] ?? []),
                        'snippets' => count($pkg['definitions']['snippets'] ?? []),
                    ],
                ];
            }
        }

        return Response::html(
            $this->kernel->themes()->render('editor/manager.twig', array_merge($this->baseData(), [
                'current_route' => 'editor',
                'definitions' => $definitions,
                'custom_definitions' => $customDefinitions,
                'packages' => $packages,
            ]))
        );
    }

    public function editorCreateElement(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/editor');
        }

        $type = (string) $request->getPost('element_type', '');
        $id = strtolower(trim((string) $request->getPost('element_id', '')));
        $id = preg_replace('/[^a-z0-9_\-]/', '_', $id);
        $label = trim((string) $request->getPost('element_label', ''));
        $description = trim((string) $request->getPost('element_description', ''));
        $category = trim((string) $request->getPost('element_category', 'custom'));
        $icon = trim((string) $request->getPost('element_icon', 'custom'));

        $fieldsJson = trim((string) $request->getPost('element_fields_json', '[]'));
        $defaultPropsJson = trim((string) $request->getPost('element_default_props_json', '{}'));

        $groupMap = [
            'layout' => 'layouts',
            'block' => 'blocks',
            'component' => 'components',
            'snippet' => 'snippets',
        ];

        if (!isset($groupMap[$type]) || $id === '' || $label === '') {
            $this->kernel->session()->flash('danger', 'Typ, ID und Label sind erforderlich.');
            return Response::redirect('/admin/editor');
        }

        $fields = json_decode($fieldsJson, true);
        $defaultProps = json_decode($defaultPropsJson, true);
        if (!is_array($fields) || !is_array($defaultProps)) {
            $this->kernel->session()->flash('danger', 'Ungültiges JSON in Felder oder Default Props.');
            return Response::redirect('/admin/editor');
        }

        $file = $this->editorCustomDefinitionsFile();
        $custom = $this->loadEditorCustomDefinitions();

        $group = $groupMap[$type];
        $custom[$group][$id] = [
            'label' => $label,
            'description' => $description,
            'category' => $category,
            'icon' => $icon,
            'source' => 'user',
            'fields' => $fields,
            'defaultProps' => $defaultProps,
            'allowedChildren' => $type === 'layout' ? ['layout', 'block', 'component', 'snippet'] : [],
        ];

        $this->writeJsonFile($file, $custom);
        $this->kernel->session()->flash('success', 'Element gespeichert: ' . $label);
        return Response::redirect('/admin/editor');
    }

    public function editorImportPackage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/editor');
        }

        $raw = trim((string) $request->getPost('package_json', ''));
        if ($raw === '') {
            $this->kernel->session()->flash('danger', 'Kein Paket-JSON übergeben.');
            return Response::redirect('/admin/editor');
        }

        $pkg = json_decode($raw, true);
        if (!is_array($pkg)) {
            $this->kernel->session()->flash('danger', 'Paket-JSON ist ungültig.');
            return Response::redirect('/admin/editor');
        }

        $definitions = $pkg['definitions'] ?? $pkg;
        if (!is_array($definitions)) {
            $this->kernel->session()->flash('danger', 'Paket enthält keine Definitionsdaten.');
            return Response::redirect('/admin/editor');
        }

        $file = $this->editorCustomDefinitionsFile();
        $custom = $this->loadEditorCustomDefinitions();

        foreach (['layouts', 'blocks', 'components', 'snippets'] as $group) {
            $incoming = $definitions[$group] ?? [];
            if (!is_array($incoming)) {
                continue;
            }
            foreach ($incoming as $id => $def) {
                if (!is_array($def)) {
                    continue;
                }
                $def['source'] = 'user';
                $custom[$group][$id] = $def;
            }
        }

        $this->writeJsonFile($file, $custom);

        $packagesDir = $this->editorStorageDir() . '/packages';
        if (!is_dir($packagesDir)) {
            mkdir($packagesDir, 0755, true);
        }
        $packageName = preg_replace('/[^a-z0-9_\-]/', '_', strtolower((string) ($pkg['name'] ?? 'imported-package')));
        $packageFile = $packagesDir . '/' . $packageName . '-' . date('Ymd-His') . '.json';
        $this->writeJsonFile($packageFile, $pkg);

        $this->kernel->session()->flash('success', 'Paket importiert: ' . ($pkg['name'] ?? 'Unbenannt'));
        return Response::redirect('/admin/editor');
    }

    public function editorExportPackage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        $registry = new DefinitionRegistry($this->kernel);
        $payload = [
            'name' => 'chamy-editor-export',
            'version' => '1.0.0',
            'exported_at' => date('c'),
            'definitions' => $registry->getAll(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new Response((string) $json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="editor-package-' . date('Ymd-His') . '.json"',
        ]);
    }

    public function editorUpdateElement(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/editor');
        }

        $type = (string) $request->getPost('element_type', '');
        $id = strtolower(trim((string) $request->getPost('element_id', '')));
        $id = preg_replace('/[^a-z0-9_\-]/', '_', $id);
        $label = trim((string) $request->getPost('element_label', ''));
        $description = trim((string) $request->getPost('element_description', ''));
        $category = trim((string) $request->getPost('element_category', 'custom'));
        $icon = trim((string) $request->getPost('element_icon', 'custom'));

        $fieldsJson = trim((string) $request->getPost('element_fields_json', '[]'));
        $defaultPropsJson = trim((string) $request->getPost('element_default_props_json', '{}'));

        $groupMap = [
            'layout' => 'layouts',
            'block' => 'blocks',
            'component' => 'components',
            'snippet' => 'snippets',
        ];

        if (!isset($groupMap[$type]) || $id === '' || $label === '') {
            $this->kernel->session()->flash('danger', 'Typ, ID und Label sind erforderlich.');
            return Response::redirect('/admin/editor');
        }

        $fields = json_decode($fieldsJson, true);
        $defaultProps = json_decode($defaultPropsJson, true);
        if (!is_array($fields) || !is_array($defaultProps)) {
            $this->kernel->session()->flash('danger', 'Ungültiges JSON in Felder oder Default Props.');
            return Response::redirect('/admin/editor');
        }

        $file = $this->editorCustomDefinitionsFile();
        $custom = $this->loadEditorCustomDefinitions();

        $group = $groupMap[$type];
        if (!isset($custom[$group][$id])) {
            $this->kernel->session()->flash('danger', 'Element nicht gefunden: ' . $id);
            return Response::redirect('/admin/editor');
        }

        $custom[$group][$id] = array_merge($custom[$group][$id], [
            'label' => $label,
            'description' => $description,
            'category' => $category,
            'icon' => $icon,
            'fields' => $fields,
            'defaultProps' => $defaultProps,
        ]);

        $this->writeJsonFile($file, $custom);
        $this->kernel->session()->flash('success', 'Element aktualisiert: ' . $label);
        return Response::redirect('/admin/editor');
    }

    public function editorDeleteElement(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/editor');
        }

        $type = (string) $request->getPost('element_type', '');
        $id = strtolower(trim((string) $request->getPost('element_id', '')));
        $id = preg_replace('/[^a-z0-9_\-]/', '_', $id);

        $groupMap = [
            'layout' => 'layouts',
            'block' => 'blocks',
            'component' => 'components',
            'snippet' => 'snippets',
        ];

        if (!isset($groupMap[$type]) || $id === '') {
            $this->kernel->session()->flash('danger', 'Typ und ID sind erforderlich.');
            return Response::redirect('/admin/editor');
        }

        $file = $this->editorCustomDefinitionsFile();
        $custom = $this->loadEditorCustomDefinitions();

        $group = $groupMap[$type];
        if (isset($custom[$group][$id])) {
            unset($custom[$group][$id]);
            $this->writeJsonFile($file, $custom);
            $this->kernel->session()->flash('success', 'Element gelöscht: ' . $id);
        } else {
            $this->kernel->session()->flash('danger', 'Element nicht gefunden: ' . $id);
        }

        return Response::redirect('/admin/editor');
    }

    /* ───────────────────────────────────────────────
     *  Content – Update (POST)
     * ─────────────────────────────────────────────── */

    public function contentUpdate(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin');
        }

        $typeKey = $request->getRouteParam('type');
        $id      = (int) $request->getRouteParam('id');

        $entry = $this->kernel->data()->getContentById($id);
        if (!$entry || $entry['content_type'] !== $typeKey) {
            return Response::notFound('Entry not found.');
        }

        $data = $request->getPost('data', []);
        if (!is_array($data)) {
            $data = [];
        }

        $status = $request->getPost('state', $entry['status']);
        $userId = $this->kernel->session()->get('user_id');

        $this->kernel->data()->updateContent($id, $data, $userId);

        if ($status !== $entry['status']) {
            // Status-Änderung: Live-Modus nutzt DB direkt, Mock-Modus ignoriert
            $source = $this->kernel->config()->get('DATA_SOURCE', 'mock');
            if ($source === 'live') {
                $db     = $this->kernel->db();
                $updateData = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
                if ($status === 'published' && empty($entry['published_at'])) {
                    $updateData['published_at'] = date('Y-m-d H:i:s');
                }
                $db->update('content_entries', $updateData, 'id = :id', ['id' => $id]);
            }
        }

        $this->kernel->session()->flash('success', $this->kernel->lang()->t('admin.content_saved'));
        return Response::redirect('/admin/content/' . $typeKey . '/' . $id . '/edit');
    }

    /* ───────────────────────────────────────────────
     *  Content – Delete (POST)
     * ─────────────────────────────────────────────── */

    public function contentDelete(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) {
            return $redirect;
        }

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin');
        }

        $typeKey = $request->getRouteParam('type');
        $id      = (int) $request->getRouteParam('id');

        $entry = $this->kernel->data()->getContentById($id);
        if ($entry !== null) {
            $name = (string) (($entry['_data']['title'] ?? $entry['_data']['slug'] ?? $typeKey . '#' . $id));
            $this->kernel->trash()->add(
                'content',
                'content_entry',
                $typeKey . ':' . $id,
                [
                    'type' => $typeKey,
                    'name' => $name,
                    'entry' => $entry,
                ],
                (int) $this->kernel->session()->get('user_id')
            );
        }

        $this->kernel->data()->deleteContent($id);

        $this->kernel->session()->flash('success', $this->kernel->lang()->t('admin.content_deleted'));
        return Response::redirect('/admin/content/' . $typeKey);
    }

    /* ───────────────────────────────────────────────
     *  Users – List
     * ─────────────────────────────────────────────── */

    public function usersList(Request $request): Response
    {
        $redirect = $this->requireAnyPermission(['users.manage', 'roles.manage', 'permissions.manage']);
        if ($redirect) return $redirect;

        $data = $this->kernel->data();
        $flags = $this->managementAccess();
        $users = $data->getUsers();
        $roles = $this->getRoleRecords();
        $permissions = $data->getPermissions();

        foreach ($roles as &$role) {
            $role['users_count'] = $data->countUsersByRole((string) $role['key']);
            $role['permission_keys'] = $data->getRolePermissions((int) $role['id']);
        }
        unset($role);

        $permissionUsage = [];
        foreach ($roles as $role) {
            foreach ($role['permission_keys'] as $permissionKey) {
                $permissionUsage[$permissionKey] = ($permissionUsage[$permissionKey] ?? 0) + 1;
            }
        }

        foreach ($permissions as &$permission) {
            $permission['roles_count'] = $permissionUsage[$permission['key']] ?? 0;
        }
        unset($permission);

        return Response::html(
            $this->kernel->themes()->render('users/manage.twig', array_merge($this->baseData(), [
                'current_route' => 'users',
                'users'         => $users,
                'roles'         => $roles,
                'permissions'   => $permissions,
                'default_management_tab' => $this->defaultManagementTab($flags),
                'management_access' => $flags,
            ]))
        );
    }

    /* ───────────────────────────────────────────────
     *  Users – Create Form
     * ─────────────────────────────────────────────── */

    public function userCreate(Request $request): Response
    {
        $redirect = $this->requirePermission('users.manage');
        if ($redirect) return $redirect;

        return Response::html(
            $this->kernel->themes()->render('users/edit.twig', array_merge($this->baseData(), [
                'current_route' => 'users',
                'edit_user'     => [],
                'roles'         => $this->getRoleKeys(),
            ]))
        );
    }

    /* ───────────────────────────────────────────────
     *  Users – Store (POST)
     * ─────────────────────────────────────────────── */

    public function userStore(Request $request): Response
    {
        $redirect = $this->requirePermission('users.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $username    = trim($request->getPost('username', ''));
        $email       = trim($request->getPost('email', ''));
        $password    = $request->getPost('password', '');
        $displayName = trim($request->getPost('display_name', ''));
        // roles can be submitted as comma-separated string or array
        $rolesInput  = $request->getPost('roles', $request->getPost('role', 'editor'));
        $roles = is_array($rolesInput) ? $rolesInput : array_filter(array_map('trim', explode(',', (string)$rolesInput)));

        if ($username === '' || $email === '' || $password === '') {
            $this->kernel->session()->flash('danger', 'Benutzername, E-Mail und Passwort sind Pflichtfelder.');
            return Response::redirect('/admin/users/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->kernel->session()->flash('danger', 'Bitte eine gültige E-Mail-Adresse eingeben.');
            return Response::redirect('/admin/users/create');
        }

        $roleKeys = $this->getRoleKeys();
        $roles = array_values(array_filter(array_map(function($r) use ($roleKeys) {
            return in_array($r, $roleKeys, true) ? $r : null;
        }, $roles)));
        if (empty($roles)) {
            $roles = [$roleKeys[0] ?? 'editor'];
        }

        // Prüfen ob Username/E-Mail existiert
        $existing = $this->kernel->data()->getUserByUsername($username);
        if ($existing) {
            $this->kernel->session()->flash('danger', 'Benutzername oder E-Mail existiert bereits.');
            return Response::redirect('/admin/users/create');
        }

        if ($this->userEmailExists($email)) {
            $this->kernel->session()->flash('danger', 'Benutzername oder E-Mail existiert bereits.');
            return Response::redirect('/admin/users/create');
        }

        $this->kernel->data()->createUser([
            'username'      => $username,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'display_name'  => $displayName ?: $username,
            'roles'         => $roles,
            'role'          => $roles[0] ?? ($roleKeys[0] ?? 'editor'),
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->kernel->session()->flash('success', 'Benutzer wurde erstellt.');
        return Response::redirect('/admin/users');
    }

    /* ───────────────────────────────────────────────
     *  Users – Edit Form
     * ─────────────────────────────────────────────── */

    public function userEdit(Request $request): Response
    {
        $redirect = $this->requirePermission('users.manage');
        if ($redirect) return $redirect;

        $id = (int) $request->getRouteParam('id');

        $editUser = $this->kernel->data()->getUserById($id);
        if (!$editUser) {
            return Response::notFound('Benutzer nicht gefunden.');
        }

        return Response::html(
            $this->kernel->themes()->render('users/edit.twig', array_merge($this->baseData(), [
                'current_route' => 'users',
                'edit_user'     => $editUser,
                'roles'         => $this->getRoleKeys(),
            ]))
        );
    }

    /* ───────────────────────────────────────────────
     *  Profile
     * ─────────────────────────────────────────────── */

    public function profilePage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $user = $this->currentUser();
        if (!$user) return Response::redirect('/admin/login');

        return Response::html(
            $this->kernel->themes()->render('profile.twig', array_merge($this->baseData(), [
                'current_route' => 'profile',
                'profile' => $user,
            ]))
        );
    }

    public function profileUpdate(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/profile');
        }

        $userId = (int)$this->kernel->session()->get('user_id');
        $email = trim($request->getPost('email', ''));
        $displayName = trim($request->getPost('display_name', ''));
        $password = $request->getPost('password', '');

        $update = [
            'email' => $email,
            'display_name' => $displayName,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($password !== '') {
            $update['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->kernel->data()->updateUser($userId, $update);

        $this->kernel->session()->flash('success', 'Profil gespeichert.');
        return Response::redirect('/admin/profile');
    }

    /* ───────────────────────────────────────────────
     *  Users – Update (POST)
     * ─────────────────────────────────────────────── */

    public function userUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('users.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $id          = (int) $request->getRouteParam('id');
        $email       = trim($request->getPost('email', ''));
        $displayName = trim($request->getPost('display_name', ''));
        $rolesInput  = $request->getPost('roles', $request->getPost('role', 'editor'));
        $roles = is_array($rolesInput) ? $rolesInput : array_filter(array_map('trim', explode(',', (string)$rolesInput)));
        $isActive    = (int) $request->getPost('is_active', '1');
        $password    = $request->getPost('password', '');

        $editUser = $this->kernel->data()->getUserById($id);
        if ($editUser === null) {
            return Response::notFound('Benutzer nicht gefunden.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->kernel->session()->flash('danger', 'Bitte eine gültige E-Mail-Adresse eingeben.');
            return Response::redirect('/admin/users/' . $id . '/edit');
        }

        $roleKeys = $this->getRoleKeys();
        $roles = array_values(array_filter(array_map(function($r) use ($roleKeys) {
            return in_array($r, $roleKeys, true) ? $r : null;
        }, $roles)));
        if (empty($roles)) {
            $roles = [$editUser['role'] ?? ($roleKeys[0] ?? 'editor')];
        }

        $currentUserId = (int) $this->kernel->session()->get('user_id');
        if ($id === $currentUserId) {
            // Prevent self-deactivation
            if ((int)$isActive !== 1) {
                $this->kernel->session()->flash('danger', 'Der eigene Account kann nicht deaktiviert oder auf eine andere Rolle umgestellt werden.');
                return Response::redirect('/admin/users/' . $id . '/edit');
            }
            // Prevent removing admin role from own account if this would remove the last admin
            $wasAdmin = in_array('admin', (array)($editUser['roles'] ?? [$editUser['role'] ?? '']), true);
            $willBeAdmin = in_array('admin', $roles, true);
            if ($wasAdmin && !$willBeAdmin && $this->kernel->data()->countUsersByRole('admin') <= 1) {
                $this->kernel->session()->flash('danger', 'Der eigene Account kann nicht auf eine andere Rolle umgestellt werden, da sonst kein Administrator übrig bliebe.');
                return Response::redirect('/admin/users/' . $id . '/edit');
            }
        }

        if ($this->userEmailExists($email, $id)) {
            $this->kernel->session()->flash('danger', 'Benutzername oder E-Mail existiert bereits.');
            return Response::redirect('/admin/users/' . $id . '/edit');
        }

        $updateData = [
            'email'        => $email,
            'display_name' => $displayName,
            'roles'        => $roles,
            'role'         => $roles[0] ?? ($editUser['role'] ?? ''),
            'is_active'    => $isActive,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        if ($password !== '') {
            $updateData['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->kernel->data()->updateUser($id, $updateData);

        $this->kernel->session()->flash('success', 'Benutzer wurde aktualisiert.');
        return Response::redirect('/admin/users/' . $id . '/edit');
    }

    /* ───────────────────────────────────────────────
     *  Users – Delete (POST)
     * ─────────────────────────────────────────────── */

    public function userDelete(Request $request): Response
    {
        $redirect = $this->requirePermission('users.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $id = (int) $request->getRouteParam('id');

        // Eigenen Account nicht löschen
        if ($id === (int) $this->kernel->session()->get('user_id')) {
            $this->kernel->session()->flash('danger', 'Sie können Ihren eigenen Account nicht löschen.');
            return Response::redirect('/admin/users');
        }

        $user = $this->kernel->data()->getUserById($id);
        $userRoles = (array)($user['roles'] ?? [$user['role'] ?? '']);
        if ($user !== null && in_array('admin', $userRoles, true) && $this->kernel->data()->countUsersByRole('admin') <= 1) {
            $this->kernel->session()->flash('danger', 'Der letzte Administrator kann nicht gelöscht werden.');
            return Response::redirect('/admin/users');
        }

        if ($user !== null) {
            $this->kernel->trash()->add(
                'users',
                'user',
                (string) ($user['username'] ?? $id),
                [
                    'name' => (string) ($user['display_name'] ?? $user['username'] ?? ''),
                    'user' => $user,
                ],
                (int) $this->kernel->session()->get('user_id')
            );
        }

        $this->kernel->data()->deleteUser($id);

        $this->kernel->session()->flash('success', 'Benutzer wurde gelöscht.');
        return Response::redirect('/admin/users');
    }

    public function userToggleStatus(Request $request): Response
    {
        $redirect = $this->requirePermission('users.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $id = (int) $request->getRouteParam('id');
        $actorId = (int) $this->kernel->session()->get('user_id');

        // Prevent self-deactivation
        if ($id === $actorId) {
            $this->kernel->session()->flash('danger', 'Sie können Ihren eigenen Account nicht deaktivieren.');
            return Response::redirect('/admin/users');
        }

        $user = $this->kernel->data()->getUserById($id);
        if (!$user) {
            return Response::notFound('Benutzer nicht gefunden.');
        }

        // Prevent deactivating last admin
        if (($user['role'] ?? '') === 'admin' && $this->kernel->data()->countUsersByRole('admin') <= 1) {
            $this->kernel->session()->flash('danger', 'Der letzte Administrator kann nicht deaktiviert werden.');
            return Response::redirect('/admin/users');
        }

        $newActive = (int) (empty($user['is_active']) ? 1 : 0);
        $this->kernel->data()->updateUser($id, ['is_active' => $newActive, 'updated_at' => date('Y-m-d H:i:s')]);

        $this->kernel->session()->flash('success', 'Benutzerstatus aktualisiert.');
        return Response::redirect('/admin/users');
    }

    /* ───────────────────────────────────────────────
     *  Roles – Create / Edit / Delete
     * ─────────────────────────────────────────────── */

    public function roleCreate(Request $request): Response
    {
        $redirect = $this->requirePermission('roles.manage');
        if ($redirect) return $redirect;

        return Response::html(
            $this->kernel->themes()->render('users/role_edit.twig', array_merge($this->baseData(), [
                'current_route' => 'users',
                'edit_role' => [],
                'permissions' => $this->kernel->data()->getPermissions(),
                'selected_permissions' => [],
            ]))
        );
    }

    public function roleStore(Request $request): Response
    {
        $redirect = $this->requirePermission('roles.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $key = strtolower(trim($request->getPost('key', '')));
        $name = trim($request->getPost('name', ''));
        $description = trim($request->getPost('description', ''));

        if ($key === '' || $name === '') {
            $this->kernel->session()->flash('danger', 'Key und Name sind Pflichtfelder.');
            return Response::redirect('/admin/roles/create');
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $key)) {
            $this->kernel->session()->flash('danger', 'Rollen-Keys dürfen nur Kleinbuchstaben, Zahlen, Bindestriche und Unterstriche enthalten.');
            return Response::redirect('/admin/roles/create');
        }

        if ($this->kernel->data()->roleExistsByKey($key)) {
            $this->kernel->session()->flash('danger', 'Dieser Rollen-Key existiert bereits.');
            return Response::redirect('/admin/roles/create');
        }

        $roleId = $this->kernel->data()->createRole([
            'uuid' => bin2hex(random_bytes(16)),
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->kernel->data()->updateRolePermissions(
            $roleId,
            $this->normalizePermissionSelection($request->getPost('permission_keys', []))
        );

        $this->kernel->session()->flash('success', 'Rolle wurde erstellt.');
        return Response::redirect('/admin/roles/' . $roleId . '/edit');
    }

    public function roleEdit(Request $request): Response
    {
        $redirect = $this->requirePermission('roles.manage');
        if ($redirect) return $redirect;

        $id = (int) $request->getRouteParam('id');
        $role = $this->kernel->data()->getRoleById($id);
        if ($role === null) {
            return Response::notFound('Rolle nicht gefunden.');
        }

        return Response::html(
            $this->kernel->themes()->render('users/role_edit.twig', array_merge($this->baseData(), [
                'current_route' => 'users',
                'edit_role' => $role,
                'permissions' => $this->kernel->data()->getPermissions(),
                'selected_permissions' => $this->kernel->data()->getRolePermissions($id),
            ]))
        );
    }

    public function roleUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('roles.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $id = (int) $request->getRouteParam('id');
        $role = $this->kernel->data()->getRoleById($id);
        if ($role === null) {
            return Response::notFound('Rolle nicht gefunden.');
        }

        $name = trim($request->getPost('name', ''));
        $description = trim($request->getPost('description', ''));

        if ($name === '') {
            $this->kernel->session()->flash('danger', 'Der Rollenname ist erforderlich.');
            return Response::redirect('/admin/roles/' . $id . '/edit');
        }

        $this->kernel->data()->updateRole($id, [
            'name' => $name,
            'description' => $description,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->kernel->data()->updateRolePermissions(
            $id,
            $this->normalizePermissionSelection($request->getPost('permission_keys', []))
        );

        $this->kernel->session()->flash('success', 'Rolle wurde aktualisiert.');
        return Response::redirect('/admin/roles/' . $id . '/edit');
    }

    public function roleDelete(Request $request): Response
    {
        $redirect = $this->requirePermission('roles.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $id = (int) $request->getRouteParam('id');
        $role = $this->kernel->data()->getRoleById($id);
        if ($role === null) {
            return Response::redirect('/admin/users');
        }

        if (in_array((string) $role['key'], $this->coreRoleKeys(), true)) {
            $this->kernel->session()->flash('danger', 'Systemrollen können nicht gelöscht werden.');
            return Response::redirect('/admin/users');
        }

        if ($this->kernel->data()->countUsersByRole((string) $role['key']) > 0) {
            $this->kernel->session()->flash('danger', 'Diese Rolle ist noch Benutzern zugewiesen und kann nicht gelöscht werden.');
            return Response::redirect('/admin/users');
        }

        $this->kernel->trash()->add(
            'access',
            'role',
            (string) ($role['key'] ?? $id),
            [
                'name' => (string) ($role['name'] ?? ''),
                'role' => $role,
                'permission_keys' => $this->kernel->data()->getRolePermissions($id),
            ],
            (int) $this->kernel->session()->get('user_id')
        );

        $this->kernel->data()->deleteRole($id);

        $this->kernel->session()->flash('success', 'Rolle wurde gelöscht.');
        return Response::redirect('/admin/users');
    }

    /* ───────────────────────────────────────────────
     *  Permissions – Create / Edit / Delete
     * ─────────────────────────────────────────────── */

    public function permissionCreate(Request $request): Response
    {
        $redirect = $this->requirePermission('permissions.manage');
        if ($redirect) return $redirect;

        return Response::html(
            $this->kernel->themes()->render('users/permission_edit.twig', array_merge($this->baseData(), [
                'current_route' => 'users',
                'edit_permission' => [],
                'roles' => $this->getRoleRecords(),
                'selected_role_ids' => [],
            ]))
        );
    }

    public function permissionStore(Request $request): Response
    {
        $redirect = $this->requirePermission('permissions.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $key = strtolower(trim($request->getPost('key', '')));
        $description = trim($request->getPost('description', ''));
        $group = strtolower(trim($request->getPost('group', 'system')));

        if ($key === '' || $description === '') {
            $this->kernel->session()->flash('danger', 'Key und Beschreibung sind Pflichtfelder.');
            return Response::redirect('/admin/permissions/create');
        }

        if (!preg_match('/^[a-z0-9._-]+$/', $key)) {
            $this->kernel->session()->flash('danger', 'Permission-Keys dürfen nur Kleinbuchstaben, Zahlen, Punkte, Bindestriche und Unterstriche enthalten.');
            return Response::redirect('/admin/permissions/create');
        }

        if ($this->kernel->data()->permissionExistsByKey($key)) {
            $this->kernel->session()->flash('danger', 'Dieser Permission-Key existiert bereits.');
            return Response::redirect('/admin/permissions/create');
        }

        $permissionId = $this->kernel->data()->createPermission([
            'key' => $key,
            'description' => $description,
            'group' => $group !== '' ? $group : 'system',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->synchronizePermissionRoles($key, $request->getPost('role_ids', []));

        $this->kernel->session()->flash('success', 'Berechtigung wurde erstellt.');
        return Response::redirect('/admin/permissions/' . $permissionId . '/edit');
    }

    public function permissionEdit(Request $request): Response
    {
        $redirect = $this->requirePermission('permissions.manage');
        if ($redirect) return $redirect;

        $id = (int) $request->getRouteParam('id');
        $permission = $this->kernel->data()->getPermissionById($id);
        if ($permission === null) {
            return Response::notFound('Berechtigung nicht gefunden.');
        }

        $selectedRoleIds = [];
        foreach ($this->getRoleRecords() as $role) {
            if (in_array($permission['key'], $this->kernel->data()->getRolePermissions((int) $role['id']), true)) {
                $selectedRoleIds[] = (int) $role['id'];
            }
        }

        return Response::html(
            $this->kernel->themes()->render('users/permission_edit.twig', array_merge($this->baseData(), [
                'current_route' => 'users',
                'edit_permission' => $permission,
                'roles' => $this->getRoleRecords(),
                'selected_role_ids' => $selectedRoleIds,
            ]))
        );
    }

    public function permissionUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('permissions.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $id = (int) $request->getRouteParam('id');
        $permission = $this->kernel->data()->getPermissionById($id);
        if ($permission === null) {
            return Response::notFound('Berechtigung nicht gefunden.');
        }

        $description = trim($request->getPost('description', ''));
        $group = strtolower(trim($request->getPost('group', 'system')));

        if ($description === '') {
            $this->kernel->session()->flash('danger', 'Die Beschreibung ist erforderlich.');
            return Response::redirect('/admin/permissions/' . $id . '/edit');
        }

        $this->kernel->data()->updatePermission($id, [
            'description' => $description,
            'group' => $group !== '' ? $group : 'system',
        ]);

        $this->synchronizePermissionRoles((string) $permission['key'], $request->getPost('role_ids', []));

        $this->kernel->session()->flash('success', 'Berechtigung wurde aktualisiert.');
        return Response::redirect('/admin/permissions/' . $id . '/edit');
    }

    public function permissionDelete(Request $request): Response
    {
        $redirect = $this->requirePermission('permissions.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/users');
        }

        $id = (int) $request->getRouteParam('id');
        $permission = $this->kernel->data()->getPermissionById($id);
        if ($permission === null) {
            return Response::redirect('/admin/users');
        }

        if (in_array((string) $permission['key'], $this->corePermissionKeys(), true)) {
            $this->kernel->session()->flash('danger', 'System-Berechtigungen können nicht gelöscht werden.');
            return Response::redirect('/admin/users');
        }

        $roleIds = [];
        foreach ($this->getRoleRecords() as $role) {
            if (in_array((string) $permission['key'], $this->kernel->data()->getRolePermissions((int) $role['id']), true)) {
                $roleIds[] = (int) $role['id'];
            }
        }
        $this->kernel->trash()->add(
            'access',
            'permission',
            (string) ($permission['key'] ?? $id),
            [
                'name' => (string) ($permission['description'] ?? $permission['key'] ?? ''),
                'permission' => $permission,
                'role_ids' => $roleIds,
            ],
            (int) $this->kernel->session()->get('user_id')
        );

        $this->kernel->data()->deletePermission($id);

        $this->kernel->session()->flash('success', 'Berechtigung wurde gelöscht.');
        return Response::redirect('/admin/users');
    }

    /* ───────────────────────────────────────────────
     *  Settings
     * ─────────────────────────────────────────────── */

    private function canManageIconLibraries(?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->kernel->permissions()->userCan($user, 'system.manage')
            || $this->kernel->permissions()->userCan($user, 'system.icons.manage');
    }

    private function canManageFontLibraries(?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->kernel->permissions()->userCan($user, 'system.manage')
            || $this->kernel->permissions()->userCan($user, 'system.fonts.manage');
    }

    /** @return array<int, string> */
    private function parseMultiSelect(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $items = array_values(array_unique(array_filter(array_map(
            static fn(mixed $item): string => trim((string) $item),
            $value
        ))));

        sort($items);
        return $items;
    }

    /** @return array<int, string> */
    private function parseLineList(mixed $value): array
    {
        if (!is_string($value)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $items = array_values(array_unique(array_filter(array_map(
            static fn(string $line): string => trim($line),
            $lines
        ))));

        return $items;
    }

    /**
     * Auto-add missing i18n keys for settings icon/font managers.
     */
    private function ensureSettingsAssetTranslations(): void
    {
        $keys = [
            'admin' => [
                'settings_tab_icons' => ['de' => 'Icons', 'en' => 'Icons'],
                'settings_tab_fonts' => ['de' => 'Schriftarten', 'en' => 'Fonts'],
                'settings_icons_title' => ['de' => 'Icon-Manager', 'en' => 'Icon manager'],
                'settings_icons_desc' => ['de' => 'Icon-Fonts analysieren, lokal installieren und als Sets verwalten.', 'en' => 'Analyze icon fonts, install them locally, and manage them as sets.'],
                'settings_fonts_title' => ['de' => 'Font-Manager', 'en' => 'Font manager'],
                'settings_fonts_desc' => ['de' => 'Fonts installieren, lokal verwalten und für Bereiche freigeben.', 'en' => 'Install fonts, manage them locally, and assign area availability.'],
                'settings_assets_known_sources' => ['de' => 'Bekannte Quellen', 'en' => 'Known sources'],
                'settings_assets_source_url' => ['de' => 'Quell-URL', 'en' => 'Source URL'],
                'settings_assets_analyze' => ['de' => 'Analysieren', 'en' => 'Analyze'],
                'settings_assets_install' => ['de' => 'Installieren', 'en' => 'Install'],
                'settings_assets_name' => ['de' => 'Name', 'en' => 'Name'],
                'settings_assets_id' => ['de' => 'ID', 'en' => 'ID'],
                'settings_assets_areas' => ['de' => 'Bereiche', 'en' => 'Areas'],
                'settings_assets_scope' => ['de' => 'Freigabe für', 'en' => 'Allowed for'],
                'settings_assets_status' => ['de' => 'Status', 'en' => 'Status'],
                'settings_assets_actions' => ['de' => 'Aktionen', 'en' => 'Actions'],
                'settings_assets_export' => ['de' => 'Export', 'en' => 'Export'],
                'settings_assets_import' => ['de' => 'Import', 'en' => 'Import'],
                'settings_assets_delete' => ['de' => 'Löschen', 'en' => 'Delete'],
                'settings_assets_google_search' => ['de' => 'Google Fonts Suche', 'en' => 'Google Fonts search'],
                'settings_assets_styles' => ['de' => 'Stile', 'en' => 'Styles'],
                'settings_assets_provider' => ['de' => 'Anbieter', 'en' => 'Provider'],
                'settings_assets_install_google' => ['de' => 'Google Font installieren', 'en' => 'Install Google font'],
                'settings_assets_import_json' => ['de' => 'Import-JSON', 'en' => 'Import JSON'],
                'settings_assets_export_json' => ['de' => 'Export-JSON', 'en' => 'Export JSON'],
                'settings_assets_readonly' => ['de' => 'Nur Leserechte für diesen Manager.', 'en' => 'Read-only access for this manager.'],
                'settings_assets_no_sets' => ['de' => 'Keine Sets vorhanden.', 'en' => 'No sets available.'],
            ],
        ];

        $de = [];
        $en = [];
        foreach ($keys as $group => $items) {
            foreach ($items as $key => $values) {
                $de[$group][$key] = $values['de'];
                $en[$group][$key] = $values['en'];
            }
        }

        $this->ensureLocaleKeys('de', $de);
        $this->ensureLocaleKeys('en', $en);
    }

    private function ensureLocaleKeys(string $locale, array $keys): void
    {
        $file = $this->kernel->path('languages', $locale . '.php');
        if (!is_file($file)) {
            return;
        }

        $data = include $file;
        if (!is_array($data)) {
            return;
        }

        $changed = false;
        $merge = function (array $target, array $incoming) use (&$merge, &$changed): array {
            foreach ($incoming as $key => $value) {
                if (is_array($value)) {
                    $targetValue = $target[$key] ?? [];
                    if (!is_array($targetValue)) {
                        $targetValue = [];
                    }
                    $target[$key] = $merge($targetValue, $value);
                    continue;
                }
                if (!array_key_exists($key, $target)) {
                    $target[$key] = $value;
                    $changed = true;
                }
            }
            return $target;
        };

        $newData = $merge($data, $keys);
        if (!$changed) {
            return;
        }

        @file_put_contents(
            $file,
            "<?php\n\nreturn " . var_export($newData, true) . ";\n",
            LOCK_EX
        );
    }

    public function settingsPage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $this->ensureSettingsAssetTranslations();

        $settings = $this->kernel->data()->getSettings();
        unset($settings['theme']);

        // Priority order for horizontal setting tabs (highest business relevance first)
        $priority = ['system', 'site', 'security', 'content', 'email', 'seo', 'cache', 'advanced'];
        $ordered = [];
        foreach ($priority as $group) {
            if (isset($settings[$group])) {
                $ordered[$group] = $settings[$group];
            }
        }
        foreach ($settings as $group => $values) {
            if (!isset($ordered[$group])) {
                $ordered[$group] = $values;
            }
        }

        $user = $this->currentUser();
        $canManageSettings = $user !== null && $this->kernel->permissions()->userCan($user, 'system.manage');
        $canManageIcons = $this->canManageIconLibraries($user);
        $canManageFonts = $this->canManageFontLibraries($user);

        $activeTab = (string) $request->getQuery('tab', '');
        if (!in_array($activeTab, ['icons', 'fonts'], true)) {
            $activeTab = '';
        }

        $assetLibrary = $this->kernel->assetLibrary();
        $iconSets = $assetLibrary->listIconSets();
        $fontSets = $assetLibrary->listFontSets();

        $googleQuery = (string) $request->getQuery('google_q', '');
        $googleCatalog = $assetLibrary->getGoogleFontCatalog($googleQuery);

        $iconsAnalysis = $this->kernel->session()->get('settings_icons_analysis', null);
        $fontsAnalysis = $this->kernel->session()->get('settings_fonts_analysis', null);

        $exportType = (string) $request->getQuery('export_type', '');
        $exportId = (string) $request->getQuery('export_id', '');
        $exportPayload = null;
        if ($exportType !== '' && $exportId !== '') {
            $exportPayload = $assetLibrary->exportSet($exportType, $exportId);
        }

        return Response::html(
            $this->kernel->themes()->render('settings.twig', array_merge($this->baseData(), [
                'current_route' => 'settings',
                'settings'      => $ordered,
                'can_manage_settings' => $canManageSettings,
                'can_manage_icons' => $canManageIcons,
                'can_manage_fonts' => $canManageFonts,
                'asset_active_tab' => $activeTab,
                'icon_sources' => $assetLibrary->knownIconSources(),
                'icon_source_templates' => $assetLibrary->knownSourceTemplates(),
                'icon_sets' => $iconSets,
                'current_sidebar_icon_set' => $this->findSettingValue($settings, 'appearance', 'sidebar_icon_set') ?? '',
                'font_sets' => $fontSets,
                'google_q' => $googleQuery,
                'google_catalog' => $googleCatalog,
                'icons_analysis' => $iconsAnalysis,
                'icons_template_result' => $this->kernel->session()->get('settings_icons_template_result', null),
                'fonts_analysis' => $fontsAnalysis,
                'asset_export_payload' => $exportPayload,
                'asset_export_type' => $exportType,
                'asset_export_id' => $exportId,
            ]))
        );
    }

    public function settingsUpdate(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/settings');
        }

        $assetAction = trim((string) $request->getPost('asset_action', ''));
        if ($assetAction !== '') {
            $user = $this->currentUser();
            $assetLibrary = $this->kernel->assetLibrary();

            if (str_starts_with($assetAction, 'icons.') && !$this->canManageIconLibraries($user)) {
                $this->kernel->session()->flash('danger', 'Keine Berechtigung für den Icon-Manager.');
                return Response::redirect('/admin/settings?tab=icons');
            }

            if (str_starts_with($assetAction, 'fonts.') && !$this->canManageFontLibraries($user)) {
                $this->kernel->session()->flash('danger', 'Keine Berechtigung für den Font-Manager.');
                return Response::redirect('/admin/settings?tab=fonts');
            }

            if ($assetAction === 'icons.analyze') {
                $analysis = $assetLibrary->analyzeIconCss((string) $request->getPost('source_url', ''));
                $this->kernel->session()->set('settings_icons_analysis', $analysis);
                $this->kernel->session()->flash($analysis['success'] ? 'success' : 'danger', (string) ($analysis['message'] ?? 'Analyse fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.install') {
                $result = $assetLibrary->installIconSetFromUrl([
                    'id' => (string) $request->getPost('set_id', ''),
                    'name' => (string) $request->getPost('set_name', ''),
                    'source_url' => (string) $request->getPost('source_url', ''),
                    'areas' => $this->parseMultiSelect($request->getPost('areas', [])),
                    'allow_for' => $this->parseMultiSelect($request->getPost('allow_for', [])),
                    'status' => (string) $request->getPost('status', 'active'),
                ]);
                if (($result['success'] ?? false) === true && is_array($result['set'] ?? null)) {
                    $assetLibrary->ensureIconSourceFromSet($result['set']);
                }
                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Installation fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.source.add') {
                $result = $assetLibrary->addIconSource([
                    'id' => (string) $request->getPost('source_id', ''),
                    'name' => (string) $request->getPost('source_name', ''),
                    'url' => (string) $request->getPost('source_url', ''),
                    'template_id' => (string) $request->getPost('source_template_id', ''),
                    'package' => (string) $request->getPost('source_package', ''),
                    'latest_version' => (string) $request->getPost('source_latest_version', ''),
                    'last_checked' => date('Y-m-d H:i:s'),
                    'status' => 'known',
                ]);
                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Quelle konnte nicht gespeichert werden.'));
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.template.search') {
                $result = $assetLibrary->resolveIconSourceByTemplate([
                    'template_id' => (string) $request->getPost('template_id', ''),
                    'query' => (string) $request->getPost('template_query', ''),
                    'path' => (string) $request->getPost('template_path', ''),
                    'version' => (string) $request->getPost('template_version', ''),
                ]);
                $this->kernel->session()->set('settings_icons_template_result', $result);
                $this->kernel->session()->flash(($result['success'] ?? false) ? 'success' : 'danger', (string) ($result['message'] ?? 'Suche fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.template.install') {
                $cssItems = $this->parseLineList($request->getPost('template_css_urls', ''));
                $jsItems = $this->parseLineList($request->getPost('template_js_urls', ''));
                $fontItems = $this->parseLineList($request->getPost('template_font_urls', ''));

                $entries = [];
                foreach ($cssItems as $item) {
                    $entries[] = ['type' => 'css', 'path' => $item];
                }
                foreach ($jsItems as $item) {
                    $entries[] = ['type' => 'js', 'path' => $item];
                }
                foreach ($fontItems as $item) {
                    $entries[] = ['type' => 'font', 'path' => $item];
                }

                $result = $assetLibrary->installIconSetFromTemplate([
                    'id' => (string) $request->getPost('set_id', ''),
                    'name' => (string) $request->getPost('set_name', ''),
                    'template_id' => (string) $request->getPost('template_id', ''),
                    'package' => (string) $request->getPost('template_package', ''),
                    'version' => (string) $request->getPost('template_version', 'latest'),
                    'asset_entries' => $entries,
                    'areas' => $this->parseMultiSelect($request->getPost('areas', [])),
                    'allow_for' => $this->parseMultiSelect($request->getPost('allow_for', [])),
                    'status' => (string) $request->getPost('status', 'active'),
                ]);
                if (($result['success'] ?? false) === true && is_array($result['set'] ?? null)) {
                    $assetLibrary->ensureIconSourceFromSet($result['set']);
                }

                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Template-Installation fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.template.add') {
                $result = $assetLibrary->addSourceTemplate([
                    'id' => (string) $request->getPost('template_new_id', ''),
                    'name' => (string) $request->getPost('template_new_name', ''),
                    'type' => (string) $request->getPost('template_new_type', 'generic'),
                    'url_template' => (string) $request->getPost('template_new_url_template', ''),
                    'versions_api' => (string) $request->getPost('template_new_versions_api', ''),
                    'default_path' => (string) $request->getPost('template_new_default_path', ''),
                ]);
                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Template konnte nicht gespeichert werden.'));
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.template.delete') {
                $id = (string) $request->getPost('template_id', '');
                $ok = $assetLibrary->removeSourceTemplate($id);
                $this->kernel->session()->flash($ok ? 'success' : 'danger', $ok ? 'Template entfernt.' : 'Template nicht gefunden.');
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.source.delete') {
                $id = (string) $request->getPost('source_id', '');
                $ok = $assetLibrary->removeIconSource($id);
                $this->kernel->session()->flash($ok ? 'success' : 'danger', $ok ? 'Quelle entfernt.' : 'Quelle nicht gefunden.');
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.update') {
                $id = (string) $request->getPost('set_id', '');
                $ok = $assetLibrary->updateSet('icons', $id, [
                    'name' => (string) $request->getPost('set_name', ''),
                    'areas' => $this->parseMultiSelect($request->getPost('areas', [])),
                    'allow_for' => $this->parseMultiSelect($request->getPost('allow_for', [])),
                    'status' => (string) $request->getPost('status', 'active'),
                ]);
                $this->kernel->session()->flash($ok ? 'success' : 'danger', $ok ? 'Icon-Set gespeichert.' : 'Icon-Set nicht gefunden.');
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.delete') {
                $assetLibrary->deleteSet('icons', (string) $request->getPost('set_id', ''));
                $this->kernel->session()->flash('success', 'Icon-Set gelöscht.');
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'icons.import') {
                $result = $assetLibrary->importSetJson((string) $request->getPost('import_json', ''));
                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Import fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'fonts.google_install') {
                $result = $assetLibrary->installGoogleFont(
                    (string) $request->getPost('google_family', ''),
                    $this->parseMultiSelect($request->getPost('google_styles', [])),
                    [
                        'areas' => $this->parseMultiSelect($request->getPost('areas', [])),
                        'allow_for' => $this->parseMultiSelect($request->getPost('allow_for', [])),
                        'status' => (string) $request->getPost('status', 'active'),
                    ]
                );
                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Installation fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=fonts');
            }

            if ($assetAction === 'fonts.analyze') {
                $analysis = $assetLibrary->analyzeFontCss((string) $request->getPost('source_url', ''));
                $this->kernel->session()->set('settings_fonts_analysis', $analysis);
                $this->kernel->session()->flash($analysis['success'] ? 'success' : 'danger', (string) ($analysis['message'] ?? 'Analyse fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=fonts');
            }

            if ($assetAction === 'fonts.install') {
                $result = $assetLibrary->installFontSetFromUrl([
                    'id' => (string) $request->getPost('set_id', ''),
                    'name' => (string) $request->getPost('set_name', ''),
                    'provider' => (string) $request->getPost('provider', 'custom'),
                    'source_url' => (string) $request->getPost('source_url', ''),
                    'areas' => $this->parseMultiSelect($request->getPost('areas', [])),
                    'allow_for' => $this->parseMultiSelect($request->getPost('allow_for', [])),
                    'status' => (string) $request->getPost('status', 'active'),
                ]);
                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Installation fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=fonts');
            }

            if ($assetAction === 'fonts.update') {
                $id = (string) $request->getPost('set_id', '');
                $ok = $assetLibrary->updateSet('fonts', $id, [
                    'name' => (string) $request->getPost('set_name', ''),
                    'areas' => $this->parseMultiSelect($request->getPost('areas', [])),
                    'allow_for' => $this->parseMultiSelect($request->getPost('allow_for', [])),
                    'status' => (string) $request->getPost('status', 'active'),
                ]);
                $this->kernel->session()->flash($ok ? 'success' : 'danger', $ok ? 'Font-Set gespeichert.' : 'Font-Set nicht gefunden.');
                return Response::redirect('/admin/settings?tab=fonts');
            }

            if ($assetAction === 'fonts.delete') {
                $assetLibrary->deleteSet('fonts', (string) $request->getPost('set_id', ''));
                $this->kernel->session()->flash('success', 'Font-Set gelöscht.');
                return Response::redirect('/admin/settings?tab=fonts');
            }

            if ($assetAction === 'fonts.import') {
                $result = $assetLibrary->importSetJson((string) $request->getPost('import_json', ''));
                $this->kernel->session()->flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Import fehlgeschlagen.'));
                return Response::redirect('/admin/settings?tab=fonts');
            }

            if ($assetAction === 'icons.set_sidebar') {
                $setId = (string) $request->getPost('sidebar_icon_set', '');
                // try to update existing setting row if present
                $appearance = $this->getSettingsByGroup('appearance');
                $found = false;
                foreach ($appearance as $row) {
                    if ((string) ($row['key'] ?? '') === 'sidebar_icon_set') {
                        $this->kernel->data()->updateSetting((int) ($row['id'] ?? 0), $setId);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    // insert new setting row
                    $now = date('Y-m-d H:i:s');
                    try {
                        $this->kernel->db()->insert('settings', [
                            'group' => 'appearance',
                            'key' => 'sidebar_icon_set',
                            'value' => $setId,
                            'type' => 'string',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        $found = true;
                    } catch (\Throwable $e) {
                        $found = false;
                    }
                }

                $this->kernel->session()->flash($found ? 'success' : 'danger', $found ? 'Sidebar Icon-Set gespeichert.' : 'Konnte nicht gespeichert werden.');
                return Response::redirect('/admin/settings?tab=icons');
            }

            if ($assetAction === 'google_api_key_save') {
                $key = trim((string) $request->getPost('google_api_key', ''));
                $secretsDir = $this->kernel->getBasePath() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'secrets';
                if (!is_dir($secretsDir)) {
                    @mkdir($secretsDir, 0750, true);
                }
                $file = $secretsDir . DIRECTORY_SEPARATOR . 'google_fonts_api_key';
                $ok = false;
                if ($key !== '') {
                    $ok = @file_put_contents($file, $key, LOCK_EX) !== false;
                } else {
                    // empty => remove
                    if (is_file($file)) {
                        $ok = @unlink($file);
                    } else {
                        $ok = true;
                    }
                }
                $this->kernel->session()->flash($ok ? 'success' : 'danger', $ok ? 'API‑Key gespeichert.' : 'API‑Key konnte nicht gespeichert werden.');
                return Response::redirect('/admin/settings?tab=fonts');
            }
        }

        $redirect = $this->requirePermission('system.manage');
        if ($redirect) return $redirect;

        $values = $request->getPost('settings', []);

        if (is_array($values)) {
            foreach ($values as $id => $value) {
                $this->kernel->data()->updateSetting((int) $id, $value);
            }
        }

        $this->kernel->session()->flash('success', 'Einstellungen wurden gespeichert.');
        return Response::redirect('/admin/settings');
    }

    /* ───────────────────────────────────────────────
     *  Themes
     * ─────────────────────────────────────────────── */

    public function themesList(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $allThemes = $this->kernel->themes()->getThemes();
        $split = $this->splitThemesByArea($allThemes);
        $adminThemes = $split['admin'];
        $frontendThemes = $split['frontend'];
        $marketplace = $this->buildThemeMarketplace($request->getQuery());

        $activeAdminTheme = $this->kernel->themes()->getAdminThemeId();
        $activeFrontendTheme = $this->kernel->themes()->getFrontendThemeId();

        $canManageThemes = $this->canManageThemes();

        return Response::html(
            $this->kernel->themes()->render('themes.twig', array_merge($this->baseData(), [
                'current_route'   => 'themes',
                'admin_themes'    => $adminThemes,
                'frontend_themes' => $frontendThemes,
                'active_admin_theme' => $activeAdminTheme,
                'active_frontend_theme' => $activeFrontendTheme,
                'marketplace' => $marketplace,
                'can_manage_themes' => $canManageThemes,
            ]))
        );
    }

    public function themesInstalled(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $allThemes = $this->kernel->themes()->getThemes();
        $split = $this->splitThemesByArea($allThemes);

        $activeAdmin = $this->kernel->themes()->getAdminThemeId();
        $activeFrontend = $this->kernel->themes()->getFrontendThemeId();
        $selectedAdmin = (string) $request->getQuery('admin_theme', $activeAdmin);
        $selectedFrontend = (string) $request->getQuery('frontend_theme', $activeFrontend);

        if (!isset($split['admin']['admin/' . $selectedAdmin])) {
            $selectedAdmin = $activeAdmin;
        }
        if (!isset($split['frontend']['frontend/' . $selectedFrontend])) {
            $selectedFrontend = $activeFrontend;
        }

        return Response::html(
            $this->kernel->themes()->render('themes_installed.twig', array_merge($this->baseData(), [
                'current_route' => 'themes',
                'admin_themes' => $split['admin'],
                'frontend_themes' => $split['frontend'],
                'active_admin_theme' => $activeAdmin,
                'active_frontend_theme' => $activeFrontend,
                'selected_admin_theme' => $selectedAdmin,
                'selected_frontend_theme' => $selectedFrontend,
                'selected_admin_theme_data' => $split['admin']['admin/' . $selectedAdmin] ?? null,
                'selected_frontend_theme_data' => $split['frontend']['frontend/' . $selectedFrontend] ?? null,
                'can_manage_themes' => $this->canManageThemes(),
            ]))
        );
    }

    public function themesInstalledDetail(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $area = (string) $request->getRouteParam('area', '');
        $id = (string) $request->getRouteParam('id', '');
        if (!in_array($area, ['admin', 'frontend'], true) || $id === '') {
            return Response::notFound('Theme nicht gefunden.');
        }

        $key = $area . '/' . $id;
        $themes = $this->kernel->themes()->getThemes();
        $theme = $themes[$key] ?? null;
        if (!is_array($theme)) {
            return Response::notFound('Theme nicht gefunden.');
        }

        $isActive = ($area === 'admin' && $id === $this->kernel->themes()->getAdminThemeId())
            || ($area === 'frontend' && $id === $this->kernel->themes()->getFrontendThemeId());

        $children = [];
        foreach ($themes as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if ((string) ($entry['_area'] ?? '') !== $area) {
                continue;
            }
            if ((string) ($entry['parent'] ?? '') === $id) {
                $children[] = $entry;
            }
        }

        return Response::html(
            $this->kernel->themes()->render('themes_installed_detail.twig', array_merge($this->baseData(), [
                'current_route' => 'themes',
                'theme_area' => $area,
                'theme_key' => $key,
                'theme' => $theme,
                'is_active_theme' => $isActive,
                'child_themes' => $children,
                'can_manage_themes' => $this->canManageThemes(),
            ]))
        );
    }

    public function themesInstalledDetailUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('system.themes');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/themes/installed');
        }

        $area = (string) $request->getRouteParam('area', '');
        $id = (string) $request->getRouteParam('id', '');
        if (!in_array($area, ['admin', 'frontend'], true) || $id === '') {
            return Response::notFound('Theme nicht gefunden.');
        }

        $manifest = [
            'name' => trim((string) $request->getPost('name', '')),
            'description' => trim((string) $request->getPost('description', '')),
            'author' => trim((string) $request->getPost('author', '')),
            'version' => trim((string) $request->getPost('version', '')),
            'disabled' => $request->getPost('disabled', '0') === '1',
        ];

        if ($manifest['name'] === '') {
            $this->kernel->session()->flash('danger', 'Der Theme-Name darf nicht leer sein.');
            return Response::redirect('/admin/themes/installed/' . $area . '/' . $id);
        }
        if ($manifest['version'] !== '' && !preg_match('/^[a-zA-Z0-9._-]{1,32}$/', $manifest['version'])) {
            $this->kernel->session()->flash('danger', 'Die Version ist ungültig. Erlaubt sind Buchstaben, Zahlen, Punkt, Unterstrich und Bindestrich.');
            return Response::redirect('/admin/themes/installed/' . $area . '/' . $id);
        }

        $ok = $this->kernel->themes()->updateThemeManifest($area, $id, $manifest);
        $this->audit('info', 'theme.detail.update', ['user_id' => $this->kernel->session()->get('user_id'), 'area' => $area, 'id' => $id, 'ok' => $ok]);

        if ($ok) {
            $this->kernel->session()->flash('success', 'Theme-Details wurden gespeichert.');
        } else {
            $this->kernel->session()->flash('danger', 'Theme-Details konnten nicht gespeichert werden.');
        }

        return Response::redirect('/admin/themes/installed/' . $area . '/' . $id);
    }

    public function themesInstalledCreateChild(Request $request): Response
    {
        $redirect = $this->requirePermission('system.themes');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/themes/installed');
        }

        $area = (string) $request->getRouteParam('area', '');
        $id = (string) $request->getRouteParam('id', '');
        if (!in_array($area, ['admin', 'frontend'], true) || $id === '') {
            return Response::notFound('Theme nicht gefunden.');
        }

        $childId = strtolower(trim((string) $request->getPost('child_id', '')));
        $name = trim((string) $request->getPost('child_name', ''));
        $author = trim((string) $request->getPost('child_author', ''));
        $description = trim((string) $request->getPost('child_description', ''));

        if ($childId === '' || !preg_match('/^[a-z0-9_-]+$/', $childId)) {
            $this->kernel->session()->flash('danger', 'Child-ID ist ungültig. Erlaubt sind a-z, 0-9, - und _.');
            return Response::redirect('/admin/themes/installed/' . $area . '/' . $id);
        }
        if ($name === '') {
            $this->kernel->session()->flash('danger', 'Child-Name darf nicht leer sein.');
            return Response::redirect('/admin/themes/installed/' . $area . '/' . $id);
        }

        $child = $this->kernel->themes()->createChildTheme($area, $id, $childId, $name, $author, $description);
        $ok = is_array($child);
        $this->audit('info', 'theme.child.create', ['user_id' => $this->kernel->session()->get('user_id'), 'area' => $area, 'parent' => $id, 'child_id' => $childId, 'ok' => $ok]);

        if (!$ok) {
            $this->kernel->session()->flash('danger', 'Child-Theme konnte nicht erstellt werden.');
            return Response::redirect('/admin/themes/installed/' . $area . '/' . $id);
        }

        $this->kernel->session()->flash('success', 'Child-Theme wurde erstellt.');
        return Response::redirect('/admin/themes/installed/' . $area . '/' . $childId);
    }

    public function themesActivate(Request $request): Response
    {
        $redirect = $this->requirePermission('system.themes');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/themes/installed');
        }

        $area = (string) $request->getRouteParam('area', '');
        $id = (string) $request->getRouteParam('id', '');
        if (!in_array($area, ['admin', 'frontend'], true) || $id === '') {
            return Response::notFound('Theme nicht gefunden.');
        }

        if (!$this->kernel->themes()->themeExists($area, $id)) {
            $this->kernel->session()->flash('danger', 'Theme nicht gefunden.');
            return Response::redirect('/admin/themes/installed');
        }

        $theme = $this->kernel->themes()->getTheme($area, $id);
        if (is_array($theme) && !empty($theme['disabled'])) {
            $this->kernel->session()->flash('danger', 'Dieses Theme ist deaktiviert. Aktivieren Sie zuerst den Theme-Status.');
            return Response::redirect('/admin/themes/installed');
        }

        $ok = false;
        if ($area === 'admin') {
            $ok = $this->kernel->themes()->setAdminThemeId($id);
        } else {
            $ok = $this->kernel->themes()->setFrontendThemeId($id);
        }

        // Persist selected theme in settings table (support both legacy and current groups).
        $settings = array_merge(
            $this->getSettingsByGroup('theme'),
            $this->getSettingsByGroup('appearance')
        );
        $values = [];
        if ($area === 'admin') $values['admin_theme'] = $id;
        if ($area === 'frontend') $values['frontend_theme'] = $id;
        $this->updateSettingsByKey($settings, $values);

        // Audit log
        $this->audit('info', 'theme.activate', ['user_id' => $this->kernel->session()->get('user_id'), 'area' => $area, 'id' => $id, 'ok' => $ok]);

        if ($ok) {
            $this->kernel->session()->flash('success', 'Theme wurde aktiviert.');
        } else {
            $this->kernel->session()->flash('danger', 'Theme konnte nicht aktiviert werden.');
        }

        return Response::redirect('/admin/themes/installed');
    }

    public function themesToggleStatus(Request $request): Response
    {
        $redirect = $this->requirePermission('system.themes');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/themes/installed');
        }

        $area = (string) $request->getRouteParam('area', '');
        $id = (string) $request->getRouteParam('id', '');
        if (!in_array($area, ['admin', 'frontend'], true) || $id === '') {
            return Response::notFound('Theme nicht gefunden.');
        }

        $isActive = ($area === 'admin' && $id === $this->kernel->themes()->getAdminThemeId())
            || ($area === 'frontend' && $id === $this->kernel->themes()->getFrontendThemeId());
        $theme = $this->kernel->themes()->getTheme($area, $id);
        if ($isActive && is_array($theme) && empty($theme['disabled'])) {
            $this->kernel->session()->flash('danger', 'Das aktuell aktive Theme kann nicht deaktiviert werden. Bitte aktivieren Sie zuerst ein anderes Theme.');
            return Response::redirect('/admin/themes/installed');
        }

        $new = $this->kernel->themes()->toggleThemeDisabled($area, $id);
        // Audit log
        $this->audit('info', 'theme.toggle_status', ['user_id' => $this->kernel->session()->get('user_id'), 'area' => $area, 'id' => $id, 'new_disabled' => $new]);

        if ($new === null) {
            $this->kernel->session()->flash('danger', 'Status konnte nicht geändert werden.');
        } else {
            $this->kernel->session()->flash('success', $new ? 'Theme deaktiviert.' : 'Theme aktiviert.');
        }

        return Response::redirect('/admin/themes/installed');
    }

    public function themesUninstall(Request $request): Response
    {
        $redirect = $this->requirePermission('system.themes');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/themes/installed');
        }

        $area = (string) $request->getRouteParam('area', '');
        $id = (string) $request->getRouteParam('id', '');
        if (!in_array($area, ['admin', 'frontend'], true) || $id === '') {
            return Response::notFound('Theme nicht gefunden.');
        }

        // Prevent uninstall of active theme
        $activeAdmin = $this->kernel->themes()->getAdminThemeId();
        $activeFrontend = $this->kernel->themes()->getFrontendThemeId();
        if (($area === 'admin' && $id === $activeAdmin) || ($area === 'frontend' && $id === $activeFrontend)) {
            $this->kernel->session()->flash('danger', 'Das aktive Theme kann nicht deinstalliert werden.');
            return Response::redirect('/admin/themes/installed');
        }

        $allThemes = $this->kernel->themes()->getThemes();
        $themeKey = $area . '/' . $id;
        $theme = $allThemes[$themeKey] ?? null;

        $uninstallMeta = $this->kernel->themes()->uninstallTheme($area, $id);
        $ok = is_array($uninstallMeta);
        // Audit log
        $this->audit('warning', 'theme.uninstall', ['user_id' => $this->kernel->session()->get('user_id'), 'area' => $area, 'id' => $id, 'ok' => $ok]);

        if ($ok) {
            $this->kernel->trash()->add(
                'themes',
                'theme',
                $themeKey,
                [
                    'name' => (string) (($theme['name'] ?? $id)),
                    'area' => $area,
                    'id' => $id,
                    'source_path' => (string) ($uninstallMeta['source_path'] ?? ''),
                    'backup_path' => (string) ($uninstallMeta['backup_path'] ?? ''),
                    'theme' => is_array($theme) ? $theme : [],
                ],
                (int) $this->kernel->session()->get('user_id')
            );
        }

        if ($ok) {
            $this->kernel->session()->flash('success', 'Theme wurde deinstalliert.');
        } else {
            $this->kernel->session()->flash('danger', 'Theme konnte nicht deinstalliert werden.');
        }

        return Response::redirect('/admin/themes/installed');
    }

    /**
     * Lightweight API endpoint for admin UI to validate Google Fonts API key.
     */
    public function googleFontsStatus(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $secretsFile = $this->kernel->getBasePath() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'secrets' . DIRECTORY_SEPARATOR . 'google_fonts_api_key';
        $key = is_file($secretsFile) ? trim((string) @file_get_contents($secretsFile)) : '';

        if ($key === '') {
            return Response::apiSuccess(['ok' => false, 'message' => 'No API key configured']);
        }

        $url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . urlencode($key);
        try {
            $opts = stream_context_create(['http' => ['timeout' => 4]]);
            $res = @file_get_contents($url, false, $opts);
            if ($res === false) {
                // Try cURL as a fallback (honors proxies and gives better errors)
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    $cres = curl_exec($ch);
                    if ($cres === false) {
                        $cerr = curl_error($ch);
                        curl_close($ch);
                        return Response::apiSuccess(['ok' => false, 'message' => 'Could not reach Google Fonts API: ' . $cerr]);
                    }
                    $res = $cres;
                    curl_close($ch);
                } else {
                    return Response::apiSuccess(['ok' => false, 'message' => 'Could not reach Google Fonts API']);
                }
            }
            $json = json_decode($res, true);
            if (is_array($json) && isset($json['kind'])) {
                // Google Webfonts API typically returns an 'items' array. Older variants may include 'totalItems'.
                if (isset($json['totalItems'])) {
                    $total = (int) $json['totalItems'];
                } elseif (isset($json['items']) && is_array($json['items'])) {
                    $total = count($json['items']);
                } else {
                    $total = 0;
                }
                return Response::apiSuccess(['ok' => true, 'message' => 'API erreichbar', 'total' => $total]);
            }
            // Google may return an error payload
            if (is_array($json) && isset($json['error'])) {
                $raw = is_string($json['error']['message'] ?? '') ? $json['error']['message'] : 'API error';
                // Map some common Google error messages to friendlier guidance
                $lower = strtolower($raw);
                if (strpos($lower, 'invalid') !== false || strpos($lower, 'invalid argument') !== false) {
                    $msg = 'Ungültiger API‑Key oder Webfonts API nicht aktiviert. Prüfe Cloud Console (APIs & Dienste → Anmeldedaten) und entferne ggf. Key‑Einschränkungen.';
                } else {
                    $msg = $raw;
                }
                return Response::apiSuccess(['ok' => false, 'message' => $msg]);
            }
            return Response::apiSuccess(['ok' => false, 'message' => 'Unexpected API response']);
        } catch (\Throwable $e) {
            return Response::apiSuccess(['ok' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * POST endpoint to validate a provided Google Fonts API key without storing it.
     */
    public function googleFontsCheck(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $key = trim((string) $request->getPost('key', ''));
        if ($key === '') {
            return Response::apiSuccess(['ok' => false, 'message' => 'No key provided']);
        }

        $url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . urlencode($key);
        try {
            $opts = stream_context_create(['http' => ['timeout' => 4]]);
            $res = @file_get_contents($url, false, $opts);
            if ($res === false) {
                // Try cURL as a fallback (honors proxies and gives better errors)
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    $cres = curl_exec($ch);
                    if ($cres === false) {
                        $cerr = curl_error($ch);
                        curl_close($ch);
                        return Response::apiSuccess(['ok' => false, 'message' => 'Could not reach Google Fonts API: ' . $cerr]);
                    }
                    $res = $cres;
                    curl_close($ch);
                } else {
                    return Response::apiSuccess(['ok' => false, 'message' => 'Could not reach Google Fonts API']);
                }
            }
            $json = json_decode($res, true);
            if (is_array($json) && isset($json['kind'])) {
                if (isset($json['totalItems'])) {
                    $total = (int) $json['totalItems'];
                } elseif (isset($json['items']) && is_array($json['items'])) {
                    $total = count($json['items']);
                } else {
                    $total = 0;
                }
                return Response::apiSuccess(['ok' => true, 'message' => 'OK', 'total' => $total]);
            }
            if (is_array($json) && isset($json['error'])) {
                $raw = is_string($json['error']['message'] ?? '') ? $json['error']['message'] : 'API error';
                $lower = strtolower($raw);
                if (strpos($lower, 'invalid') !== false || strpos($lower, 'invalid argument') !== false) {
                    $msg = 'Ungültiger API‑Key oder Webfonts API nicht aktiviert. Prüfe Cloud Console (APIs & Dienste → Anmeldedaten) und entferne ggf. Key‑Einschränkungen.';
                } else {
                    $msg = $raw;
                }
                return Response::apiSuccess(['ok' => false, 'message' => $msg]);
            }
            return Response::apiSuccess(['ok' => false, 'message' => 'Unexpected API response']);
        } catch (\Throwable $e) {
            return Response::apiSuccess(['ok' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * GET endpoint for live Google Fonts search with server-side filtering.
     */
    public function googleFontsSearch(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $query = [
            'q' => (string) $request->getQuery('q', ''),
            'style' => (string) $request->getQuery('style', ''),
            'category' => (string) $request->getQuery('category', ''),
            'subcategory' => (string) $request->getQuery('subcategory', ''),
            'page' => (int) $request->getQuery('page', 1),
            'per_page' => (int) $request->getQuery('per_page', 10),
        ];

        try {
            $result = $this->kernel->assetLibrary()->searchGoogleFonts($query);
            return Response::apiSuccess($result);
        } catch (\Throwable $e) {
            return Response::apiError('google_fonts_search_failed', 'Google Fonts Suche fehlgeschlagen: ' . $e->getMessage(), 500);
        }
    }

    public function trashPage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $query = $request->getQuery();
        $filters = [
            'status' => (string) ($query['status'] ?? 'trashed'),
            'category' => (string) ($query['category'] ?? 'all'),
            'entity_type' => (string) ($query['entity_type'] ?? 'all'),
            'q' => (string) ($query['q'] ?? ''),
            'sort' => (string) ($query['sort'] ?? 'deleted_desc'),
        ];

        $items = $this->kernel->trash()->list($filters);

        // Build a map of actor id -> display name for deleted_by resolution
        $actorIds = [];
        foreach ($items as $it) {
            $did = $it['deleted_by'] ?? null;
            if ($did !== null && $did !== '' ) {
                $actorIds[(int)$did] = true;
            }
        }

        $trashActors = [];
        if (!empty($actorIds)) {
            foreach (array_keys($actorIds) as $aid) {
                $u = $this->kernel->data()->getUserById((int)$aid);
                if ($u !== null) {
                    $trashActors[$aid] = (string) ($u['display_name'] ?? $u['username'] ?? $u['email'] ?? '');
                }
            }
        }

        return Response::html(
            $this->kernel->themes()->render('trash.twig', array_merge($this->baseData(), [
                'current_route' => 'trash',
                'trash_items' => $items,
                'trash_stats' => $this->kernel->trash()->stats(),
                'trash_filters' => $filters,
                'trash_actors' => $trashActors,
                'can_manage_trash' => $this->canManageTrash(),
            ]))
        );
    }

    public function trashRestore(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/trash');
        }

        $id = (string) $request->getRouteParam('id', '');
        $item = $this->kernel->trash()->get($id);
        if ($item === null) {
            $this->kernel->session()->flash('danger', 'Papierkorb-Eintrag nicht gefunden.');
            return Response::redirect('/admin/trash');
        }

        if (!$this->canManageTrashItem($item)) {
            $this->kernel->session()->flash('danger', 'Keine Berechtigung für diese Wiederherstellung.');
            return Response::redirect('/admin/trash');
        }

        if ((string) ($item['status'] ?? '') !== 'trashed') {
            $this->kernel->session()->flash('info', 'Eintrag ist nicht mehr aktiv im Papierkorb.');
            return Response::redirect('/admin/trash');
        }

        $ok = $this->restoreTrashItem($item);
        if ($ok) {
            $this->kernel->trash()->markRestored($id);
            $this->kernel->session()->flash('success', 'Eintrag wurde wiederhergestellt.');
        } else {
            $this->kernel->session()->flash('danger', 'Eintrag konnte nicht wiederhergestellt werden.');
        }

        $this->audit('info', 'trash.restore', ['user_id' => $this->kernel->session()->get('user_id'), 'trash_id' => $id, 'ok' => $ok]);
        return Response::redirect('/admin/trash');
    }

    public function trashPurge(Request $request): Response
    {
        $redirect = $this->requirePermission('system.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/trash');
        }

        $id = (string) $request->getRouteParam('id', '');
        $item = $this->kernel->trash()->get($id);
        if ($item === null) {
            $this->kernel->session()->flash('danger', 'Papierkorb-Eintrag nicht gefunden.');
            return Response::redirect('/admin/trash');
        }

        $this->kernel->trash()->purge($id);
        $this->audit('warning', 'trash.purge', ['user_id' => $this->kernel->session()->get('user_id'), 'trash_id' => $id]);
        $this->kernel->session()->flash('success', 'Eintrag wurde endgültig entfernt.');
        return Response::redirect('/admin/trash');
    }

    private function canManageTrash(): bool
    {
        $user = $this->currentUser();
        if ($user === null) return false;

        foreach (['content.delete', 'users.manage', 'roles.manage', 'permissions.manage', 'system.themes', 'system.mods', 'system.manage'] as $permission) {
            if ($this->kernel->permissions()->userCan($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    private function canManageTrashItem(array $item): bool
    {
        $user = $this->currentUser();
        if ($user === null) return false;

        $type = (string) ($item['entity_type'] ?? '');
        $required = match ($type) {
            'content_entry' => 'content.delete',
            'user' => 'users.manage',
            'role' => 'roles.manage',
            'permission' => 'permissions.manage',
            'theme' => 'system.themes',
            'module' => 'system.mods',
            default => 'system.manage',
        };

        return $this->kernel->permissions()->userCan($user, $required)
            || $this->kernel->permissions()->userCan($user, 'system.manage');
    }

    private function restoreTrashItem(array $item): bool
    {
        $type = (string) ($item['entity_type'] ?? '');
        $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];

        return match ($type) {
            'content_entry' => $this->restoreContentEntry($payload),
            'user' => $this->restoreUser($payload),
            'role' => $this->restoreRole($payload),
            'permission' => $this->restorePermission($payload),
            'theme' => $this->restoreTheme($payload),
            default => false,
        };
    }

    private function restoreTheme(array $payload): bool
    {
        return $this->kernel->themes()->restoreThemeFromTrash($payload);
    }

    private function restoreContentEntry(array $payload): bool
    {
        $entry = is_array($payload['entry'] ?? null) ? $payload['entry'] : null;
        if ($entry === null) return false;

        $type = (string) ($payload['type'] ?? $entry['content_type'] ?? '');
        if ($type === '') return false;

        $data = is_array($entry['_data'] ?? null)
            ? $entry['_data']
            : (is_string($entry['data'] ?? null) ? (json_decode((string) $entry['data'], true) ?: []) : []);

        $created = $this->kernel->data()->createContent($type, $data, $this->kernel->session()->get('user_id'));
        return is_array($created) && !empty($created['id']);
    }

    private function restoreUser(array $payload): bool
    {
        $user = is_array($payload['user'] ?? null) ? $payload['user'] : null;
        if ($user === null) return false;

        $username = (string) ($user['username'] ?? '');
        if ($username === '' || $this->kernel->data()->getUserByUsername($username) !== null) {
            return false;
        }

        $newUser = [
            'username' => $username,
            'email' => (string) ($user['email'] ?? ''),
            'password_hash' => (string) ($user['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT)),
            'display_name' => (string) ($user['display_name'] ?? $username),
            'roles' => (array) ($user['roles'] ?? [$user['role'] ?? 'editor']),
            'role' => (string) ($user['role'] ?? ((array) ($user['roles'] ?? ['editor']))[0] ?? 'editor'),
            'is_active' => (int) ($user['is_active'] ?? 1),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->kernel->data()->createUser($newUser) > 0;
    }

    private function restoreRole(array $payload): bool
    {
        $role = is_array($payload['role'] ?? null) ? $payload['role'] : null;
        if ($role === null) return false;

        $key = (string) ($role['key'] ?? '');
        if ($key === '' || $this->kernel->data()->roleExistsByKey($key)) {
            return false;
        }

        $newId = $this->kernel->data()->createRole([
            'uuid' => bin2hex(random_bytes(16)),
            'key' => $key,
            'name' => (string) ($role['name'] ?? $key),
            'description' => (string) ($role['description'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($newId <= 0) return false;

        $this->kernel->data()->updateRolePermissions($newId, (array) ($payload['permission_keys'] ?? []));
        return true;
    }

    private function restorePermission(array $payload): bool
    {
        $permission = is_array($payload['permission'] ?? null) ? $payload['permission'] : null;
        if ($permission === null) return false;

        $key = (string) ($permission['key'] ?? '');
        if ($key === '' || $this->kernel->data()->permissionExistsByKey($key)) {
            return false;
        }

        $newId = $this->kernel->data()->createPermission([
            'key' => $key,
            'description' => (string) ($permission['description'] ?? $key),
            'group' => (string) ($permission['group'] ?? 'system'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($newId <= 0) return false;

        // Re-assign to roles where possible
        $roleIds = array_map('intval', (array) ($payload['role_ids'] ?? []));
        foreach ($roleIds as $roleId) {
            $existing = $this->kernel->data()->getRolePermissions($roleId);
            if (!in_array($key, $existing, true)) {
                $existing[] = $key;
                $this->kernel->data()->updateRolePermissions($roleId, $existing);
            }
        }

        return true;
    }

    private function audit(string $level, string $message, array $context = []): void
    {
        // Lightweight audit logger using the ErrorHandler log file
        try {
            $logger = new \Chamy\Core\Errors\ErrorHandler($this->kernel->getBasePath());
            $logger->log($level, $message, $context);
        } catch (\Throwable $e) {
            // best-effort logging, do not break execution
        }
    }

    public function themesMarketplace(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        return Response::html(
            $this->kernel->themes()->render('themes_marketplace.twig', array_merge($this->baseData(), [
                'current_route' => 'themes',
                'marketplace' => $this->buildThemeMarketplace($request->getQuery()),
                'can_manage_themes' => $this->canManageThemes(),
            ]))
        );
    }

    public function themesMarketplaceDetail(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $themeId = (string) $request->getRouteParam('id', '');
        $theme = null;
        foreach ($this->marketplaceThemeCatalog() as $entry) {
            if (($entry['id'] ?? '') === $themeId) {
                $theme = $entry;
                break;
            }
        }

        if ($theme === null) {
            return Response::notFound('Marketplace-Theme nicht gefunden.');
        }

        return Response::html(
            $this->kernel->themes()->render('themes_marketplace_detail.twig', array_merge($this->baseData(), [
                'current_route' => 'themes',
                'theme' => $theme,
                'can_manage_themes' => $this->canManageThemes(),
            ]))
        );
    }

    private function getSettingsByGroup(string $group): array
    {
        $all = $this->kernel->data()->getSettings();
        $settings = $all[$group] ?? [];
        return is_array($settings) ? $settings : [];
    }

    private function updateSettingsByKey(array $settings, array $values): int
    {
        $updated = 0;
        foreach ($settings as $setting) {
            $key = (string) ($setting['key'] ?? '');
            $id = (int) ($setting['id'] ?? 0);
            if ($key === '' || $id <= 0 || !array_key_exists($key, $values)) {
                continue;
            }
            $this->kernel->data()->updateSetting($id, (string) $values[$key]);
            $updated++;
        }

        return $updated;
    }

    public function themesConfigPage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $themeSettings = $this->getSettingsByGroup('theme');
        $selectedArea = strtolower((string) $request->getQuery('area', 'frontend'));
        if (!in_array($selectedArea, ['admin', 'frontend'], true)) {
            $selectedArea = 'frontend';
        }

        $selectedTheme = (string) $request->getQuery('theme', '');
        $allThemes = $this->kernel->themes()->getThemes();
        $split = $this->splitThemesByArea($allThemes);

        return Response::html(
            $this->kernel->themes()->render('themes_config.twig', array_merge($this->baseData(), [
                'current_route' => 'themes',
                'can_manage_themes' => $this->canManageThemes(),
                'theme_settings' => $themeSettings,
                'selected_area' => $selectedArea,
                'selected_theme' => $selectedTheme,
                'admin_themes' => $split['admin'],
                'frontend_themes' => $split['frontend'],
            ]))
        );
    }

    public function themesConfigUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('system.themes');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/themes/config');
        }

        $themeSettings = $this->getSettingsByGroup('theme');
        $values = $request->getPost('config', []);
        $updated = is_array($values)
            ? $this->updateSettingsByKey($themeSettings, $values)
            : 0;

        if ($updated > 0) {
            $this->kernel->session()->flash('success', 'Theme-Konfiguration wurde gespeichert.');
        } else {
            $this->kernel->session()->flash('info', 'Keine passenden Theme-Einstellungen gefunden oder geändert.');
        }

        return Response::redirect('/admin/themes/config');
    }

    public function themesMarketplaceConfigPage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $themeSettings = $this->getSettingsByGroup('theme');
        $settingsByKey = [];
        foreach ($themeSettings as $setting) {
            $settingsByKey[(string) ($setting['key'] ?? '')] = (string) ($setting['value'] ?? '');
        }

        $config = [
            'catalog_enabled' => ($settingsByKey['marketplace.catalog_enabled'] ?? '1') === '1',
            'allow_paid_themes' => ($settingsByKey['marketplace.allow_paid_themes'] ?? '1') === '1',
            'default_sort' => $settingsByKey['marketplace.default_sort'] ?? 'popular',
            'default_area' => $settingsByKey['marketplace.default_area'] ?? 'all',
        ];

        return Response::html(
            $this->kernel->themes()->render('themes_marketplace_config.twig', array_merge($this->baseData(), [
                'current_route' => 'themes',
                'can_manage_themes' => $this->canManageThemes(),
                'theme_settings' => $themeSettings,
                'marketplace_config' => $config,
            ]))
        );
    }

    public function themesMarketplaceConfigUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('system.themes');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/themes/marketplace/config');
        }

        $themeSettings = $this->getSettingsByGroup('theme');
        $input = $request->getPost('marketplace', []);
        $values = [
            'marketplace.catalog_enabled' => isset($input['catalog_enabled']) ? '1' : '0',
            'marketplace.allow_paid_themes' => isset($input['allow_paid_themes']) ? '1' : '0',
            'marketplace.default_sort' => (string) ($input['default_sort'] ?? 'popular'),
            'marketplace.default_area' => (string) ($input['default_area'] ?? 'all'),
        ];

        $updated = $this->updateSettingsByKey($themeSettings, $values);
        if ($updated > 0) {
            $this->kernel->session()->flash('success', 'Marketplace-Konfiguration wurde gespeichert.');
        } else {
            $this->kernel->session()->flash('info', 'Keine passenden Marketplace-Einstellungen gefunden oder geändert.');
        }

        return Response::redirect('/admin/themes/marketplace/config');
    }

    /* ═══════════════════════════════════════════════
     *  Modules – Helpers
     * ═══════════════════════════════════════════════ */

    private function canManageMods(): bool
    {
        $user = $this->currentUser();
        return $user !== null && $this->kernel->permissions()->userCan($user, 'system.mods');
    }

    private function modMarketplaceCatalog(): array
    {
        return [
            [
                'id'           => 'seo-toolkit',
                'name'         => 'SEO Toolkit',
                'description'  => 'Umfassende SEO-Optimierung mit Meta-Tags, Sitemap-Generator und Open Graph Integration.',
                'category'     => 'marketing',
                'pricing'      => 'free',
                'price'        => 0,
                'currency'     => 'EUR',
                'rating'       => 4.8,
                'downloads'    => 12400,
                'version'      => '2.3.1',
                'author'       => 'Chamy Team',
                'preview_color'=> '#39ff14',
                'tags'         => ['seo', 'meta', 'sitemap', 'opengraph'],
                'features'     => ['Meta-Tag Editor', 'XML Sitemap', 'OG Preview', 'Schema.org'],
                'requires'     => ['chamy' => '^1.0'],
                'updated_at'   => '2026-02-28',
            ],
            [
                'id'           => 'media-manager-pro',
                'name'         => 'Media Manager Pro',
                'description'  => 'Erweiterte Medienverwaltung mit Bildbearbeitung, Lazy Loading und CDN-Support.',
                'category'     => 'content',
                'pricing'      => 'paid',
                'price'        => 49,
                'currency'     => 'EUR',
                'rating'       => 4.9,
                'downloads'    => 8750,
                'version'      => '3.1.0',
                'author'       => 'MediaWorks',
                'preview_color'=> '#1f8ef1',
                'tags'         => ['media', 'images', 'cdn', 'upload'],
                'features'     => ['Drag & Drop Upload', 'Bildbearbeitung', 'CDN Integration', 'Lazy Loading'],
                'requires'     => ['chamy' => '^1.0', 'php' => '^8.1'],
                'updated_at'   => '2026-03-01',
            ],
            [
                'id'           => 'analytics-dashboard',
                'name'         => 'Analytics Dashboard',
                'description'  => 'Echtzeitstatistiken mit Besucherzahlen, Heatmaps und Conversion-Tracking.',
                'category'     => 'analytics',
                'pricing'      => 'paid',
                'price'        => 79,
                'currency'     => 'EUR',
                'rating'       => 4.7,
                'downloads'    => 5320,
                'version'      => '1.8.2',
                'author'       => 'DataVision',
                'preview_color'=> '#b44aff',
                'tags'         => ['analytics', 'tracking', 'statistik', 'heatmap'],
                'features'     => ['Live Dashboard', 'Heatmaps', 'Conversion Tracking', 'Export'],
                'requires'     => ['chamy' => '^1.0'],
                'updated_at'   => '2026-02-15',
            ],
            [
                'id'           => 'form-builder',
                'name'         => 'Form Builder',
                'description'  => 'Drag-and-Drop Formular-Editor mit Validierung, CAPTCHA und E-Mail-Benachrichtigungen.',
                'category'     => 'content',
                'pricing'      => 'free',
                'price'        => 0,
                'currency'     => 'EUR',
                'rating'       => 4.6,
                'downloads'    => 15800,
                'version'      => '2.0.4',
                'author'       => 'Chamy Team',
                'preview_color'=> '#ff6a00',
                'tags'         => ['formular', 'kontakt', 'builder', 'captcha'],
                'features'     => ['Drag & Drop', 'Validierung', 'CAPTCHA', 'E-Mail Versand'],
                'requires'     => ['chamy' => '^1.0'],
                'updated_at'   => '2026-03-05',
            ],
            [
                'id'           => 'backup-guardian',
                'name'         => 'Backup Guardian',
                'description'  => 'Automatisierte Backups mit Zeitplanung, Cloud-Anbindung und One-Click-Restore.',
                'category'     => 'system',
                'pricing'      => 'paid',
                'price'        => 39,
                'currency'     => 'EUR',
                'rating'       => 4.9,
                'downloads'    => 9100,
                'version'      => '1.5.0',
                'author'       => 'SecureOps',
                'preview_color'=> '#00f0ff',
                'tags'         => ['backup', 'restore', 'cloud', 'scheduler'],
                'features'     => ['Auto-Backup', 'Cloud Storage', 'Scheduler', 'One-Click Restore'],
                'requires'     => ['chamy' => '^1.0'],
                'updated_at'   => '2026-02-20',
            ],
            [
                'id'           => 'multilang-pro',
                'name'         => 'MultiLang Pro',
                'description'  => 'Mehrsprachigkeits-Toolkit mit automatischer Übersetzung und Sprachumschalter.',
                'category'     => 'localization',
                'pricing'      => 'paid',
                'price'        => 59,
                'currency'     => 'EUR',
                'rating'       => 4.5,
                'downloads'    => 4200,
                'version'      => '1.2.0',
                'author'       => 'LinguaLab',
                'preview_color'=> '#ff3c81',
                'tags'         => ['i18n', 'sprachen', 'übersetzung', 'locale'],
                'features'     => ['Auto-Übersetzung', 'Sprachumschalter', 'RTL Support', 'Import/Export'],
                'requires'     => ['chamy' => '^1.0'],
                'updated_at'   => '2026-01-30',
            ],
            [
                'id'           => 'api-gateway',
                'name'         => 'API Gateway',
                'description'  => 'RESTful API mit Token-Auth, Rate Limiting, Swagger-Dokumentation und Webhooks.',
                'category'     => 'developer',
                'pricing'      => 'free',
                'price'        => 0,
                'currency'     => 'EUR',
                'rating'       => 4.8,
                'downloads'    => 11200,
                'version'      => '2.1.0',
                'author'       => 'Chamy Team',
                'preview_color'=> '#ffd000',
                'tags'         => ['api', 'rest', 'webhooks', 'swagger'],
                'features'     => ['REST API', 'Token Auth', 'Rate Limiting', 'Swagger Docs'],
                'requires'     => ['chamy' => '^1.0'],
                'updated_at'   => '2026-03-08',
            ],
            [
                'id'           => 'cache-turbo',
                'name'         => 'Cache Turbo',
                'description'  => 'Hochleistungs-Caching mit Redis/Memcached-Support und intelligenter Cache-Invalidierung.',
                'category'     => 'performance',
                'pricing'      => 'paid',
                'price'        => 29,
                'currency'     => 'EUR',
                'rating'       => 4.7,
                'downloads'    => 7600,
                'version'      => '1.4.2',
                'author'       => 'SpeedCore',
                'preview_color'=> '#14c9a5',
                'tags'         => ['cache', 'redis', 'performance', 'speed'],
                'features'     => ['Redis/Memcached', 'Page Cache', 'Fragment Cache', 'Auto-Invalidierung'],
                'requires'     => ['chamy' => '^1.0'],
                'updated_at'   => '2026-02-25',
            ],
        ];
    }

    private function buildModMarketplace(array $query): array
    {
        $catalog = $this->modMarketplaceCatalog();

        $category = strtolower(trim((string) ($query['category'] ?? 'all')));
        $pricing  = strtolower(trim((string) ($query['pricing'] ?? 'all')));
        $search   = mb_strtolower(trim((string) ($query['q'] ?? '')));
        $sort     = strtolower(trim((string) ($query['sort'] ?? 'popular')));

        $validSorts = ['popular', 'rating', 'price_asc', 'price_desc', 'name', 'newest'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'popular';
        }

        $validPricing = ['all', 'free', 'paid'];
        if (!in_array($pricing, $validPricing, true)) {
            $pricing = 'all';
        }

        $categories = [];
        foreach ($catalog as $item) {
            $cat = (string) ($item['category'] ?? 'other');
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }
        ksort($categories);

        if ($category !== 'all' && !isset($categories[$category])) {
            $category = 'all';
        }

        $filtered = array_values(array_filter($catalog, static function (array $item) use ($category, $pricing, $search): bool {
            if ($category !== 'all' && ($item['category'] ?? '') !== $category) {
                return false;
            }
            if ($pricing !== 'all' && ($item['pricing'] ?? '') !== $pricing) {
                return false;
            }
            if ($search !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($item['name'] ?? ''),
                    (string) ($item['description'] ?? ''),
                    implode(' ', $item['tags'] ?? []),
                    (string) ($item['author'] ?? ''),
                    (string) ($item['category'] ?? ''),
                ]));
                if (!str_contains($haystack, $search)) {
                    return false;
                }
            }
            return true;
        }));

        usort($filtered, static function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'rating'     => (($b['rating'] ?? 0) <=> ($a['rating'] ?? 0)),
                'price_asc'  => (($a['price'] ?? 0) <=> ($b['price'] ?? 0)),
                'price_desc' => (($b['price'] ?? 0) <=> ($a['price'] ?? 0)),
                'name'       => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')),
                'newest'     => strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? '')),
                default      => (($b['downloads'] ?? 0) <=> ($a['downloads'] ?? 0)),
            };
        });

        return [
            'items'   => $filtered,
            'filters' => [
                'category' => $category,
                'pricing'  => $pricing,
                'q'        => (string) ($query['q'] ?? ''),
                'sort'     => $sort,
            ],
            'counts' => [
                'all'  => count($catalog),
                'free' => count(array_filter($catalog, static fn(array $i): bool => ($i['pricing'] ?? '') === 'free')),
                'paid' => count(array_filter($catalog, static fn(array $i): bool => ($i['pricing'] ?? '') === 'paid')),
            ],
            'categories' => $categories,
        ];
    }

    private function getModuleStats(): array
    {
        $installed = $this->kernel->modules()->getInstalled();
        $active    = $this->kernel->modules()->getActive();
        $catalog   = $this->modMarketplaceCatalog();

        return [
            'installed'        => count($installed),
            'active'           => count($active),
            'inactive'         => count($installed) - count($active),
            'marketplace_total'=> count($catalog),
            'marketplace_free' => count(array_filter($catalog, static fn(array $i): bool => ($i['pricing'] ?? '') === 'free')),
            'marketplace_paid' => count(array_filter($catalog, static fn(array $i): bool => ($i['pricing'] ?? '') === 'paid')),
        ];
    }

    private function categoryLabels(): array
    {
        return [
            'marketing'    => 'Marketing',
            'content'      => 'Inhalte',
            'analytics'    => 'Analytik',
            'system'       => 'System',
            'localization' => 'Lokalisierung',
            'developer'    => 'Entwickler',
            'performance'  => 'Performance',
        ];
    }

    /* ═══════════════════════════════════════════════
     *  Modules – Dashboard
     * ═══════════════════════════════════════════════ */

    public function modulesList(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $modules = $this->kernel->modules()->getInstalled();
        $active  = $this->kernel->modules()->getActive();
        $user    = $this->currentUser();
        $canManage = $user !== null && $this->kernel->permissions()->userCan($user, 'system.mods');

        $marketplace = $this->buildModMarketplace($request->getQuery());
        $stats       = $this->getModuleStats();

        return Response::html(
            $this->kernel->themes()->render('modules.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'modules'            => $modules,
                'active'             => $active,
                'can_manage_modules' => $canManage,
                'marketplace'        => $marketplace,
                'mod_stats'          => $stats,
                'category_labels'    => $this->categoryLabels(),
            ]))
        );
    }

    /* ═══════════════════════════════════════════════
     *  Modules – Marketplace
     * ═══════════════════════════════════════════════ */

    public function modulesMarketplace(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        return Response::html(
            $this->kernel->themes()->render('modules_marketplace.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'marketplace'        => $this->buildModMarketplace($request->getQuery()),
                'can_manage_modules' => $this->canManageMods(),
                'category_labels'    => $this->categoryLabels(),
            ]))
        );
    }

    public function modulesMarketplaceDetail(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $modId = (string) $request->getRouteParam('id', '');
        $mod = null;
        foreach ($this->modMarketplaceCatalog() as $entry) {
            if (($entry['id'] ?? '') === $modId) {
                $mod = $entry;
                break;
            }
        }

        if ($mod === null) {
            return Response::notFound('Marketplace-Modul nicht gefunden.');
        }

        $isInstalled = $this->kernel->modules()->isInstalled($modId);

        return Response::html(
            $this->kernel->themes()->render('modules_marketplace_detail.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'mod'                => $mod,
                'is_installed'       => $isInstalled,
                'can_manage_modules' => $this->canManageMods(),
                'category_labels'    => $this->categoryLabels(),
            ]))
        );
    }

    /* ═══════════════════════════════════════════════
     *  Modules – Manager
     * ═══════════════════════════════════════════════ */

    public function modulesManager(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $modules   = $this->kernel->modules()->getInstalled();
        $active    = $this->kernel->modules()->getActive();
        $canManage = $this->canManageMods();

        // Enrich with file-based config info
        $enriched = [];
        foreach ($modules as $id => $manifest) {
            $manifest['_is_active']  = isset($active[$id]);
            $manifest['_has_config'] = file_exists(($manifest['_path'] ?? '') . DIRECTORY_SEPARATOR . 'config.json');
            $enriched[$id] = $manifest;
        }

        return Response::html(
            $this->kernel->themes()->render('modules_manager.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'modules'            => $enriched,
                'can_manage_modules' => $canManage,
            ]))
        );
    }

    public function modulesManagerDetail(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $modId   = (string) $request->getRouteParam('id', '');
        $manifest = $this->kernel->modules()->getManifest($modId);

        if ($manifest === null) {
            return Response::notFound('Modul nicht gefunden.');
        }

        $manifest['_is_active']  = $this->kernel->modules()->isActive($modId);
        $manifest['_has_config'] = file_exists(($manifest['_path'] ?? '') . DIRECTORY_SEPARATOR . 'config.json');

        // Read readme if exists
        $readme = '';
        $readmePath = ($manifest['_path'] ?? '') . DIRECTORY_SEPARATOR . 'README.md';
        if (file_exists($readmePath)) {
            $readme = (string) file_get_contents($readmePath);
        }

        return Response::html(
            $this->kernel->themes()->render('modules_manager_detail.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'mod'                => $manifest,
                'readme'             => $readme,
                'can_manage_modules' => $this->canManageMods(),
            ]))
        );
    }

    public function modulesToggle(Request $request): Response
    {
        $redirect = $this->requirePermission('system.mods');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/modules/manager');
        }

        $modId = (string) $request->getRouteParam('id', '');

        if ($this->kernel->modules()->isActive($modId)) {
            $this->kernel->modules()->deactivate($modId);
            $this->kernel->session()->flash('success', 'Modul wurde deaktiviert.');
        } elseif ($this->kernel->modules()->isInstalled($modId)) {
            $this->kernel->modules()->activate($modId);
            $this->kernel->session()->flash('success', 'Modul wurde aktiviert.');
        } else {
            $this->kernel->session()->flash('danger', 'Modul nicht gefunden.');
        }

        return Response::redirect('/admin/modules/manager');
    }

    public function modulesUninstall(Request $request): Response
    {
        $redirect = $this->requirePermission('system.mods');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/modules/manager');
        }

        $modId    = (string) $request->getRouteParam('id', '');
        $manifest = $this->kernel->modules()->getManifest($modId);

        if ($manifest === null) {
            $this->kernel->session()->flash('danger', 'Modul nicht gefunden.');
            return Response::redirect('/admin/modules/manager');
        }

        // Deactivate first
        if ($this->kernel->modules()->isActive($modId)) {
            $this->kernel->modules()->deactivate($modId);
        }

        // Backup to trash
        $modPath = $manifest['_path'] ?? '';
        $backupDir = $this->kernel->getBasePath() . '/storage/trash/modules';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        $backupPath = $backupDir . '/' . $modId . '_' . date('Ymd_His');
        if (is_dir($modPath)) {
            @rename($modPath, $backupPath);
        }

        $this->kernel->trash()->add(
            'modules',
            'module',
            $modId,
            [
                'name'        => (string) ($manifest['name'] ?? $modId),
                'manifest'    => $manifest,
                'backup_path' => $backupPath,
            ],
            (int) $this->kernel->session()->get('user_id')
        );

        $this->audit('warning', 'module.uninstall', ['user_id' => $this->kernel->session()->get('user_id'), 'module_id' => $modId]);
        $this->kernel->session()->flash('success', 'Modul wurde deinstalliert.');
        return Response::redirect('/admin/modules/manager');
    }

    /* ═══════════════════════════════════════════════
     *  Modules – SDK
     * ═══════════════════════════════════════════════ */

    public function modulesSdk(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        return Response::html(
            $this->kernel->themes()->render('modules_sdk.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'can_manage_modules' => $this->canManageMods(),
            ]))
        );
    }

    /* ═══════════════════════════════════════════════
     *  Modules – Config
     * ═══════════════════════════════════════════════ */

    public function modulesConfig(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $modSettings = $this->getSettingsByGroup('modules');
        $settingsByKey = [];
        foreach ($modSettings as $setting) {
            $settingsByKey[(string) ($setting['key'] ?? '')] = (string) ($setting['value'] ?? '');
        }

        $config = [
            'auto_update'        => ($settingsByKey['modules.auto_update'] ?? '0') === '1',
            'marketplace_enabled'=> ($settingsByKey['modules.marketplace_enabled'] ?? '1') === '1',
            'allow_paid'         => ($settingsByKey['modules.allow_paid'] ?? '1') === '1',
            'sandbox_mode'       => ($settingsByKey['modules.sandbox_mode'] ?? '0') === '1',
        ];

        return Response::html(
            $this->kernel->themes()->render('modules_config.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'can_manage_modules' => $this->canManageMods(),
                'mod_settings'       => $modSettings,
                'mod_config'         => $config,
            ]))
        );
    }

    public function modulesConfigUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('system.mods');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/modules/config');
        }

        $modSettings = $this->getSettingsByGroup('modules');
        $input = $request->getPost('modules', []);
        $values = [
            'modules.auto_update'         => isset($input['auto_update']) ? '1' : '0',
            'modules.marketplace_enabled' => isset($input['marketplace_enabled']) ? '1' : '0',
            'modules.allow_paid'          => isset($input['allow_paid']) ? '1' : '0',
            'modules.sandbox_mode'        => isset($input['sandbox_mode']) ? '1' : '0',
        ];

        $updated = $this->updateSettingsByKey($modSettings, $values);
        if ($updated > 0) {
            $this->kernel->session()->flash('success', 'Modul-Konfiguration wurde gespeichert.');
        } else {
            $this->kernel->session()->flash('info', 'Keine passenden Modul-Einstellungen gefunden oder geändert.');
        }

        return Response::redirect('/admin/modules/config');
    }

    public function modulesModConfig(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $modId    = (string) $request->getRouteParam('id', '');
        $manifest = $this->kernel->modules()->getManifest($modId);

        if ($manifest === null) {
            return Response::notFound('Modul nicht gefunden.');
        }

        $configFile = ($manifest['_path'] ?? '') . DIRECTORY_SEPARATOR . 'config.json';
        $config     = [];
        if (file_exists($configFile)) {
            $raw = (string) file_get_contents($configFile);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }

        return Response::html(
            $this->kernel->themes()->render('modules_mod_config.twig', array_merge($this->baseData(), [
                'current_route'      => 'modules',
                'mod'                => $manifest,
                'mod_config'         => $config,
                'can_manage_modules' => $this->canManageMods(),
            ]))
        );
    }

    public function modulesModConfigUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('system.mods');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/modules/config');
        }

        $modId    = (string) $request->getRouteParam('id', '');
        $manifest = $this->kernel->modules()->getManifest($modId);

        if ($manifest === null) {
            $this->kernel->session()->flash('danger', 'Modul nicht gefunden.');
            return Response::redirect('/admin/modules/config');
        }

        $configFile = ($manifest['_path'] ?? '') . DIRECTORY_SEPARATOR . 'config.json';
        $input      = $request->getPost('config', []);

        if (is_array($input)) {
            @file_put_contents(
                $configFile,
                json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
            $this->kernel->session()->flash('success', 'Modul-Konfiguration wurde gespeichert.');
        }

        return Response::redirect('/admin/modules/config/' . $modId);
    }
}
