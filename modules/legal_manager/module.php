<?php

/**
 * ═══════════════════════════════════════════════════════════════════
 *  Legal Manager – Modul-Einstiegspunkt
 *
 *  Wird automatisch vom ModuleManager beim Boot geladen.
 *  Registriert: Migrationen · Berechtigungen · Routen · Hooks
 * ═══════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

use Chamy\Core\Kernel;
use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;

// ─── Kernel & Manager ──────────────────────────────────────────────
$kernel  = Kernel::getInstance();
$router  = $kernel->getRouter();
$hooks   = $kernel->hooks();
$perms   = $kernel->permissions();
$session = $kernel->session();
$lang    = $kernel->lang();

$modulePath = __DIR__;

// ─── Service-Autoloading ───────────────────────────────────────────
require_once $modulePath . '/src/LegalService.php';
require_once $modulePath . '/src/LegalAuditService.php';
require_once $modulePath . '/src/LegalDocumentBuilder.php';

// ─── Datenbank-Migrationen ─────────────────────────────────────────
try {
    $runner = new \Chamy\Core\Database\MigrationRunner($kernel->db());
    $runner->ensureMigrationsTable();
    $runner->run($modulePath . '/migrations');
} catch (\Throwable $e) {
    error_log('[LegalManager] Migration error: ' . $e->getMessage());
}

// ─── Berechtigungen ────────────────────────────────────────────────
$perms->definePermission('legal.view',      $lang->t('legal.perm_view'),     'legal');
$perms->definePermission('legal.manage',    $lang->t('legal.perm_manage'),   'legal');
$perms->definePermission('legal.publish',   $lang->t('legal.perm_publish'),  'legal');
$perms->definePermission('legal.audit.view', $lang->t('legal.perm_audit_view'), 'legal');
$perms->definePermission('legal.audit.run', $lang->t('legal.perm_audit_run'), 'legal');
$perms->definePermission('legal.stats.view', $lang->t('legal.perm_stats_view'), 'legal');
$perms->definePermission('legal.settings',  $lang->t('legal.perm_settings'), 'legal');

// Persist module permissions in DB so they appear in the Roles/Permissions manager UI.
try {
    $permissionTable = $kernel->db()->table('permissions');
    $permissionSeeds = [
        ['key' => 'legal.view', 'description' => $lang->t('legal.perm_view'), 'group' => 'legal'],
        ['key' => 'legal.manage', 'description' => $lang->t('legal.perm_manage'), 'group' => 'legal'],
        ['key' => 'legal.publish', 'description' => $lang->t('legal.perm_publish'), 'group' => 'legal'],
        ['key' => 'legal.audit.view', 'description' => $lang->t('legal.perm_audit_view'), 'group' => 'legal'],
        ['key' => 'legal.audit.run', 'description' => $lang->t('legal.perm_audit_run'), 'group' => 'legal'],
        ['key' => 'legal.stats.view', 'description' => $lang->t('legal.perm_stats_view'), 'group' => 'legal'],
        ['key' => 'legal.settings', 'description' => $lang->t('legal.perm_settings'), 'group' => 'legal'],
    ];

    foreach ($permissionSeeds as $seed) {
        $existing = $kernel->db()->fetchOne("SELECT id FROM {$permissionTable} WHERE `key` = ? LIMIT 1", [$seed['key']]);
        if ($existing === null) {
            $kernel->db()->insert('permissions', [
                'key' => $seed['key'],
                'description' => $seed['description'],
                'group' => $seed['group'],
            ]);
        }
    }
} catch (\Throwable $e) {
    error_log('[LegalManager] Permission seed warning: ' . $e->getMessage());
}

// Grant Legal permissions to privileged roles (e.g. roles that already can manage modules).
$legalPerms = [
    'legal.view',
    'legal.manage',
    'legal.publish',
    'legal.audit.view',
    'legal.audit.run',
    'legal.stats.view',
    'legal.settings',
];

try {
    $roles = $kernel->data()->getRoles();
    foreach ($roles as $role) {
        $roleKey = (string) ($role['key'] ?? '');
        if ($roleKey === '') {
            continue;
        }
        if ($roleKey === 'admin' || $kernel->permissions()->roleHas($roleKey, 'system.mods')) {
            foreach ($legalPerms as $permKey) {
                $kernel->permissions()->grantToRole($roleKey, $permKey);
            }
        }
    }
} catch (\Throwable $e) {
    error_log('[LegalManager] Permission bootstrap warning: ' . $e->getMessage());
}

// ─── Hilfsfunktionen ──────────────────────────────────────────────
$requireAuth = function () use ($session): ?Response {
    if (!$session->get('user_id')) {
        return Response::redirect('/admin/login');
    }
    return null;
};

$requirePerm = function (string $perm) use ($kernel, $session, $requireAuth): ?Response {
    $redirect = $requireAuth();
    if ($redirect) {
        return $redirect;
    }
    $userId = (int) $session->get('user_id');
    $user   = $kernel->data()->getUserById($userId);
    if (!$user || !$kernel->permissions()->userCan($user, $perm)) {
        return Response::html(
            $kernel->themes()->render('errors/403.twig', [
                'message' => $kernel->lang()->t('legal.no_permission'),
            ]),
            403
        );
    }
    return null;
};

$getUser = function () use ($kernel, $session): ?array {
    $userId = (int) $session->get('user_id');
    return $userId ? $kernel->data()->getUserById($userId) : null;
};

$getLocale = function () use ($kernel): string {
    return $kernel->config()->get('APP_LOCALE', 'de');
};

$getService = function () use ($kernel): \LegalManager\LegalService {
    return new \LegalManager\LegalService($kernel->db());
};

$render = function (string $template, array $data = []) use ($kernel, $getUser, $getLocale): Response {
    return Response::html(
        $kernel->themes()->render('legal/' . $template, array_merge([
            'user'       => $getUser(),
            'app_locale' => $getLocale(),
            'flash_messages' => $kernel->session()->getAllFlash(),
        ], $data))
    );
};

// ─── Sidebar-Hook ──────────────────────────────────────────────────
// Register module navigation via MenuManager (preferred) + legacy Hook fallback
$registeredViaMenuManager = false;
try {
    $kernel->menus()->registerModuleNav(
        'legal_manager',
        'module.legal_manager',
        [
            [
                'key' => 'module.legal_manager.dashboard',
                'target_type' => 'route',
                'target_value' => '/admin/legal',
                'icon' => 'shield',
                'permission' => 'legal.view',
                'labels' => ['de' => 'Dashboard', 'en' => 'Dashboard'],
                'sort_order' => 0,
            ],
            [
                'key' => 'module.legal_manager.base_data',
                'target_type' => 'route',
                'target_value' => '/admin/legal/base-data',
                'icon' => 'user',
                'permission' => 'legal.manage',
                'labels' => ['de' => 'Stammdaten', 'en' => 'Base Data'],
                'sort_order' => 10,
            ],
            [
                'key' => 'module.legal_manager.privacy',
                'target_type' => 'route',
                'target_value' => '/admin/legal/privacy',
                'icon' => 'lock',
                'permission' => 'legal.manage',
                'labels' => ['de' => 'Datenschutz', 'en' => 'Privacy'],
                'sort_order' => 20,
            ],
            [
                'key' => 'module.legal_manager.imprint',
                'target_type' => 'route',
                'target_value' => '/admin/legal/imprint',
                'icon' => 'file-text',
                'permission' => 'legal.manage',
                'labels' => ['de' => 'Impressum', 'en' => 'Imprint'],
                'sort_order' => 30,
            ],
            [
                'key' => 'module.legal_manager.consent',
                'target_type' => 'route',
                'target_value' => '/admin/legal/consent',
                'icon' => 'check-circle',
                'permission' => 'legal.manage',
                'labels' => ['de' => 'Einwilligung', 'en' => 'Consent'],
                'sort_order' => 40,
            ],
            [
                'key' => 'module.legal_manager.settings',
                'target_type' => 'route',
                'target_value' => '/admin/legal/settings',
                'icon' => 'settings',
                'permission' => 'legal.settings',
                'labels' => ['de' => 'Einstellungen', 'en' => 'Settings'],
                'sort_order' => 50,
            ],
        ],
        ['de' => 'Legal Manager', 'en' => 'Legal Manager'],
        'shield',
        'legal.view'
    );
    $registeredViaMenuManager = true;
} catch (\Throwable $e) {
    error_log('[LegalManager] MenuManager registration warning: ' . $e->getMessage());
}

// Legacy hook-based sidebar injection has been removed intentionally.
// Modules must register menu entries via MenuManager only.

// Note: Module assets are included directly in the module's admin templates
// (see templates/admin/legal/dashboard.twig) to avoid injecting styles/scripts
// into unrelated admin pages. Global injection via the 'admin.head' hook
// was removed to prevent style leakage into the main menu.


// ═══════════════════════════════════════════════════════════════════
//  ADMIN-ROUTEN
// ═══════════════════════════════════════════════════════════════════

// ─── Dashboard ─────────────────────────────────────────────────────
$router->get('/admin/legal', function () use ($kernel, $requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.view')) {
        return $deny;
    }
    $locale  = $getLocale();
    $service = $getService();

    $config = $service->getConfig(dirname(__FILE__));
    if (!empty($config['auto_create_default_blocks'])) {
        $service->createDefaultPrivacyBlocks($locale);
        $service->createDefaultImprintBlocks($locale);
    }

    $status = $service->getDashboardStatus($locale);
    return $render('dashboard.twig', ['status' => $status, 'locale' => $locale]);
});

// ─── Stammdaten ────────────────────────────────────────────────────
$router->get('/admin/legal/base-data', function () use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $locale = $getLocale();
    $data   = $getService()->getBaseData($locale);
    return $render('base_data.twig', ['base_data' => $data, 'locale' => $locale]);
});

$router->post('/admin/legal/base-data', function () use ($kernel, $requirePerm, $getLocale, $getUser, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/base-data');
    }

    $locale  = $getLocale();
    $user    = $getUser();
    $userId  = (int) ($user['id'] ?? 0);
    $fields  = $request->getPost('fields');

    if (is_array($fields)) {
        $getService()->saveBaseData($fields, $locale, $userId);
    }

    $session->flash('success', $kernel->lang()->t('legal.base_data_saved'));
    return Response::redirect('/admin/legal/base-data');
});

// ─── Datenschutz ───────────────────────────────────────────────────
$router->get('/admin/legal/privacy', function () use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $locale   = $getLocale();
    $service  = $getService();
    $blocks   = $service->getBlocks('privacy', $locale);
    $versions = $service->getDocumentVersions('privacy', $locale);
    return $render('privacy.twig', [
        'blocks'   => $blocks,
        'versions' => $versions,
        'locale'   => $locale,
    ]);
});

$router->get('/admin/legal/privacy/block/new', function () use ($requirePerm, $getLocale, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    return $render('privacy_block_edit.twig', [
        'block'         => null,
        'document_type' => 'privacy',
        'locale'        => $getLocale(),
    ]);
});

$router->get('/admin/legal/privacy/block/{id}', function (Request $request) use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $id = (int) $request->getRouteParam('id');
    $block = $getService()->getBlock($id);
    if (!$block || ($block['document_type'] ?? '') !== 'privacy') {
        return Response::redirect('/admin/legal/privacy');
    }
    return $render('privacy_block_edit.twig', [
        'block'         => $block,
        'document_type' => 'privacy',
        'locale'        => $getLocale(),
    ]);
});

$router->post('/admin/legal/privacy/block', function () use ($kernel, $requirePerm, $getLocale, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/privacy');
    }

    $getService()->saveBlock([
        'id'            => (int) ($request->getPost('id') ?? 0),
        'document_type' => 'privacy',
        'block_key'     => $request->getPost('block_key') ?? '',
        'locale'        => $getLocale(),
        'title'         => $request->getPost('title') ?? '',
        'content'       => $request->getPost('content') ?? '',
        'sort_order'    => (int) ($request->getPost('sort_order') ?? 0),
        'is_active'     => (int) ($request->getPost('is_active') ?? 1),
    ]);

    $session->flash('success', $kernel->lang()->t('legal.block_saved'));
    return Response::redirect('/admin/legal/privacy');
});

$router->post('/admin/legal/privacy/block/{id}/delete', function (Request $request) use ($kernel, $requirePerm, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $id = (int) $request->getRouteParam('id');
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/privacy');
    }
    $getService()->deleteBlock($id);
    $session->flash('success', $kernel->lang()->t('legal.block_deleted'));
    return Response::redirect('/admin/legal/privacy');
});

$router->post('/admin/legal/privacy/reorder', function () use ($kernel, $requirePerm, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        return Response::json(['error' => 'CSRF'], 403);
    }
    $order = $request->getPost('order');
    if (is_array($order)) {
        $getService()->reorderBlocks(array_map('intval', $order));
    }
    return Response::json(['ok' => true]);
});

$router->post('/admin/legal/privacy/publish', function () use ($kernel, $requirePerm, $getLocale, $getUser, $getService) {
    if ($deny = $requirePerm('legal.publish')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/privacy');
    }

    $locale  = $getLocale();
    $user    = $getUser();
    $note    = trim((string) ($request->getPost('change_note') ?? ''));
    $service = $getService();
    $builder = new \LegalManager\LegalDocumentBuilder($service);

    $t = fn(string $key) => $kernel->lang()->t($key);
    $html    = $builder->buildSnapshot('privacy', $locale, $t);
    $version = $service->publishDocument('privacy', $locale, (int) ($user['id'] ?? 0), $note, $html);

    $session->flash('success', $kernel->lang()->t('legal.publish_success') . ' (v' . $version . ')');
    return Response::redirect('/admin/legal/privacy');
});

// ─── Impressum ─────────────────────────────────────────────────────
$router->get('/admin/legal/imprint', function () use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $locale   = $getLocale();
    $service  = $getService();
    $blocks   = $service->getBlocks('imprint', $locale);
    $versions = $service->getDocumentVersions('imprint', $locale);
    return $render('imprint.twig', [
        'blocks'   => $blocks,
        'versions' => $versions,
        'locale'   => $locale,
    ]);
});

$router->get('/admin/legal/imprint/block/new', function () use ($requirePerm, $getLocale, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    return $render('imprint_block_edit.twig', [
        'block'         => null,
        'document_type' => 'imprint',
        'locale'        => $getLocale(),
    ]);
});

$router->get('/admin/legal/imprint/block/{id}', function (Request $request) use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $id = (int) $request->getRouteParam('id');
    $block = $getService()->getBlock($id);
    if (!$block || ($block['document_type'] ?? '') !== 'imprint') {
        return Response::redirect('/admin/legal/imprint');
    }
    return $render('imprint_block_edit.twig', [
        'block'         => $block,
        'document_type' => 'imprint',
        'locale'        => $getLocale(),
    ]);
});

$router->post('/admin/legal/imprint/block', function () use ($kernel, $requirePerm, $getLocale, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/imprint');
    }

    $getService()->saveBlock([
        'id'            => (int) ($request->getPost('id') ?? 0),
        'document_type' => 'imprint',
        'block_key'     => $request->getPost('block_key') ?? '',
        'locale'        => $getLocale(),
        'title'         => $request->getPost('title') ?? '',
        'content'       => $request->getPost('content') ?? '',
        'sort_order'    => (int) ($request->getPost('sort_order') ?? 0),
        'is_active'     => (int) ($request->getPost('is_active') ?? 1),
    ]);

    $session->flash('success', $kernel->lang()->t('legal.block_saved'));
    return Response::redirect('/admin/legal/imprint');
});

$router->post('/admin/legal/imprint/block/{id}/delete', function (Request $request) use ($kernel, $requirePerm, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $id = (int) $request->getRouteParam('id');
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/imprint');
    }
    $getService()->deleteBlock($id);
    $session->flash('success', $kernel->lang()->t('legal.block_deleted'));
    return Response::redirect('/admin/legal/imprint');
});

$router->post('/admin/legal/imprint/reorder', function () use ($kernel, $requirePerm, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        return Response::json(['error' => 'CSRF'], 403);
    }
    $order = $request->getPost('order');
    if (is_array($order)) {
        $getService()->reorderBlocks(array_map('intval', $order));
    }
    return Response::json(['ok' => true]);
});

$router->post('/admin/legal/imprint/publish', function () use ($kernel, $requirePerm, $getLocale, $getUser, $getService) {
    if ($deny = $requirePerm('legal.publish')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/imprint');
    }

    $locale  = $getLocale();
    $user    = $getUser();
    $note    = trim((string) ($request->getPost('change_note') ?? ''));
    $service = $getService();
    $builder = new \LegalManager\LegalDocumentBuilder($service);

    $t = fn(string $key) => $kernel->lang()->t($key);
    $html    = $builder->buildSnapshot('imprint', $locale, $t);
    $version = $service->publishDocument('imprint', $locale, (int) ($user['id'] ?? 0), $note, $html);

    $session->flash('success', $kernel->lang()->t('legal.publish_success') . ' (v' . $version . ')');
    return Response::redirect('/admin/legal/imprint');
});

// ─── Consent / Cookies ─────────────────────────────────────────────
$router->get('/admin/legal/consent', function () use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $locale     = $getLocale();
    $categories = $getService()->getConsentCategories($locale);
    return $render('consent.twig', ['categories' => $categories, 'locale' => $locale]);
});

$router->post('/admin/legal/consent', function () use ($kernel, $requirePerm, $getLocale, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/consent');
    }

    $getService()->saveConsentCategory([
        'id'           => (int) ($request->getPost('id') ?? 0),
        'category_key' => $request->getPost('category_key') ?? '',
        'label'        => $request->getPost('label') ?? '',
        'description'  => $request->getPost('description') ?? '',
        'is_required'  => (int) ($request->getPost('is_required') ?? 0),
        'sort_order'   => (int) ($request->getPost('sort_order') ?? 0),
        'is_active'    => (int) ($request->getPost('is_active') ?? 1),
        'locale'       => $getLocale(),
    ]);

    $session->flash('success', $kernel->lang()->t('legal.category_saved'));
    return Response::redirect('/admin/legal/consent');
});

$router->post('/admin/legal/consent/{id}/delete', function (Request $request) use ($kernel, $requirePerm, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $id = (int) $request->getRouteParam('id');
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/consent');
    }
    $getService()->deleteConsentCategory($id);
    $session->flash('success', $kernel->lang()->t('legal.category_deleted'));
    return Response::redirect('/admin/legal/consent');
});

// ─── Externe Dienste ───────────────────────────────────────────────
$router->get('/admin/legal/services', function () use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $locale   = $getLocale();
    $services = $getService()->getServices($locale);
    return $render('services.twig', ['services' => $services, 'locale' => $locale]);
});

$router->post('/admin/legal/services', function () use ($kernel, $requirePerm, $getLocale, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/services');
    }

    $getService()->saveService([
        'id'               => (int) ($request->getPost('id') ?? 0),
        'name'             => $request->getPost('name') ?? '',
        'provider'         => $request->getPost('provider') ?? '',
        'category'         => $request->getPost('category') ?? 'other',
        'purpose'          => $request->getPost('purpose') ?? '',
        'data_collected'   => $request->getPost('data_collected') ?? '',
        'privacy_url'      => $request->getPost('privacy_url') ?? '',
        'consent_required' => (int) ($request->getPost('consent_required') ?? 1),
        'is_active'        => (int) ($request->getPost('is_active') ?? 1),
        'locale'           => $getLocale(),
    ]);

    $session->flash('success', $kernel->lang()->t('legal.service_saved'));
    return Response::redirect('/admin/legal/services');
});

$router->post('/admin/legal/services/{id}/delete', function (Request $request) use ($kernel, $requirePerm, $getService) {
    if ($deny = $requirePerm('legal.manage')) {
        return $deny;
    }
    $id = (int) $request->getRouteParam('id');
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/services');
    }
    $getService()->deleteService($id);
    $session->flash('success', $kernel->lang()->t('legal.service_deleted'));
    return Response::redirect('/admin/legal/services');
});

// ─── Audit ─────────────────────────────────────────────────────────
$router->get('/admin/legal/audit', function () use ($kernel, $requirePerm, $getLocale, $render) {
    if ($deny = $requirePerm('legal.audit.view')) {
        return $deny;
    }
    $auditService = new \LegalManager\LegalAuditService($kernel->db(), dirname(__DIR__, 2));
    $lastAudit    = $auditService->getLastAuditResults();
    return $render('audit.twig', ['audit' => $lastAudit, 'locale' => $getLocale()]);
});

$router->post('/admin/legal/audit/run', function () use ($kernel, $requirePerm, $getLocale) {
    if ($deny = $requirePerm('legal.audit.run')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/audit');
    }
    $auditService = new \LegalManager\LegalAuditService($kernel->db(), dirname(__DIR__, 2));
    $auditService->runAudit($getLocale());
    $session->flash('success', $kernel->lang()->t('legal.audit_complete'));
    return Response::redirect('/admin/legal/audit');
});

// ─── Statistik ─────────────────────────────────────────────────────
$router->get('/admin/legal/stats', function () use ($requirePerm, $getLocale, $getService, $render) {
    if ($deny = $requirePerm('legal.stats.view')) {
        return $deny;
    }
    $request = Request::capture();
    $days    = max(7, min(365, (int) ($request->getQuery('days') ?? 30)));
    $stats   = $getService()->getStats($days);
    return $render('stats.twig', ['stats' => $stats, 'days' => $days, 'locale' => $getLocale()]);
});

// ─── Einstellungen ─────────────────────────────────────────────────
$router->get('/admin/legal/settings', function () use ($requirePerm, $getLocale, $getService, $render, $modulePath) {
    if ($deny = $requirePerm('legal.settings')) {
        return $deny;
    }
    $config = $getService()->getConfig($modulePath);
    return $render('settings.twig', ['config' => $config, 'locale' => $getLocale()]);
});

$router->post('/admin/legal/settings', function () use ($kernel, $requirePerm, $getService, $modulePath) {
    if ($deny = $requirePerm('legal.settings')) {
        return $deny;
    }
    $request = Request::capture();
    $session = $kernel->session();
    if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
        $session->flash('error', $kernel->lang()->t('legal.error_invalid_request'));
        return Response::redirect('/admin/legal/settings');
    }

    $service    = $getService();
    $oldConfig  = $service->getConfig($modulePath);
    $newConfig  = array_merge($oldConfig, [
        'privacy_slug'               => trim((string) ($request->getPost('privacy_slug') ?? 'datenschutz')),
        'imprint_slug'               => trim((string) ($request->getPost('imprint_slug') ?? 'impressum')),
        'auto_create_default_blocks' => (bool) ($request->getPost('auto_create_default_blocks') ?? false),
        'stats_enabled'              => (bool) ($request->getPost('stats_enabled') ?? false),
        'stats_anonymize_ip'         => (bool) ($request->getPost('stats_anonymize_ip') ?? true),
        'consent_management_enabled' => (bool) ($request->getPost('consent_management_enabled') ?? false),
        'audit_on_publish'           => (bool) ($request->getPost('audit_on_publish') ?? false),
        'default_locale'             => trim((string) ($request->getPost('default_locale') ?? 'de')),
        'frontend_page_enabled'      => (bool) ($request->getPost('frontend_page_enabled') ?? true),
    ]);

    $service->saveConfig($modulePath, $newConfig);
    $session->flash('success', $kernel->lang()->t('legal.settings_saved'));
    return Response::redirect('/admin/legal/settings');
});


// ═══════════════════════════════════════════════════════════════════
//  FRONTEND-ROUTEN
// ═══════════════════════════════════════════════════════════════════

$config       = (new \LegalManager\LegalService($kernel->db()))->getConfig($modulePath);
$privacySlug  = trim((string) ($config['privacy_slug'] ?? 'datenschutz'));
$imprintSlug  = trim((string) ($config['imprint_slug'] ?? 'impressum'));
$frontendOn   = (bool) ($config['frontend_page_enabled'] ?? true);
$statsEnabled = (bool) ($config['stats_enabled'] ?? false);
$anonymize    = (bool) ($config['stats_anonymize_ip'] ?? true);

if ($frontendOn) {
    $router->get('/' . $privacySlug, function () use ($kernel, $getLocale, $getService, $statsEnabled, $anonymize, $modulePath) {
        $locale  = $getLocale();
        $service = $getService();
        $builder = new \LegalManager\LegalDocumentBuilder($service);

        $t    = fn(string $key) => $kernel->lang()->t($key);
        $html = $builder->buildPrivacyPage($locale, $t);

        $published = $service->getPublishedDocument('privacy', $locale);

        if ($statsEnabled) {
            $service->recordStat('privacy', 'view', $locale, $anonymize);
        }

        return Response::html(
            $kernel->themes()->render('legal/frontend_privacy.twig', [
                'content'       => $html,
                'published'     => $published,
                'page_title'    => $kernel->lang()->t('legal.frontend_privacy_title'),
                'app_locale'    => $locale,
                'last_updated'  => $published ? ($published['published_at'] ?? null) : null,
            ], 'frontend')
        );
    });

    $router->get('/' . $imprintSlug, function () use ($kernel, $getLocale, $getService, $statsEnabled, $anonymize, $modulePath) {
        $locale  = $getLocale();
        $service = $getService();
        $builder = new \LegalManager\LegalDocumentBuilder($service);

        $t    = fn(string $key) => $kernel->lang()->t($key);
        $html = $builder->buildImprintPage($locale, $t);

        $published = $service->getPublishedDocument('imprint', $locale);

        if ($statsEnabled) {
            $service->recordStat('imprint', 'view', $locale, $anonymize);
        }

        return Response::html(
            $kernel->themes()->render('legal/frontend_imprint.twig', [
                'content'       => $html,
                'published'     => $published,
                'page_title'    => $kernel->lang()->t('legal.frontend_imprint_title'),
                'app_locale'    => $locale,
                'last_updated'  => $published ? ($published['published_at'] ?? null) : null,
            ], 'frontend')
        );
    });
}

