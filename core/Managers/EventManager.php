<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Interfaces\EventDispatcherInterface;

final class EventManager implements ManagerInterface, EventDispatcherInterface
{
    /** @var array<string, array<int, array<callable>>> */
    private array $listeners = [];

    public function getName(): string
    {
        return 'event';
    }

    public function boot(): void
    {
        // Ready to dispatch events
    }

    public function listen(string $event, callable $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;
    }

    public function dispatch(string $event, array $payload = []): array
    {
        if (!isset($this->listeners[$event])) {
            return $payload;
        }

        $listeners = $this->listeners[$event];
        krsort($listeners);

        foreach ($listeners as $priorityGroup) {
            foreach ($priorityGroup as $listener) {
                $result = $listener($payload, $event);

                if (is_array($result)) {
                    $payload = $result;
                }
            }
        }

        return $payload;
    }

    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }

    public function getListeners(?string $event = null): array
    {
        if ($event !== null) {
            return $this->listeners[$event] ?? [];
        }

        return $this->listeners;
    }

    public function removeListeners(string $event): void
    {
        unset($this->listeners[$event]);
    }

    public function getRegisteredEvents(): array
    {
        return array_keys($this->listeners);
    }
}
