<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Interfaces\HookInterface;

final class HookManager implements ManagerInterface, HookInterface
{
    private EventManager $events;

    /** @var array<string, array{description: string, source: string}> */
    private array $registry = [];

    public function __construct(EventManager $events)
    {
        $this->events = $events;
    }

    public function getName(): string
    {
        return 'hook';
    }

    public function boot(): void
    {
        // Hooks ready
    }

    public function define(string $hook, string $description = '', string $source = 'system'): void
    {
        $this->registry[$hook] = [
            'description' => $description,
            'source'      => $source,
        ];
    }

    public function register(string $hook, callable $callback, int $priority = 10): void
    {
        $this->events->listen('hook.' . $hook, $callback, $priority);
    }

    public function fire(string $hook, mixed $payload = null): mixed
    {
        $wrapped = ['value' => $payload];
        $result = $this->events->dispatch('hook.' . $hook, $wrapped);

        return $result['value'] ?? $payload;
    }

    public function fireArray(string $hook, array $payload = []): array
    {
        return $this->events->dispatch('hook.' . $hook, $payload);
    }

    public function getRegistered(): array
    {
        return $this->registry;
    }

    public function isDefined(string $hook): bool
    {
        return isset($this->registry[$hook]);
    }

    public function hasCallbacks(string $hook): bool
    {
        return $this->events->hasListeners('hook.' . $hook);
    }
}
