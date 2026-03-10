<?php

declare(strict_types=1);

namespace Chamy\Core;

use Dotenv\Dotenv;

final class Bootstrap
{
    public static function init(string $basePath): Kernel
    {
        $basePath = rtrim($basePath, '/\\');

        // Autoloader
        require_once $basePath . '/vendor/autoload.php';

        // Environment
        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->load();
        }

        // Timezone
        date_default_timezone_set('Europe/Berlin');

        // Internal encoding
        mb_internal_encoding('UTF-8');

        // Kernel
        $kernel = Kernel::create($basePath);
        $kernel->boot();

        return $kernel;
    }
}
