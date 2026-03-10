<?php

declare(strict_types=1);

namespace Chamy\Core\Interfaces;

interface EventDispatcherInterface
{
    public function listen(string $event, callable $listener, int $priority = 0): void;

    public function dispatch(string $event, array $payload = []): array;
}
