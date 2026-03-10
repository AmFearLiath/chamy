<?php

declare(strict_types=1);

namespace Chamy\Core;

use Chamy\Core\Interfaces\ManagerInterface;
use RuntimeException;

final class ManagerRegistry
{
    /** @var array<string, ManagerInterface> */
    private array $managers = [];

    /** @var array<string, bool> */
    private array $booted = [];

    public function register(string $name, ManagerInterface $manager): void
    {
        if (isset($this->managers[$name])) {
            throw new RuntimeException("Manager '{$name}' is already registered.");
        }

        $this->managers[$name] = $manager;
        $this->booted[$name] = false;
    }

    public function get(string $name): ManagerInterface
    {
        if (!isset($this->managers[$name])) {
            throw new RuntimeException("Manager '{$name}' is not registered.");
        }

        return $this->managers[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->managers[$name]);
    }

    public function boot(string $name): void
    {
        if (!isset($this->managers[$name])) {
            throw new RuntimeException("Manager '{$name}' is not registered.");
        }

        if ($this->booted[$name]) {
            return;
        }

        $this->managers[$name]->boot();
        $this->booted[$name] = true;
    }

    public function bootAll(): void
    {
        foreach ($this->managers as $name => $manager) {
            $this->boot($name);
        }
    }

    public function isBooted(string $name): bool
    {
        return $this->booted[$name] ?? false;
    }

    /** @return array<string, ManagerInterface> */
    public function all(): array
    {
        return $this->managers;
    }
}
