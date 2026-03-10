<?php

declare(strict_types=1);

namespace Chamy\Core\Http;

final class Response
{
    private int $statusCode;
    private string $body;
    private array $headers;

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function html(string $body, int $statusCode = 200): self
    {
        return new self($body, $statusCode, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $data, int $statusCode = 200): self
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new self($body, $statusCode, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function apiSuccess(mixed $data, array $meta = [], int $statusCode = 200): self
    {
        return self::json([
            'success' => true,
            'data'    => $data,
            'meta'    => $meta,
            'errors'  => [],
        ], $statusCode);
    }

    public static function apiError(string $code, string $message, int $statusCode = 400, array $meta = []): self
    {
        return self::json([
            'success' => false,
            'data'    => null,
            'meta'    => $meta,
            'errors'  => [
                ['code' => $code, 'message' => $message],
            ],
        ], $statusCode);
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return self::apiError('not_found', $message, 404);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::apiError('forbidden', $message, 403);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }
}
