<?php

declare(strict_types=1);

namespace Chamy\Core\Interfaces;

interface ConfigurableInterface
{
    public function getConfig(string $key, mixed $default = null): mixed;

    public function setConfig(string $key, mixed $value): void;
}
