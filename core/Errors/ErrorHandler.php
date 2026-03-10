<?php

declare(strict_types=1);

namespace Chamy\Core\Errors;

use Throwable;

final class ErrorHandler
{
    private string $basePath;
    private string $logPath;
    private bool $debug = false;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->logPath = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        $this->debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $this->log('error', $message, [
            'level' => $level,
            'file'  => $file,
            'line'  => $line,
        ]);

        if ($this->debug) {
            return false; // Let PHP handle it for display
        }

        return true;
    }

    public function handleException(Throwable $e): void
    {
        $this->log('critical', $e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);

        $isCli = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');

        if ($this->debug) {
            if (!$isCli && !headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo "Chamy Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo $e->getTraceAsString() . "\n";
            return;
        }

        if (!$isCli && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode([
            'success' => false,
            'data'    => null,
            'meta'    => [],
            'errors'  => [
                ['code' => 'internal_error', 'message' => 'An internal error occurred.']
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }

        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $file = $this->logPath . DIRECTORY_SEPARATOR . "chamy-{$date}.log";

        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $line = "[{$time}] {$level}: {$message}{$contextString}" . PHP_EOL;

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
