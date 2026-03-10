<?php

declare(strict_types=1);

namespace Chamy\Core\Routing;

final class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly mixed $handler,
        public readonly string $name = '',
        public readonly array $middleware = [],
        public readonly string $group = ''
    ) {
    }

    public function matches(string $method, string $path): ?array
    {
        if ($this->method !== 'ANY' && $this->method !== $method) {
            return null;
        }

        $regex = $this->compilePattern();

        if (preg_match($regex, $path, $matches)) {
            return array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    private function compilePattern(): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $this->pattern);
        return '#^' . $pattern . '$#';
    }
}
