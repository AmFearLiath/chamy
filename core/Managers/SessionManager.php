<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;

final class SessionManager implements ManagerInterface
{
    private string $name;
    private int $lifetime;
    private bool $started = false;

    public function __construct(string $name = 'chamy_session', int $lifetime = 120)
    {
        $this->name = $name;
        $this->lifetime = $lifetime;
    }

    public function getName(): string
    {
        return 'session';
    }

    public function boot(): void
    {
        if ($this->started || php_sapi_name() === 'cli') {
            return;
        }

        $this->start();
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $this->lifetime * 60,
            'path'     => $cookieParams['path'],
            'domain'   => $cookieParams['domain'],
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly'  => true,
            'samesite'  => 'Lax',
        ]);

        session_name($this->name);
        session_start();

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function getAllFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        return $messages;
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
        $this->started = false;
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function getId(): string
    {
        return session_id() ?: '';
    }

    public function getCsrfToken(): string
    {
        if (!$this->has('_csrf_token')) {
            $this->set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return (string) $this->get('_csrf_token');
    }

    public function verifyCsrfToken(string $token): bool
    {
        return hash_equals($this->getCsrfToken(), $token);
    }
}
