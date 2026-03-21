<?php

declare(strict_types=1);

/**
 * Legal Manager – Zentraler Fachservice
 *
 * Kapselt alle DB-Zugriffe und Geschäftslogik für:
 * - Stammdaten (Single Source of Truth)
 * - Dokumentblöcke (Datenschutz / Impressum)
 * - Dokumentversionen (Veröffentlichungsstände)
 * - Externe Dienste
 * - Consent-Kategorien
 * - Statistik
 * - Konfiguration
 */

namespace LegalManager;

use Chamy\Core\Database\Connection;

final class LegalService
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /* ================================================================
     *  Konfiguration (config.json)
     * ================================================================ */

    public function getConfig(string $modulePath): array
    {
        $file = $modulePath . DIRECTORY_SEPARATOR . 'config.json';
        if (!file_exists($file)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($file), true);
        return is_array($decoded) ? $decoded : [];
    }

    public function saveConfig(string $modulePath, array $config): bool
    {
        $file = $modulePath . DIRECTORY_SEPARATOR . 'config.json';
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /* ================================================================
     *  Stammdaten
     * ================================================================ */

    /** Alle Stammdaten als assoziatives Array [field_key => field_value]. */
    public function getBaseData(string $locale = 'de'): array
    {
        $rows = $this->db->fetchAll(
            'SELECT field_key, field_value FROM ' . $this->db->table('legal_base_data')
            . ' WHERE locale = :locale ORDER BY field_key',
            ['locale' => $locale]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['field_key']] = (string) $row['field_value'];
        }
        return $result;
    }

    /** Stammdaten speichern/aktualisieren (upsert pro Feld). */
    public function saveBaseData(array $data, string $locale, int $userId): void
    {
        $table = $this->db->table('legal_base_data');
        $now = date('Y-m-d H:i:s');

        foreach ($data as $key => $value) {
            $key = trim((string) $key);
            $value = trim((string) $value);
            if ($key === '') {
                continue;
            }

            $existing = $this->db->fetchOne(
                "SELECT id FROM {$table} WHERE field_key = :fk AND locale = :loc",
                ['fk' => $key, 'loc' => $locale]
            );

            if ($existing) {
                $this->db->query(
                    "UPDATE {$table} SET field_value = :val, updated_at = :now, updated_by = :uid WHERE id = :id",
                    ['val' => $value, 'now' => $now, 'uid' => $userId, 'id' => (int) $existing['id']]
                );
            } else {
                $this->db->query(
                    "INSERT INTO {$table} (field_key, field_value, locale, updated_at, updated_by) VALUES (:fk, :val, :loc, :now, :uid)",
                    ['fk' => $key, 'val' => $value, 'loc' => $locale, 'now' => $now, 'uid' => $userId]
                );
            }
        }
    }

    /** Prüft, welche Pflichtfelder der Stammdaten ausgefüllt sind. */
    public function getBaseDataCompleteness(string $locale = 'de'): array
    {
        $required = [
            'company_name', 'operator_name', 'address_street', 'address_zip',
            'address_city', 'address_country', 'contact_email',
        ];
        $data = $this->getBaseData($locale);
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        return [
            'total'    => count($required),
            'filled'   => count($required) - count($missing),
            'missing'  => $missing,
            'complete' => empty($missing),
        ];
    }

    /* ================================================================
     *  Dokumentblöcke
     * ================================================================ */

    /** Blöcke eines Dokumenttyps sortiert abrufen. */
    public function getBlocks(string $documentType, string $locale = 'de'): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->db->table('legal_document_blocks')
            . ' WHERE document_type = :dt AND locale = :loc ORDER BY sort_order ASC, id ASC',
            ['dt' => $documentType, 'loc' => $locale]
        );
    }

    /** Einzelnen Block laden. */
    public function getBlock(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM ' . $this->db->table('legal_document_blocks') . ' WHERE id = :id',
            ['id' => $id]
        );
    }

    /** Block erstellen oder aktualisieren (upsert per ID). */
    public function saveBlock(array $data): int
    {
        $table = $this->db->table('legal_document_blocks');
        $now = date('Y-m-d H:i:s');

        $id = (int) ($data['id'] ?? 0);
        $fields = [
            'document_type' => trim((string) ($data['document_type'] ?? '')),
            'block_key'     => trim((string) ($data['block_key'] ?? '')),
            'locale'        => trim((string) ($data['locale'] ?? 'de')),
            'title'         => trim((string) ($data['title'] ?? '')),
            'content'       => (string) ($data['content'] ?? ''),
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'is_active'     => (int) ($data['is_active'] ?? 1),
            'is_system'     => (int) ($data['is_system'] ?? 0),
            'source_module' => isset($data['source_module']) ? trim((string) $data['source_module']) : null,
        ];

        if ($id > 0) {
            $this->db->query(
                "UPDATE {$table} SET document_type = :document_type, block_key = :block_key, locale = :locale,
                 title = :title, content = :content, sort_order = :sort_order, is_active = :is_active,
                 is_system = :is_system, source_module = :source_module, updated_at = :now WHERE id = :id",
                array_merge($fields, ['now' => $now, 'id' => $id])
            );
            return $id;
        }

        $this->db->query(
            "INSERT INTO {$table} (document_type, block_key, locale, title, content, sort_order, is_active, is_system, source_module, created_at, updated_at)
             VALUES (:document_type, :block_key, :locale, :title, :content, :sort_order, :is_active, :is_system, :source_module, :now_created, :now_updated)",
            array_merge($fields, ['now_created' => $now, 'now_updated' => $now])
        );
        return (int) $this->db->getPdo()->lastInsertId();
    }

    /** Block löschen. */
    public function deleteBlock(int $id): bool
    {
        return $this->db->query(
            'DELETE FROM ' . $this->db->table('legal_document_blocks') . ' WHERE id = :id',
            ['id' => $id]
        )->rowCount() > 0;
    }

    /** Blockreihenfolge aktualisieren. */
    public function reorderBlocks(array $orderedIds): void
    {
        $table = $this->db->table('legal_document_blocks');
        foreach ($orderedIds as $position => $id) {
            $this->db->query(
                "UPDATE {$table} SET sort_order = :pos WHERE id = :id",
                ['pos' => $position, 'id' => (int) $id]
            );
        }
    }

    /* ================================================================
     *  Dokumentversionen / Veröffentlichung
     * ================================================================ */

    /** Versionshistorie eines Dokumenttyps abrufen. */
    public function getDocumentVersions(string $documentType, string $locale = 'de'): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->db->table('legal_documents')
            . ' WHERE document_type = :dt AND locale = :loc ORDER BY version DESC',
            ['dt' => $documentType, 'loc' => $locale]
        );
    }

    /** Aktuell veröffentlichte Version abrufen. */
    public function getPublishedDocument(string $documentType, string $locale = 'de'): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM ' . $this->db->table('legal_documents')
            . " WHERE document_type = :dt AND locale = :loc AND status = 'published' ORDER BY version DESC LIMIT 1",
            ['dt' => $documentType, 'loc' => $locale]
        );
    }

    /** Dokument veröffentlichen – archiviert alte, erstellt neue Version. */
    public function publishDocument(string $documentType, string $locale, int $userId, string $note, string $htmlSnapshot): int
    {
        $table = $this->db->table('legal_documents');
        $now = date('Y-m-d H:i:s');

        // Aktuelle veröffentlichte Versionen archivieren
        $this->db->query(
            "UPDATE {$table} SET status = 'archived' WHERE document_type = :dt AND locale = :loc AND status = 'published'",
            ['dt' => $documentType, 'loc' => $locale]
        );

        // Nächste Versionsnummer
        $maxVersion = (int) $this->db->fetchColumn(
            "SELECT COALESCE(MAX(version), 0) FROM {$table} WHERE document_type = :dt AND locale = :loc",
            ['dt' => $documentType, 'loc' => $locale]
        );

        $this->db->query(
            "INSERT INTO {$table} (document_type, locale, version, status, content_snapshot, change_note, published_at, published_by, created_at, updated_at, updated_by)
             VALUES (:dt, :loc, :ver, 'published', :snap, :note, :now_published, :uid, :now_created, :now_updated, :uid)",
            [
                'dt' => $documentType, 'loc' => $locale, 'ver' => $maxVersion + 1,
                'snap' => $htmlSnapshot, 'note' => $note, 'now_published' => $now, 'now_created' => $now, 'now_updated' => $now, 'uid' => $userId,
            ]
        );

        return $maxVersion + 1;
    }

    /* ================================================================
     *  Externe Dienste
     * ================================================================ */

    public function getServices(string $locale = 'de'): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->db->table('legal_services')
            . ' WHERE locale = :loc ORDER BY category, name',
            ['loc' => $locale]
        );
    }

    public function getService(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM ' . $this->db->table('legal_services') . ' WHERE id = :id',
            ['id' => $id]
        );
    }

    public function saveService(array $data): int
    {
        $table = $this->db->table('legal_services');
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);

        $fields = [
            'name'             => trim((string) ($data['name'] ?? '')),
            'provider'         => trim((string) ($data['provider'] ?? '')),
            'category'         => trim((string) ($data['category'] ?? 'other')),
            'purpose'          => trim((string) ($data['purpose'] ?? '')),
            'data_collected'   => trim((string) ($data['data_collected'] ?? '')),
            'privacy_url'      => trim((string) ($data['privacy_url'] ?? '')),
            'consent_required' => (int) ($data['consent_required'] ?? 1),
            'is_active'        => (int) ($data['is_active'] ?? 1),
            'source_module'    => !empty($data['source_module']) ? trim((string) $data['source_module']) : null,
            'locale'           => trim((string) ($data['locale'] ?? 'de')),
        ];

        if ($id > 0) {
            $setClauses = [];
            $params = ['id' => $id, 'now' => $now];
            foreach ($fields as $col => $val) {
                $setClauses[] = "`{$col}` = :{$col}";
                $params[$col] = $val;
            }
            $setClauses[] = 'updated_at = :now';
            $this->db->query("UPDATE {$table} SET " . implode(', ', $setClauses) . ' WHERE id = :id', $params);
            return $id;
        }

        $cols = array_keys($fields);
        $placeholders = array_map(fn(string $c) => ':' . $c, $cols);
        $this->db->query(
            "INSERT INTO {$table} (`" . implode('`, `', $cols) . "`, created_at, updated_at) VALUES ("
            . implode(', ', $placeholders) . ", :now_created, :now_updated)",
            array_merge($fields, ['now_created' => $now, 'now_updated' => $now])
        );
        return (int) $this->db->getPdo()->lastInsertId();
    }

    public function deleteService(int $id): bool
    {
        return $this->db->query(
            'DELETE FROM ' . $this->db->table('legal_services') . ' WHERE id = :id',
            ['id' => $id]
        )->rowCount() > 0;
    }

    /* ================================================================
     *  Consent-Kategorien
     * ================================================================ */

    public function getConsentCategories(string $locale = 'de'): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->db->table('legal_consent_categories')
            . ' WHERE locale = :loc ORDER BY sort_order ASC, id ASC',
            ['loc' => $locale]
        );
    }

    public function getConsentCategory(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM ' . $this->db->table('legal_consent_categories') . ' WHERE id = :id',
            ['id' => $id]
        );
    }

    public function saveConsentCategory(array $data): int
    {
        $table = $this->db->table('legal_consent_categories');
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);

        $fields = [
            'category_key' => trim((string) ($data['category_key'] ?? '')),
            'label'        => trim((string) ($data['label'] ?? '')),
            'description'  => trim((string) ($data['description'] ?? '')),
            'is_required'  => (int) ($data['is_required'] ?? 0),
            'sort_order'   => (int) ($data['sort_order'] ?? 0),
            'is_active'    => (int) ($data['is_active'] ?? 1),
            'locale'       => trim((string) ($data['locale'] ?? 'de')),
        ];

        if ($id > 0) {
            $setClauses = [];
            $params = ['id' => $id, 'now' => $now];
            foreach ($fields as $col => $val) {
                $setClauses[] = "`{$col}` = :{$col}";
                $params[$col] = $val;
            }
            $setClauses[] = 'updated_at = :now';
            $this->db->query("UPDATE {$table} SET " . implode(', ', $setClauses) . ' WHERE id = :id', $params);
            return $id;
        }

        $cols = array_keys($fields);
        $placeholders = array_map(fn(string $c) => ':' . $c, $cols);
        $this->db->query(
            "INSERT INTO {$table} (`" . implode('`, `', $cols) . "`, created_at, updated_at) VALUES ("
            . implode(', ', $placeholders) . ", :now, :now)",
            array_merge($fields, ['now' => $now])
        );
        return (int) $this->db->getPdo()->lastInsertId();
    }

    public function deleteConsentCategory(int $id): bool
    {
        return $this->db->query(
            'DELETE FROM ' . $this->db->table('legal_consent_categories') . ' WHERE id = :id',
            ['id' => $id]
        )->rowCount() > 0;
    }

    /* ================================================================
     *  Statistik
     * ================================================================ */

    /** Statistik-Event aufzeichnen (datenschutzkonform, nur Hashes). */
    public function recordStat(string $pageType, string $eventType, string $locale, bool $anonymize = true): void
    {
        $ipHash = null;
        $uaHash = null;

        if ($anonymize) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($ip !== '') {
                // IP kürzen + hashen für maximale Anonymisierung
                $parts = explode('.', $ip);
                $parts[count($parts) - 1] = '0';
                $ipHash = hash('sha256', implode('.', $parts) . date('Y-m'));
            }
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($ua !== '') {
                $uaHash = hash('sha256', $ua . date('Y-m'));
            }
        }

        $this->db->query(
            'INSERT INTO ' . $this->db->table('legal_stats')
            . ' (page_type, event_type, locale, ip_hash, user_agent_hash) VALUES (:pt, :et, :loc, :ip, :ua)',
            ['pt' => $pageType, 'et' => $eventType, 'loc' => $locale, 'ip' => $ipHash, 'ua' => $uaHash]
        );
    }

    /** Aggregierte Statistikdaten abrufen. */
    public function getStats(int $days = 30): array
    {
        $table = $this->db->table('legal_stats');
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $totals = $this->db->fetchAll(
            "SELECT page_type, event_type, COUNT(*) AS cnt FROM {$table} WHERE created_at >= :since GROUP BY page_type, event_type",
            ['since' => $since]
        );

        $daily = $this->db->fetchAll(
            "SELECT DATE(created_at) AS day, page_type, COUNT(*) AS cnt FROM {$table}
             WHERE created_at >= :since AND event_type = 'view' GROUP BY day, page_type ORDER BY day ASC",
            ['since' => $since]
        );

        return [
            'totals' => $totals,
            'daily'  => $daily,
            'days'   => $days,
        ];
    }

    /* ================================================================
     *  Dashboard-Statusübersicht
     * ================================================================ */

    /** Komplettstatus für Das Dashboard. */
    public function getDashboardStatus(string $locale = 'de'): array
    {
        $baseComplete = $this->getBaseDataCompleteness($locale);

        $privacyBlocks = $this->getBlocks('privacy', $locale);
        $activePrivacy = array_filter($privacyBlocks, fn($b) => (int) ($b['is_active'] ?? 0) === 1);

        $imprintBlocks = $this->getBlocks('imprint', $locale);
        $activeImprint = array_filter($imprintBlocks, fn($b) => (int) ($b['is_active'] ?? 0) === 1);

        $publishedPrivacy = $this->getPublishedDocument('privacy', $locale);
        $publishedImprint = $this->getPublishedDocument('imprint', $locale);

        $services = $this->getServices($locale);
        $categories = $this->getConsentCategories($locale);

        // Letztes Audit
        $lastAudit = $this->db->fetchOne(
            'SELECT scan_id, created_at FROM ' . $this->db->table('legal_audit_results')
            . ' ORDER BY created_at DESC LIMIT 1'
        );

        // Audit-Warnungen
        $auditWarnings = 0;
        $auditCritical = 0;
        if ($lastAudit) {
            $auditWarnings = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM " . $this->db->table('legal_audit_results')
                . " WHERE scan_id = :sid AND severity = 'warning'",
                ['sid' => $lastAudit['scan_id']]
            );
            $auditCritical = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM " . $this->db->table('legal_audit_results')
                . " WHERE scan_id = :sid AND severity = 'critical'",
                ['sid' => $lastAudit['scan_id']]
            );
        }

        // Statistik (letzte 30 Tage)
        $recentViews = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM " . $this->db->table('legal_stats')
            . " WHERE event_type = 'view' AND created_at >= :since",
            ['since' => date('Y-m-d H:i:s', strtotime('-30 days'))]
        );

        return [
            'base_data'         => $baseComplete,
            'privacy_blocks'    => count($activePrivacy),
            'imprint_blocks'    => count($activeImprint),
            'privacy_published' => $publishedPrivacy,
            'imprint_published' => $publishedImprint,
            'services_count'    => count($services),
            'categories_count'  => count($categories),
            'last_audit'        => $lastAudit,
            'audit_warnings'    => $auditWarnings,
            'audit_critical'    => $auditCritical,
            'recent_views'      => $recentViews,
        ];
    }

    /* ================================================================
     *  Standard-Blöcke erstellen
     * ================================================================ */

    /** Erzeugt die standardmäßigen Datenschutz-Blöcke, wenn keine vorhanden. */
    public function createDefaultPrivacyBlocks(string $locale = 'de'): void
    {
        $existing = $this->getBlocks('privacy', $locale);
        if (!empty($existing)) {
            return;
        }

        $defaults = [
            'introduction', 'responsible', 'hosting', 'server_logs', 'contact_form',
            'communication', 'cookies', 'necessary_services', 'analytics',
            'external_media', 'data_subject_rights', 'revocation', 'retention', 'security',
        ];

        foreach ($defaults as $i => $key) {
            $this->saveBlock([
                'document_type' => 'privacy',
                'block_key'     => $key,
                'locale'        => $locale,
                'title'         => '', // Titel wird über i18n per block_key aufgelöst
                'content'       => '',
                'sort_order'    => ($i + 1) * 10,
                'is_active'     => 1,
                'is_system'     => 1,
            ]);
        }
    }

    /** Erzeugt die standardmäßigen Impressums-Blöcke, wenn keine vorhanden. */
    public function createDefaultImprintBlocks(string $locale = 'de'): void
    {
        $existing = $this->getBlocks('imprint', $locale);
        if (!empty($existing)) {
            return;
        }

        $defaults = [
            'operator', 'representation', 'contact', 'register_data',
            'tax_data', 'responsible_content', 'technical_contact', 'dispute_resolution',
        ];

        foreach ($defaults as $i => $key) {
            $this->saveBlock([
                'document_type' => 'imprint',
                'block_key'     => $key,
                'locale'        => $locale,
                'title'         => '',
                'content'       => '',
                'sort_order'    => ($i + 1) * 10,
                'is_active'     => 1,
                'is_system'     => 1,
            ]);
        }
    }

    /* ================================================================
     *  Tabellen-Verfügbarkeit prüfen
     * ================================================================ */

    /** Prüft ob die Modultabellen existieren. */
    public function tablesExist(): bool
    {
        try {
            $this->db->fetchOne('SELECT 1 FROM ' . $this->db->table('legal_base_data') . ' LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
