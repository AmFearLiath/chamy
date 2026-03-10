<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Database\Connection;

final class VersionManager implements ManagerInterface
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getName(): string
    {
        return 'version';
    }

    public function boot(): void
    {
        // Ready for versioning
    }

    public function createVersion(int $contentId, array $data, ?int $userId = null, string $note = ''): int
    {
        $table = $this->db->table('content_versions');
        $now = date('Y-m-d H:i:s');

        // Get next version number
        $version = $this->getNextVersionNumber($contentId);

        $id = $this->db->insert('content_versions', [
            'content_id'   => $contentId,
            'version'      => $version,
            'data'         => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'note'         => $note,
            'created_by'   => $userId,
            'created_at'   => $now,
        ]);

        return (int) $id;
    }

    public function getVersions(int $contentId): array
    {
        $table = $this->db->table('content_versions');

        $rows = $this->db->fetchAll(
            "SELECT * FROM {$table} WHERE content_id = :id ORDER BY version DESC",
            ['id' => $contentId]
        );

        foreach ($rows as &$row) {
            if (isset($row['data'])) {
                $row['data'] = json_decode($row['data'], true);
            }
        }

        return $rows;
    }

    public function getVersion(int $contentId, int $versionNumber): ?array
    {
        $table = $this->db->table('content_versions');

        $row = $this->db->fetchOne(
            "SELECT * FROM {$table} WHERE content_id = :id AND version = :version",
            ['id' => $contentId, 'version' => $versionNumber]
        );

        if ($row !== null && isset($row['data'])) {
            $row['data'] = json_decode($row['data'], true);
        }

        return $row;
    }

    public function getLatestVersion(int $contentId): ?array
    {
        $table = $this->db->table('content_versions');

        $row = $this->db->fetchOne(
            "SELECT * FROM {$table} WHERE content_id = :id ORDER BY version DESC LIMIT 1",
            ['id' => $contentId]
        );

        if ($row !== null && isset($row['data'])) {
            $row['data'] = json_decode($row['data'], true);
        }

        return $row;
    }

    public function getVersionCount(int $contentId): int
    {
        $table = $this->db->table('content_versions');

        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$table} WHERE content_id = :id",
            ['id' => $contentId]
        );
    }

    // ------------------------------------------------------------------

    private function getNextVersionNumber(int $contentId): int
    {
        $table = $this->db->table('content_versions');

        $max = $this->db->fetchColumn(
            "SELECT COALESCE(MAX(version), 0) FROM {$table} WHERE content_id = :id",
            ['id' => $contentId]
        );

        return ((int) $max) + 1;
    }
}
