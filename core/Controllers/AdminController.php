<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

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

        return [
            'user'          => $this->currentUser(),
            'content_types' => $types,
            'app_locale'    => $this->kernel->config()->get('APP_LOCALE', 'de'),
            'app_version'   => '1.0.0',
            'php_version'   => PHP_VERSION,
            'current_theme' => 'Neon Dark',
            'flash_messages'=> $this->kernel->session()->getAllFlash(),
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

    public function settingsPage(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

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

        return Response::html(
            $this->kernel->themes()->render('settings.twig', array_merge($this->baseData(), [
                'current_route' => 'settings',
                'settings'      => $ordered,
                'can_manage_settings' => $canManageSettings,
            ]))
        );
    }

    public function settingsUpdate(Request $request): Response
    {
        $redirect = $this->requirePermission('system.manage');
        if ($redirect) return $redirect;

        if (!$this->kernel->session()->verifyCsrfToken($request->getPost('csrf_token', ''))) {
            $this->kernel->session()->flash('danger', 'Ungültige Anfrage.');
            return Response::redirect('/admin/settings');
        }

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
     *  Modules
     * ─────────────────────────────────────────────── */

    public function modulesList(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect) return $redirect;

        $modules   = $this->kernel->modules()->getInstalled();
        $active    = $this->kernel->modules()->getActive();
        $user = $this->currentUser();
        $canManageModules = $user !== null
            && $this->kernel->permissions()->userCan($user, 'system.mods');

        return Response::html(
            $this->kernel->themes()->render('modules.twig', array_merge($this->baseData(), [
                'current_route' => 'modules',
                'modules'       => $modules,
                'active'        => $active,
                'can_manage_modules' => $canManageModules,
            ]))
        );
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

        $ok = false;
        if ($area === 'admin') {
            $ok = $this->kernel->themes()->setAdminThemeId($id);
        } else {
            $ok = $this->kernel->themes()->setFrontendThemeId($id);
        }

        // Persist to appearance settings if possible
        $settings = $this->getSettingsByGroup('appearance');
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

        return Response::html(
            $this->kernel->themes()->render('trash.twig', array_merge($this->baseData(), [
                'current_route' => 'trash',
                'trash_items' => $this->kernel->trash()->list($filters),
                'trash_stats' => $this->kernel->trash()->stats(),
                'trash_filters' => $filters,
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
}
