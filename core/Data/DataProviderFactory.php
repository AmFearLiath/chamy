<?php

declare(strict_types=1);

namespace Chamy\Core\Data;

use Chamy\Core\Database\Connection;

/**
 * Factory – Erzeugt je nach DATA_SOURCE-Einstellung den passenden DataProvider.
 */
final class DataProviderFactory
{
    public static function create(string $source, string $basePath, ?Connection $db = null): DataProviderInterface
    {
        return match ($source) {
            'live'  => new LiveDataProvider($db),
            default => new MockDataProvider($basePath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'mock'),
        };
    }
}
