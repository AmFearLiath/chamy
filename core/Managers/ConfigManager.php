<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;

final class ConfigManager implements ManagerInterface
{
    private string $basePath;

    /** @var array<string, mixed> */
    private array $config = [];

    private bool $booted = false;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->loadEnvironment();
        $this->loadConfigFiles();
    }

    public function getName(): string
    {
        return 'config';
    }

    public function boot(): void
    {
        $this->booted = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // First: check $_ENV / .env values (uppercased keys)
        $envValue = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($envValue !== false && $envValue !== null) {
            return $envValue;
        }

        // Then: check file-based config (dot notation)
        return $this->dotGet($this->config, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->dotSet($this->config, $key, $value);
    }

    public function all(): array
    {
        return $this->config;
    }

    public function isDebug(): bool
    {
        return $this->get('APP_DEBUG', 'false') === 'true';
    }

    public function getEnvironment(): string
    {
        return (string) $this->get('APP_ENV', 'production');
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    // ------------------------------------------------------------------

    private function loadEnvironment(): void
    {
        // .env is already loaded by Bootstrap / Dotenv, so $_ENV is populated.
    }

    private function loadConfigFiles(): void
    {
        $configPath = $this->basePath . DIRECTORY_SEPARATOR . 'config';

        if (!is_dir($configPath)) {
            return;
        }

        $files = glob($configPath . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $value = require $file;
            if (is_array($value)) {
                $this->config[$key] = $value;
            }
        }
    }

    private function dotGet(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        $segments = explode('.', $key);
        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    private function dotSet(array &$array, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &$array;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }
}
