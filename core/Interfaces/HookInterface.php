<?php

declare(strict_types=1);

namespace Chamy\Core\Interfaces;

interface HookInterface
{
    public function register(string $hook, callable $callback, int $priority = 10): void;

    public function fire(string $hook, mixed $payload = null): mixed;

    public function getRegistered(): array;
}
