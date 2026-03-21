<?php

declare(strict_types=1);

/**
 * Legal Manager – Audit-Service
 *
 * Durchsucht Templates und Assets nach externen Ressourcen (URLs),
 * vergleicht sie mit den deklarierten Diensten und speichert
 * Prüfergebnisse für den Audit-Bericht.
 */

namespace LegalManager;

use Chamy\Core\Database\Connection;

final class LegalAuditService
{
    private Connection $db;
    private string $projectRoot;

    public function __construct(Connection $db, string $projectRoot)
    {
        $this->db = $db;
        $this->projectRoot = rtrim($projectRoot, '/\\');
    }

    /* ----------------------------------------------------------------
     *  Audit ausführen
     * ---------------------------------------------------------------- */

    /**
     * Vollständigen Audit-Scan durchführen.
     *
     * 1. Template- und Asset-Dateien sammeln
     * 2. Externe URLs extrahieren
     * 3. Mit deklarierten Diensten abgleichen
     * 4. Ergebnisse speichern
     */
    public function runAudit(string $locale = 'de'): array
    {
        $scanId = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $table  = $this->db->table('legal_audit_results');

        // 1. Dateien sammeln
        $files = $this->collectFiles();

        // 2. URLs extrahieren
        $foundResources = [];
        foreach ($files as $fileInfo) {
            $urls = $this->extractExternalUrls($fileInfo['path']);
            foreach ($urls as $url) {
                $foundResources[] = [
                    'url'         => $url,
                    'source_file' => $fileInfo['relative'],
                    'source_area' => $fileInfo['area'],
                ];
            }
        }

        // 3. Deklarierte Dienste laden
        $declaredDomains = $this->getDeclaredDomains($locale);

        // 4. Ergebnisse bewerten und speichern
        $results = [];
        foreach ($foundResources as $resource) {
            $domain     = $this->extractDomain($resource['url']);
            $isExternal = !$this->isInternalDomain($domain);
            $isDeclared = $isExternal ? $this->isDomainDeclared($domain, $declaredDomains) : true;

            $severity = 'info';
            if ($isExternal && !$isDeclared) {
                $severity = 'critical';
            } elseif ($isExternal && $isDeclared) {
                $severity = 'info';
            }

            $message = $isDeclared
                ? ($isExternal ? "Externer Dienst deklariert: {$domain}" : "Intern: {$domain}")
                : "Externes Laden von {$domain} – nicht als Dienst deklariert!";

            $row = [
                'scan_id'       => $scanId,
                'resource_type' => $this->guessResourceType($resource['url']),
                'resource_url'  => mb_substr($resource['url'], 0, 500),
                'is_external'   => $isExternal ? 1 : 0,
                'is_declared'   => $isDeclared ? 1 : 0,
                'severity'      => $severity,
                'message'       => $message,
                'source_file'   => mb_substr($resource['source_file'], 0, 255),
                'source_area'   => mb_substr($resource['source_area'], 0, 100),
            ];

            $this->db->query(
                "INSERT INTO {$table} (scan_id, resource_type, resource_url, is_external, is_declared, severity, message, source_file, source_area)
                 VALUES (:scan_id, :resource_type, :resource_url, :is_external, :is_declared, :severity, :message, :source_file, :source_area)",
                $row
            );

            $results[] = $row;
        }

        return [
            'scan_id'      => $scanId,
            'total'        => count($results),
            'external'     => count(array_filter($results, fn($r) => (int) $r['is_external'] === 1)),
            'undeclared'   => count(array_filter($results, fn($r) => (int) $r['is_external'] === 1 && (int) $r['is_declared'] === 0)),
            'results'      => $results,
        ];
    }

    /* ----------------------------------------------------------------
     *  Letzte Ergebnisse & Bereinigung
     * ---------------------------------------------------------------- */

    public function getLastAuditResults(): array
    {
        $table = $this->db->table('legal_audit_results');
        $lastScan = $this->db->fetchOne("SELECT scan_id, created_at FROM {$table} ORDER BY created_at DESC LIMIT 1");
        if (!$lastScan) {
            return ['scan_id' => null, 'date' => null, 'results' => []];
        }

        $results = $this->db->fetchAll(
            "SELECT * FROM {$table} WHERE scan_id = :sid ORDER BY severity DESC, resource_url ASC",
            ['sid' => $lastScan['scan_id']]
        );

        return [
            'scan_id' => $lastScan['scan_id'],
            'date'    => $lastScan['created_at'],
            'results' => $results,
        ];
    }

