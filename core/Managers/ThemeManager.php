<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Kernel;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class ThemeManager implements ManagerInterface
{
    private Kernel $kernel;
    private string $adminThemeId;
    private string $frontendThemeId;

    private ?Environment $adminTwig = null;
    private ?Environment $frontendTwig = null;

    /** @var array<string, array> */
    private array $themes = [];

    public function __construct(Kernel $kernel, string $adminThemeId = 'default', string $frontendThemeId = 'default')
    {
        $this->kernel = $kernel;
        $this->adminThemeId = $adminThemeId;
        $this->frontendThemeId = $frontendThemeId;
    }

    public function getName(): string
    {
        return 'theme';
    }

    public function boot(): void
    {
        $this->discoverThemes();
        $this->initAdminTwig();
    }

    public function getAdminTwig(): Environment
    {
        if ($this->adminTwig === null) {
            $this->initAdminTwig();
        }

        return $this->adminTwig;
    }

    public function getFrontendTwig(): Environment
    {
        if ($this->frontendTwig === null) {
            $this->initFrontendTwig();
        }

        return $this->frontendTwig;
    }

    public function render(string $template, array $context = [], string $area = 'admin'): string
    {
        $twig = $area === 'admin' ? $this->getAdminTwig() : $this->getFrontendTwig();
        try {
            return $twig->render($template, $context);
        } catch (\Throwable $e) {
            // Build a readable error page that highlights the most relevant file/path and shows a code excerpt.
            $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $file = $e->getFile();
            $line = $e->getLine();

            $source = '';
            if ($file && is_readable($file)) {
                $lines = @file($file, FILE_IGNORE_NEW_LINES);
                if (is_array($lines)) {
                    $start = max(0, $line - 6);
                    $end = min(count($lines) - 1, $line + 4);
                    $excerpt = [];
                    for ($i = $start; $i <= $end; $i++) {
                        $num = $i + 1;
                        $code = htmlspecialchars($lines[$i] ?? '', ENT_QUOTES, 'UTF-8');
                        if ($num === $line) {
                            $excerpt[] = '<div class="code-line highlight"><span class="ln">' . $num . '</span> ' . $code . '</div>';
                        } else {
                            $excerpt[] = '<div class="code-line"><span class="ln">' . $num . '</span> ' . $code . '</div>';
                        }
                    }
                    $source = '<pre class="code-excerpt">' . implode("\n", $excerpt) . '</pre>';
                }
            }

            $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

            $html = '<!doctype html><html><head><meta charset="utf-8"><title>Chamy Error</title>'
                . '<style>body{font-family:Inter,Segoe UI,Helvetica,Arial,sans-serif;background:#0f1113;color:#e6eef6;padding:24px}'
                . '.container{max-width:1100px;margin:0 auto}h1{margin:0 0 12px;font-size:20px} .muted{color:#9aa3ad}'
                . '.file-path{background:#071014;padding:10px;border-left:6px solid #ffba00;margin:12px 0;font-weight:600}'
                . '.code-excerpt{background:#0b0d0f;padding:12px;border-radius:6px;overflow:auto}'
                . '.code-line{padding:2px 6px;color:#b9c6d0;font-family:monospace;font-size:13px}'
                . '.code-line .ln{display:inline-block;width:48px;color:#54616a;margin-right:12px}'
                . '.code-line.highlight{background:rgba(255,186,0,0.06);border-left:3px solid #ffba00;color:#fff}'
                . '.trace{margin-top:14px;padding:12px;background:#071014;border-radius:6px;color:#aab6bf;font-size:13px;white-space:pre-wrap}'
                . '</style></head><body><div class="container"><h1>Fehler beim Rendern der Vorlage: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '</h1>'
                . '<div class="muted">' . $message . '</div>';

            if ($file) {
                $html .= '<div class="file-path">' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . ' <span class="muted">line ' . (int)$line . '</span></div>';
            }

            $html .= $source;

            $html .= '<h2 style="margin-top:18px;font-size:16px">Stack Trace</h2>';
            $html .= '<div class="trace">' . nl2br($trace) . '</div>';
            $html .= '</div></body></html>';

            return $html;
        }
    }

    public function getAdminThemeId(): string
    {
        return $this->adminThemeId;
    }

    public function getFrontendThemeId(): string
    {
        return $this->frontendThemeId;
    }

    public function getThemes(): array
    {
        return $this->themes;
    }

    public function getTheme(string $area, string $id): ?array
    {
        $key = $area . '/' . $id;
        return $this->themes[$key] ?? null;
    }

    public function themeExists(string $area, string $id): bool
    {
        $key = $area . '/' . $id;
        return isset($this->themes[$key]);
    }

    public function setAdminThemeId(string $id): bool
    {
        $key = 'admin/' . $id;
        if (!isset($this->themes[$key])) {
            return false;
        }
        $this->adminThemeId = $id;
        // Re-init admin twig to pick up templates from new theme
        $this->initAdminTwig();
        return true;
    }

    public function setFrontendThemeId(string $id): bool
    {
        $key = 'frontend/' . $id;
        if (!isset($this->themes[$key])) {
            return false;
        }
        $this->frontendThemeId = $id;
        $this->initFrontendTwig();
        return true;
    }

    public function uninstallTheme(string $area, string $id): array|false
    {
        $key = $area . '/' . $id;
        if (!isset($this->themes[$key])) {
            return false;
        }
        $path = $this->themes[$key]['_path'] ?? '';
        if ($path === '' || !is_dir($path)) {
            return false;
        }

        // Prevent accidental deletes outside themes folders
        $base = $area === 'admin' ? $this->kernel->path('themes', 'admin') : $this->kernel->path('themes', 'frontend');
        $realBase = realpath($base) ?: $base;
        $realPath = realpath($path) ?: $path;
        if (strpos($realPath, $realBase) !== 0) {
            return false;
        }

        $trashBase = $this->kernel->path('storage', 'trash', 'themes');
        if (!is_dir($trashBase)) {
            @mkdir($trashBase, 0755, true);
        }

        $stamp = date('YmdHis');
        $backupPath = $trashBase . DIRECTORY_SEPARATOR . $area . '__' . $id . '__' . $stamp;
        if (!@rename($path, $backupPath)) {
            return false;
        }

        // Refresh discovery
        $this->discoverThemes();
        return [
            'area' => $area,
            'id' => $id,
            'source_path' => $path,
            'backup_path' => $backupPath,
        ];
    }

    public function restoreThemeFromTrash(array $payload): bool
    {
        $area = (string) ($payload['area'] ?? '');
        $id = (string) ($payload['id'] ?? '');
        $backupPath = (string) ($payload['backup_path'] ?? '');
        $sourcePath = (string) ($payload['source_path'] ?? '');

        if ($area === '' || $id === '' || $backupPath === '' || $sourcePath === '') {
            return false;
        }
        if (!is_dir($backupPath)) {
            return false;
        }
        if (is_dir($sourcePath)) {
            return false;
        }

        $ok = @rename($backupPath, $sourcePath);
        if (!$ok) {
            return false;
        }

        $this->discoverThemes();
        return true;
    }

    public function updateThemeManifest(string $area, string $id, array $updates): bool
    {
        $key = $area . '/' . $id;
        if (!isset($this->themes[$key])) {
            return false;
        }

        $themePath = (string) ($this->themes[$key]['_path'] ?? '');
        $manifestPath = $themePath . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($manifestPath) || !is_writable($manifestPath)) {
            return false;
        }

        $raw = file_get_contents($manifestPath);
        if ($raw === false) {
            return false;
        }

        $manifest = json_decode($raw, true);
        if (!is_array($manifest)) {
            return false;
        }

        foreach (['name', 'description', 'author', 'version', 'parent', 'disabled'] as $field) {
            if (array_key_exists($field, $updates)) {
                $manifest[$field] = $updates[$field];
            }
        }

        $ok = @file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($ok === false) {
            return false;
        }

        $this->discoverThemes();
        return true;
    }

    public function createChildTheme(string $area, string $parentId, string $childId, string $name, string $author = '', string $description = ''): array|false
    {
        $parentKey = $area . '/' . $parentId;
        if (!isset($this->themes[$parentKey])) {
            return false;
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $childId)) {
            return false;
        }

        $childKey = $area . '/' . $childId;
        if (isset($this->themes[$childKey])) {
            return false;
        }

        $base = $area === 'admin' ? $this->kernel->path('themes', 'admin') : $this->kernel->path('themes', 'frontend');
        $childPath = $base . DIRECTORY_SEPARATOR . $childId;
        if (is_dir($childPath)) {
            return false;
        }

        @mkdir($childPath . DIRECTORY_SEPARATOR . 'templates', 0755, true);
        @mkdir($childPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css', 0755, true);
        @mkdir($childPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js', 0755, true);

        $manifest = [
            'id' => $childId,
            'name' => $name !== '' ? $name : ('Child of ' . $parentId),
            'description' => $description,
            'author' => $author,
            'version' => '1.0.0',
            'parent' => $parentId,
            'disabled' => false,
        ];

        $ok = @file_put_contents(
            $childPath . DIRECTORY_SEPARATOR . 'theme.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if ($ok === false) {
            return false;
        }

        $this->discoverThemes();
        return $this->themes[$childKey] ?? false;
    }

    public function toggleThemeDisabled(string $area, string $id): ?bool
    {
        $key = $area . '/' . $id;
        if (!isset($this->themes[$key])) {
            return null;
        }
        $manifestPath = ($this->themes[$key]['_path'] ?? '') . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($manifestPath) || !is_writable($manifestPath)) {
            return null;
        }
        $content = file_get_contents($manifestPath);
        if ($content === false) return null;
        $manifest = json_decode($content, true);
        if (!is_array($manifest)) return null;
        $disabled = !(!empty($manifest['disabled']));
        $manifest['disabled'] = $disabled;
        $ok = @file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($ok === false) return null;
        // Refresh discovery so UI reflects change
        $this->discoverThemes();
        return (bool)$disabled;
    }

    public function refresh(): void
    {
        $this->discoverThemes();
    }

    public function getAdminThemePath(): string
    {
        return $this->kernel->path('themes', 'admin', $this->adminThemeId);
    }

    public function getFrontendThemePath(): string
    {
        return $this->kernel->path('themes', 'frontend', $this->frontendThemeId);
    }

    // ------------------------------------------------------------------

    private function discoverThemes(): void
    {
        $areas = [
            'admin'    => $this->kernel->path('themes', 'admin'),
            'frontend' => $this->kernel->path('themes', 'frontend'),
        ];

        foreach ($areas as $area => $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $dirs = glob($basePath . '/*', GLOB_ONLYDIR);
            if ($dirs === false) {
                continue;
            }

            foreach ($dirs as $dir) {
                $manifestFile = $dir . DIRECTORY_SEPARATOR . 'theme.json';

                if (!file_exists($manifestFile)) {
                    continue;
                }

                $content = file_get_contents($manifestFile);
                if ($content === false) {
                    continue;
                }

                $manifest = json_decode($content, true);
                if (is_array($manifest) && !empty($manifest['id'])) {
                    $manifest['_path'] = $dir;
                    $manifest['_area'] = $area;
                    $key = $area . '/' . $manifest['id'];
                    $this->themes[$key] = $manifest;
                }
            }
        }
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        if ($items === false) return;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function initAdminTwig(): void
    {
        $templatePath = $this->getAdminThemePath() . DIRECTORY_SEPARATOR . 'templates';
        if (!is_dir($templatePath)) {
            mkdir($templatePath, 0755, true);
        }

        $paths = [$templatePath];
        $active = $this->getTheme('admin', $this->adminThemeId);
        $parentId = (string) ($active['parent'] ?? '');
        if ($parentId !== '') {
            $parentPath = $this->kernel->path('themes', 'admin', $parentId, 'templates');
            if (is_dir($parentPath)) {
                $paths[] = $parentPath;
            }
        }

        $loader = new FilesystemLoader($paths);
        $this->adminTwig = new Environment($loader, [
            'cache' => $this->kernel->path('storage', 'cache', 'twig'),
            'debug' => $this->kernel->config()->isDebug(),
            'auto_reload' => true,
            'strict_variables' => false,
            'autoescape' => 'html',
        ]);

        $this->registerTwigFunctions($this->adminTwig);
    }

    private function initFrontendTwig(): void
    {
        $templatePath = $this->getFrontendThemePath() . DIRECTORY_SEPARATOR . 'templates';
        if (!is_dir($templatePath)) {
            mkdir($templatePath, 0755, true);
        }

        $paths = [$templatePath];
        $active = $this->getTheme('frontend', $this->frontendThemeId);
        $parentId = (string) ($active['parent'] ?? '');
        if ($parentId !== '') {
            $parentPath = $this->kernel->path('themes', 'frontend', $parentId, 'templates');
            if (is_dir($parentPath)) {
                $paths[] = $parentPath;
            }
        }

        $loader = new FilesystemLoader($paths);
        $this->frontendTwig = new Environment($loader, [
            'cache' => $this->kernel->path('storage', 'cache', 'twig'),
            'debug' => $this->kernel->config()->isDebug(),
            'auto_reload' => true,
            'strict_variables' => false,
            'autoescape' => 'html',
        ]);

        $this->registerTwigFunctions($this->frontendTwig);
    }

    private function registerTwigFunctions(Environment $twig): void
    {
        $kernel = $this->kernel;

        // Translation function
        $twig->addFunction(new TwigFunction('t', function (string $key, array $params = []) use ($kernel): string {
            return $kernel->lang()->t($key, $params);
        }));

        // Hook function
        $twig->addFunction(new TwigFunction('hook', function (string $name, mixed $default = '') use ($kernel): string {
            $result = $kernel->hooks()->fire($name, $default);
            return is_string($result) ? $result : '';
        }, ['is_safe' => ['html']]));

        // Asset URL
        $twig->addFunction(new TwigFunction('asset', function (string $path) use ($kernel): string {
            return '/assets/' . ltrim($path, '/');
        }));

        // Route URL
        $twig->addFunction(new TwigFunction('route', function (string $name, array $params = []) use ($kernel): string {
            return $kernel->getRouter()->url($name, $params);
        }));

        // CSRF token
        $twig->addFunction(new TwigFunction('csrf_token', function () use ($kernel): string {
            return $kernel->session()->getCsrfToken();
        }));

        // CSRF field
        $twig->addFunction(new TwigFunction('csrf_field', function () use ($kernel): string {
            $token = $kernel->session()->getCsrfToken();
            return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        }, ['is_safe' => ['html']]));

        // Config
        $twig->addFunction(new TwigFunction('config', function (string $key, mixed $default = null) use ($kernel): mixed {
            return $kernel->config()->get($key, $default);
        }));
    }
}
