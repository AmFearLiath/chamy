<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Kernel;

/**
 * MarketplaceManager – Handles discovery, validation and installation
 * of modules and themes from the Chamy Marketplace.
 *
 * MVP: local package import + stub for remote API.
 */
final class MarketplaceManager implements ManagerInterface
{
    private ?Kernel $kernel;

    /** @var array Cached catalog (in production fetched from API) */
    private array $catalog = [];

    public function __construct(?Kernel $kernel = null)
    {
        $this->kernel = $kernel;
    }

    public function getName(): string
    {
        return 'marketplace';
    }

    public function boot(): void
    {
        // In production: fetch catalog from remote API, cache locally
    }

    /* ───────────────────────────────────────────────
     *  Catalog
     * ─────────────────────────────────────────────── */

    /**
     * Get available packages from the marketplace catalog.
     *
     * @param string $type 'module'|'theme'|'all'
     */
    public function getCatalog(string $type = 'all'): array
    {
        if ($type === 'all') {
            return $this->catalog;
        }

        return array_filter($this->catalog, fn(array $p) => ($p['type'] ?? '') === $type);
    }

    public function searchCatalog(string $query): array
    {
        $q = mb_strtolower($query);

        return array_filter($this->catalog, function (array $pkg) use ($q) {
            return str_contains(mb_strtolower($pkg['name'] ?? ''), $q)
                || str_contains(mb_strtolower($pkg['description'] ?? ''), $q)
                || str_contains(mb_strtolower($pkg['id'] ?? ''), $q);
        });
    }

    /* ───────────────────────────────────────────────
     *  Installation
     * ─────────────────────────────────────────────── */

    /**
     * Install a package from a local ZIP archive.
     *
     * @param string $zipPath Path to the zip file
     * @param string $type    'module' or 'theme'
     * @return array{success: bool, message: string, id?: string}
     */
    public function installFromZip(string $zipPath, string $type = 'module'): array
    {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'ZIP-Datei nicht gefunden.'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'ZIP-Archiv konnte nicht geöffnet werden.'];
        }

        // Determine extraction target
        $targetBase = $type === 'theme'
            ? $this->kernel->path('themes')
            : $this->kernel->path('modules');

        // Find manifest to identify the package
        $manifestName = $type === 'theme' ? 'theme.json' : 'manifest.json';
        $manifest = null;
        $rootDir  = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (basename($name) === $manifestName) {
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    $manifest = json_decode($content, true);
                    $rootDir  = dirname($name);
                }
                break;
            }
        }

        if (!$manifest || empty($manifest['id'])) {
            $zip->close();
            return ['success' => false, 'message' => "Kein gültiges {$manifestName} im Archiv gefunden."];
        }

        $packageId = $manifest['id'];
        $targetDir = $targetBase . DIRECTORY_SEPARATOR . $packageId;

        if (is_dir($targetDir)) {
            $zip->close();
            return ['success' => false, 'message' => "Paket '{$packageId}' ist bereits installiert."];
        }

        $zip->extractTo($targetBase);
        $zip->close();

        // If extracted into a different folder name, rename
        if ($rootDir !== '' && $rootDir !== $packageId) {
            $extracted = $targetBase . DIRECTORY_SEPARATOR . $rootDir;
            if (is_dir($extracted)) {
                rename($extracted, $targetDir);
            }
        }

        return [
            'success' => true,
            'message' => "Paket '{$packageId}' wurde installiert.",
            'id'      => $packageId,
        ];
    }

    /**
     * Uninstall a package by removing its directory.
     */
    public function uninstall(string $packageId, string $type = 'module'): array
    {
        $targetBase = $type === 'theme'
            ? $this->kernel->path('themes')
            : $this->kernel->path('modules');

        $targetDir = $targetBase . DIRECTORY_SEPARATOR . $packageId;

        if (!is_dir($targetDir)) {
            return ['success' => false, 'message' => "Paket '{$packageId}' nicht gefunden."];
        }

        $this->removeDirectory($targetDir);

        return [
            'success' => true,
            'message' => "Paket '{$packageId}' wurde deinstalliert.",
        ];
    }

    /* ───────────────────────────────────────────────
     *  Validation
     * ─────────────────────────────────────────────── */

    /**
     * Validate a manifest against required fields.
     */
    public function validateManifest(array $manifest, string $type = 'module'): array
    {
        $errors   = [];
        $required = ['id', 'name', 'version'];

        if ($type === 'module') {
            $required[] = 'entry';
        }

        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                $errors[] = "Pflichtfeld '{$field}' fehlt.";
            }
        }

        if (!empty($manifest['id']) && !preg_match('/^[a-z][a-z0-9_]{1,48}[a-z0-9]$/', $manifest['id'])) {
            $errors[] = "ID '{$manifest['id']}' ist ungültig (nur a-z, 0-9, _ erlaubt, 3-50 Zeichen).";
        }

        return $errors;
    }

    /* ───────────────────────────────────────────────
     *  Helpers
     * ─────────────────────────────────────────────── */

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
