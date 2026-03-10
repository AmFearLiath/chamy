<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Kernel;

final class ContentManager implements ManagerInterface
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getName(): string
    {
        return 'content';
    }

    public function boot(): void
    {
        // Ready for content operations
    }

    public function create(string $typeId, array $data, string $locale = null): array
    {
        $locale = $locale ?? $this->kernel->lang()->getLocale();
        $db = $this->kernel->getDatabase();
        $now = date('Y-m-d H:i:s');
        $uuid = $this->generateUuid();

        $entry = [
            'uuid'           => $uuid,
            'content_type'   => $typeId,
            'locale'         => $locale,
            'status'         => 'draft',
            'version'        => 1,
            'data'           => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_by'     => $data['_created_by'] ?? null,
            'updated_by'     => $data['_created_by'] ?? null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        unset($data['_created_by']);

        $id = $db->insert('content_entries', $entry);

        $this->kernel->events()->dispatch('content.created', [
            'id'      => $id,
            'uuid'    => $uuid,
            'type_id' => $typeId,
        ]);

        return array_merge($entry, ['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $db = $this->kernel->getDatabase();
        $table = $db->table('content_entries');

        $row = $db->fetchOne("SELECT * FROM {$table} WHERE id = :id", ['id' => $id]);

        if ($row !== null && isset($row['data'])) {
            $row['data'] = json_decode($row['data'], true);
        }

        return $row;
    }

    public function findByUuid(string $uuid): ?array
    {
        $db = $this->kernel->getDatabase();
        $table = $db->table('content_entries');

        $row = $db->fetchOne("SELECT * FROM {$table} WHERE uuid = :uuid", ['uuid' => $uuid]);

        if ($row !== null && isset($row['data'])) {
            $row['data'] = json_decode($row['data'], true);
        }

        return $row;
    }

    public function update(int $id, array $data): bool
    {
        $db = $this->kernel->getDatabase();
        $now = date('Y-m-d H:i:s');

        $update = [
            'data'       => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'updated_at' => $now,
            'updated_by' => $data['_updated_by'] ?? null,
        ];

        unset($data['_updated_by']);

        $affected = $db->update('content_entries', $update, 'id = :id', ['id' => $id]);

        if ($affected > 0) {
            $this->kernel->events()->dispatch('content.updated', ['id' => $id]);
        }

        return $affected > 0;
    }

    public function delete(int $id): bool
    {
        $db = $this->kernel->getDatabase();

        $affected = $db->update(
            'content_entries',
            ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $id]
        );

        if ($affected > 0) {
            $this->kernel->events()->dispatch('content.deleted', ['id' => $id]);
        }

        return $affected > 0;
    }

    public function listByType(string $typeId, string $status = null, int $limit = 50, int $offset = 0): array
    {
        $db = $this->kernel->getDatabase();
        $table = $db->table('content_entries');

        $sql = "SELECT * FROM {$table} WHERE content_type = :type";
        $params = ['type' => $typeId];

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        } else {
            $sql .= " AND status != 'deleted'";
        }

        $sql .= " ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $rows = $db->fetchAll($sql, $params);

        foreach ($rows as &$row) {
            if (isset($row['data'])) {
                $row['data'] = json_decode($row['data'], true);
            }
        }

        return $rows;
    }

    public function count(string $typeId, string $status = null): int
    {
        $db = $this->kernel->getDatabase();
        $table = $db->table('content_entries');

        $sql = "SELECT COUNT(*) FROM {$table} WHERE content_type = :type";
        $params = ['type' => $typeId];

        if ($status !== null) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        } else {
            $sql .= " AND status != 'deleted'";
        }

        return (int) $db->fetchColumn($sql, $params);
    }

    // ------------------------------------------------------------------

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
