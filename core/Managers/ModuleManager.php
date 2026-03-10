<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Kernel;
use RuntimeException;

final class ModuleManager implements ManagerInterface
{
    private Kernel $kernel;

    /** @var array<string, array> */
    private array $installed = [];

    /** @var array<string, array> */
    private array $active = [];

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getName(): string
    {
        return 'module';
    }

    public function boot(): void
    {
        $this->discoverModules();
        $this->bootActiveModules();
    }

    public function getInstalled(): array
    {
        return $this->installed;
    }

    public function getActive(): array
    {
        return $this->active;
    }

    public function isInstalled(string $moduleId): bool
    {
        return isset($this->installed[$moduleId]);
    }

    public function isActive(string $moduleId): bool
    {
        return isset($this->active[$moduleId]);
    }

    public function getManifest(string $moduleId): ?array
    {
        return $this->installed[$moduleId] ?? null;
    }

    public function activate(string $moduleId): void
    {
        if (!$this->isInstalled($moduleId)) {
            throw new RuntimeException("Module '{$moduleId}' is not installed.");
        }

        $this->active[$moduleId] = $this->installed[$moduleId];
        $this->kernel->events()->dispatch('module.activated', ['module_id' => $moduleId]);
    }

    public function deactivate(string $moduleId): void
    {
        if (!$this->isActive($moduleId)) {
            return;
        }

        unset($this->active[$moduleId]);
        $this->kernel->events()->dispatch('module.deactivated', ['module_id' => $moduleId]);
    }

    public function getModulePath(string $moduleId): string
    {
        return $this->kernel->path('modules', $moduleId);
    }

    // ------------------------------------------------------------------

    private function discoverModules(): void
    {
        $modulesPath = $this->kernel->path('modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        $dirs = glob($modulesPath . '/*', GLOB_ONLYDIR);
        if ($dirs === false) {
            return;
        }

        foreach ($dirs as $dir) {
            $manifestFile = $dir . DIRECTORY_SEPARATOR . 'manifest.json';

            if (!file_exists($manifestFile)) {
                continue;
            }

            $content = file_get_contents($manifestFile);
            if ($content === false) {
                continue;
            }

            $manifest = json_decode($content, true);

            if (!is_array($manifest) || empty($manifest['id'])) {
                continue;
            }

            $manifest['_path'] = $dir;
            $this->installed[$manifest['id']] = $manifest;
        }
    }

    private function bootActiveModules(): void
    {
        // For MVP: all installed modules are active
        $this->active = $this->installed;

        foreach ($this->active as $moduleId => $manifest) {
            $this->bootModule($moduleId, $manifest);
        }
    }

    private function bootModule(string $moduleId, array $manifest): void
    {
        $modulePath = $manifest['_path'];
        $entry = $manifest['entry'] ?? 'module.php';
        $entryFile = $modulePath . DIRECTORY_SEPARATOR . $entry;

        if (!file_exists($entryFile)) {
            return;
        }

        // Load language files
        $langPath = $modulePath . DIRECTORY_SEPARATOR . 'languages';
        if (is_dir($langPath)) {
            $locale = $this->kernel->lang()->getLocale();
            $fallback = $this->kernel->lang()->getFallback();

            foreach ([$locale, $fallback] as $lang) {
                $langFile = $langPath . DIRECTORY_SEPARATOR . $lang . '.php';
                if (file_exists($langFile)) {
                    $this->kernel->lang()->loadFile($lang, $langFile);
                }
            }
        }

        // Load content types
        $ctPath = $modulePath . DIRECTORY_SEPARATOR . 'content-types';
        if (is_dir($ctPath)) {
            $files = glob($ctPath . '/*.php');
            if ($files) {
                foreach ($files as $ctFile) {
                    $definition = require $ctFile;
                    if (is_array($definition)) {
                        $this->kernel->contentTypes()->registerType($definition);
                    }
                }
            }
        }

        // Execute module entry
        $bootstrap = require $entryFile;

        if (is_callable($bootstrap)) {
            $bootstrap($this->kernel);
        }

        $this->kernel->events()->dispatch('module.booted', ['module_id' => $moduleId]);
    }
}
