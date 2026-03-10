<?php

declare(strict_types=1);

namespace Chamy\Core\Http;

final class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $query;
    private array $post;
    private array $server;
    private array $headers;
    private array $cookies;
    private ?string $body;

    /** @var array<string, string> */
    private array $routeParams = [];

    private function __construct()
    {
    }

    public static function capture(): self
    {
        $instance = new self();
        $instance->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $instance->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $instance->path = parse_url($instance->uri, PHP_URL_PATH) ?: '/';
        $instance->query = $_GET;
        $instance->post = $_POST;
        $instance->server = $_SERVER;
        $instance->cookies = $_COOKIE;
        $instance->headers = self::parseHeaders();
        $instance->body = null;

        return $instance;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function getPost(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }

        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $this->getJsonBody()[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->getJsonBody());
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function getBody(): string
    {
        if ($this->body === null) {
            $this->body = file_get_contents('php://input') ?: '';
        }

        return $this->body;
    }

    public function getJsonBody(): array
    {
        $body = $this->getBody();

        if ($body === '') {
            return [];
        }

        $contentType = $this->getHeader('content-type', '');
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($body, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function isJson(): bool
    {
        return str_contains($this->getHeader('content-type', ''), 'application/json');
    }

    public function isAjax(): bool
    {
        return $this->getHeader('x-requested-with') === 'XMLHttpRequest';
    }

    public function expectsJson(): bool
    {
        return $this->isJson()
            || $this->isAjax()
            || str_contains($this->getHeader('accept', ''), 'application/json');
    }

    public function getServer(string $key, ?string $default = null): ?string
    {
        return $this->server[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function getRouteParam(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function getClientIp(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ------------------------------------------------------------------

    private static function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
