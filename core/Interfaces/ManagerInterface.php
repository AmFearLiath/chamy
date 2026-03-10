<?php

declare(strict_types=1);

namespace Chamy\Core\Interfaces;

interface ManagerInterface
{
    public function getName(): string;

    public function boot(): void;
}
