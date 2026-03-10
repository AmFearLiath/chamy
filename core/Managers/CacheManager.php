<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;

final class CacheManager implements ManagerInterface
{
    private string $storagePath;
    private string $driver;
    private string $prefix;

    public function __construct(string $storagePath, string $driver = 'file', string $prefix = 'chamy_cache_')
    {
        $this->storagePath = $storagePath;
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    public function getName(): string
    {
        return 'cache';
    }

    public function boot(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->filePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            $this->forget($key);
            return $default;
        }

        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            $this->forget($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $file = $this->filePath($key);

        $data = serialize([
            'value'   => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ]);

        file_put_contents($file, $data, LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): void
    {
        $file = $this->filePath($key);

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function flush(): void
    {
        $files = glob($this->storagePath . DIRECTORY_SEPARATOR . $this->prefix . '*.cache');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    private function filePath(string $key): string
    {
        $hash = md5($this->prefix . $key);
        return $this->storagePath . DIRECTORY_SEPARATOR . $this->prefix . $hash . '.cache';
    }
}
