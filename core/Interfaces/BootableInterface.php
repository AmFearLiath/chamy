<?php

declare(strict_types=1);

namespace Chamy\Core\Interfaces;

interface BootableInterface
{
    public function boot(): void;

    public function isBooted(): bool;
}