    public function clearAuditResults(): void
    {
        $this->db->query('DELETE FROM ' . $this->db->table('legal_audit_results'));
    }

    /* ----------------------------------------------------------------
     *  Interne Hilfsmethoden
     * ---------------------------------------------------------------- */

    /** Sammelt alle relevanten Dateien (Twig, PHP, CSS, JS). */
    private function collectFiles(): array
    {
        $files = [];
        $scanDirs = [
            ['path' => $this->projectRoot . '/themes',  'area' => 'theme'],
            ['path' => $this->projectRoot . '/modules', 'area' => 'module'],
            ['path' => $this->projectRoot . '/public',  'area' => 'public'],
        ];

        $extensions = ['twig', 'php', 'css', 'js', 'html'];

        foreach ($scanDirs as $dir) {
            if (!is_dir($dir['path'])) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir['path'], \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensions, true) && $file->isReadable()) {
                    $fullPath = str_replace('\\', '/', $file->getPathname());
                    $relativePath = str_replace('\\', '/', substr($fullPath, strlen($this->projectRoot) + 1));
                    $files[] = [
                        'path'     => $fullPath,
                        'relative' => $relativePath,
                        'area'     => $dir['area'],
                    ];
                }
            }
        }

        return $files;
    }

    /** Extrahiert externe URLs aus einer Datei. */
    private function extractExternalUrls(string $filePath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false || $content === '') {
            return [];
        }

        $urls = [];
        // Muster: src="...", href="...", url(...), action="...", data-src="..."
        $patterns = [
            '/(?:src|href|action|data-src)\s*=\s*["\']\s*(https?:\/\/[^"\'>\s]+)/i',
            '/url\s*\(\s*["\']?\s*(https?:\/\/[^"\')\s]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $urls = array_merge($urls, $matches[1]);
            }
        }

        return array_unique($urls);
    }

    /** Domain aus URL extrahieren. */
    private function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        return is_string($host) ? strtolower($host) : '';
    }

    /** Prüft ob eine Domain intern (eigene Site) ist. */
    private function isInternalDomain(string $domain): bool
    {
        if ($domain === '' || $domain === 'localhost') {
            return true;
        }

        $ownHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $ownHost = strtolower((string) $ownHost);

        // Direkte Übereinstimmung oder Subdomain
        return $domain === $ownHost || str_ends_with($domain, '.' . $ownHost);
    }

    /** Prüft ob eine Domain in den deklarierten Diensten enthalten ist. */
    private function isDomainDeclared(string $domain, array $declaredDomains): bool
    {
        foreach ($declaredDomains as $declared) {
            if ($domain === $declared || str_ends_with($domain, '.' . $declared)) {
                return true;
            }
        }
        return false;
    }

    /** Lädt alle deklarierten Dienst-Domains. */
    private function getDeclaredDomains(string $locale): array
    {
        $services = $this->db->fetchAll(
            'SELECT privacy_url FROM ' . $this->db->table('legal_services')
            . ' WHERE locale = :loc AND is_active = 1',
            ['loc' => $locale]
        );

        $domains = [];
        foreach ($services as $svc) {
            $d = $this->extractDomain((string) ($svc['privacy_url'] ?? ''));
            if ($d !== '') {
                // Auch die übergeordnete Domain hinzufügen (z.B. google.com für fonts.googleapis.com)
                $domains[] = $d;
                $parts = explode('.', $d);
                if (count($parts) > 2) {
                    $domains[] = implode('.', array_slice($parts, -2));
                }
            }
        }
        return array_unique($domains);
    }

    /** Rät den Ressourcentyp anhand der URL. */
    private function guessResourceType(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        if (preg_match('/\.(css|less|scss)$/i', $path)) {
            return 'stylesheet';
        }
        if (preg_match('/\.(js|mjs)$/i', $path)) {
            return 'script';
        }
        if (preg_match('/\.(woff2?|ttf|otf|eot)$/i', $path)) {
            return 'font';
        }
        if (preg_match('/\.(png|jpe?g|gif|svg|webp|ico)$/i', $path)) {
            return 'image';
        }
        if (preg_match('/\.(mp4|webm|ogg|mp3)$/i', $path)) {
            return 'media';
        }
        return 'other';
    }
}
