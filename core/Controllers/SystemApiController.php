<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;

final class SystemApiController extends BaseApiController
{
    public function info(Request $request): Response
    {
        return $this->success([
            'name'        => $this->kernel->config()->get('APP_NAME', 'Chamy'),
            'version'     => '1.0.0',
            'locale'      => $this->kernel->config()->get('APP_LOCALE', 'de'),
            'environment' => $this->kernel->config()->getEnvironment(),
            'php_version' => PHP_VERSION,
        ]);
    }

    public function health(Request $request): Response
    {
        $checks = [];

        // Database
        try {
            $this->kernel->db()->fetchOne("SELECT 1 as ok");
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
        }

        // Cache directory
        $cachePath = $this->kernel->path('storage', 'cache');
        $checks['cache'] = is_writable($cachePath) ? 'ok' : 'error';

        // Logs directory
        $logsPath = $this->kernel->path('storage', 'logs');
        $checks['logs'] = is_writable($logsPath) ? 'ok' : 'error';

        $allOk = !in_array('error', $checks, true);

        return Response::json([
            'success' => true,
            'status'  => $allOk ? 'healthy' : 'degraded',
            'checks'  => $checks,
        ], $allOk ? 200 : 503);
    }

    public function contentTypes(Request $request): Response
    {
        $types = $this->kernel->contentTypes()->getAllTypes();

        $result = [];
        foreach ($types as $key => $type) {
            $result[] = [
                'id'           => $key,
                'label'        => $type['label'] ?? $key,
                'label_plural' => $type['label_plural'] ?? $key,
                'group'        => $type['group'] ?? 'content',
                'icon'         => $type['icon'] ?? null,
                'fields'       => array_map(fn($f) => [
                    'name'     => $f['name'] ?? '',
                    'type'     => $f['type'] ?? 'text',
                    'label'    => $f['label'] ?? '',
                    'required' => $f['required'] ?? false,
                ], $type['fields'] ?? []),
            ];
        }

        return $this->success($result);
    }

    public function states(Request $request): Response
    {
        $states = $this->kernel->states();

        return $this->success([
            'states'      => $states->getAllStates(),
            'transitions' => $states->getAllTransitions(),
        ]);
    }

    public function users(Request $request): Response
    {
        $db     = $this->kernel->db();
        $prefix = $db->getPrefix();

        $users = $db->fetchAll(
            "SELECT id, username, email, role, created_at FROM {$prefix}users ORDER BY id"
        );

        return $this->success($users);
    }

    public function modules(Request $request): Response
    {
        $moduleManager = $this->kernel->modules();
        $modules = $moduleManager->getAll();

        $result = [];
        foreach ($modules as $key => $module) {
            $result[] = [
                'id'          => $key,
                'name'        => $module['name'] ?? $key,
                'version'     => $module['version'] ?? '0.0.0',
                'description' => $module['description'] ?? '',
                'active'      => $module['active'] ?? false,
            ];
        }

        return $this->success($result);
    }

    public function themes(Request $request): Response
    {
        $themeManager = $this->kernel->themes();

        return $this->success([
            'admin'    => $themeManager->getAdminThemes(),
            'frontend' => $themeManager->getFrontendThemes(),
        ]);
    }
}
