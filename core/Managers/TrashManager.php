<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;

final class TrashManager implements ManagerInterface
{
    private string $storageDir;
    private string $storageFile;

    /** @var array<int, array<string, mixed>> */
    private array $items = [];

    public function __construct(string $basePath)
    {
        $this->storageDir = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'trash';
        $this->storageFile = $this->storageDir . DIRECTORY_SEPARATOR . 'trash.json';
    }

    public function getName(): string
    {
        return 'trash';
    }

    public function boot(): void
    {
        $this->load();
    }

    public function add(string $category, string $entityType, string $entityKey, array $payload, ?int $deletedBy = null): string
    {
        $id = bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');

        $item = [
            'id' => $id,
            'category' => $category,
            'entity_type' => $entityType,
            'entity_key' => $entityKey,
            'payload' => $payload,
            'deleted_by' => $deletedBy,
            'deleted_at' => $now,
            'status' => 'trashed',
            'restored_at' => null,
            'purged_at' => null,
        ];

        $this->items[] = $item;
        $this->persist();

        return $id;
    }

    public function markRestored(string $id): bool
    {
        foreach ($this->items as &$item) {
            if (($item['id'] ?? '') !== $id) {
                continue;
            }
            $item['status'] = 'restored';
            $item['restored_at'] = date('Y-m-d H:i:s');
            $this->persist();
            return true;
        }

        return false;
    }

    public function purge(string $id): bool
    {
        foreach ($this->items as &$item) {
            if (($item['id'] ?? '') !== $id) {
                continue;
            }
            $item['status'] = 'purged';
            $item['purged_at'] = date('Y-m-d H:i:s');
            $this->persist();
            return true;
        }

        return false;
    }

    public function get(string $id): ?array
    {
        foreach ($this->items as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    public function list(array $filters = []): array
    {
        $status = strtolower(trim((string) ($filters['status'] ?? 'trashed')));
        $category = strtolower(trim((string) ($filters['category'] ?? 'all')));
        $entityType = strtolower(trim((string) ($filters['entity_type'] ?? 'all')));
        $search = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $sort = strtolower(trim((string) ($filters['sort'] ?? 'deleted_desc')));

        $rows = array_values(array_filter($this->items, static function (array $item) use ($status, $category, $entityType, $search): bool {
            if ($status !== 'all' && (string) ($item['status'] ?? '') !== $status) {
                return false;
            }
            if ($category !== 'all' && (string) ($item['category'] ?? '') !== $category) {
                return false;
            }
            if ($entityType !== 'all' && (string) ($item['entity_type'] ?? '') !== $entityType) {
                return false;
            }
            if ($search === '') {
                return true;
            }

            $payload = $item['payload'] ?? [];
            $name = (string) ($payload['name'] ?? $payload['title'] ?? $payload['username'] ?? '');
            $haystack = mb_strtolower(implode(' ', [
                (string) ($item['id'] ?? ''),
                (string) ($item['entity_key'] ?? ''),
                (string) ($item['entity_type'] ?? ''),
                $name,
            ]));

            return str_contains($haystack, $search);
        }));

        usort($rows, static function (array $a, array $b) use ($sort): int {
            $deletedA = (string) ($a['deleted_at'] ?? '');
            $deletedB = (string) ($b['deleted_at'] ?? '');
            $nameA = (string) (($a['payload']['name'] ?? $a['payload']['title'] ?? $a['entity_key'] ?? ''));
            $nameB = (string) (($b['payload']['name'] ?? $b['payload']['title'] ?? $b['entity_key'] ?? ''));

            return match ($sort) {
                'deleted_asc' => strcmp($deletedA, $deletedB),
                'name_asc' => strcmp($nameA, $nameB),
                'name_desc' => strcmp($nameB, $nameA),
                default => strcmp($deletedB, $deletedA),
            };
        });

        return $rows;
    }

    public function stats(): array
    {
        $stats = [
            'total' => count($this->items),
            'trashed' => 0,
            'restored' => 0,
            'purged' => 0,
            'categories' => [],
            'types' => [],
        ];

        foreach ($this->items as $item) {
            $status = (string) ($item['status'] ?? 'trashed');
            if (isset($stats[$status]) && is_int($stats[$status])) {
                $stats[$status]++;
            }

            $cat = (string) ($item['category'] ?? 'other');
            $type = (string) ($item['entity_type'] ?? 'unknown');
            $stats['categories'][$cat] = ($stats['categories'][$cat] ?? 0) + 1;
            $stats['types'][$type] = ($stats['types'][$type] ?? 0) + 1;
        }

        ksort($stats['categories']);
        ksort($stats['types']);

        return $stats;
    }

    private function load(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }

        if (!is_file($this->storageFile)) {
            $this->items = [];
            return;
        }

        $raw = @file_get_contents($this->storageFile);
        if ($raw === false || trim($raw) === '') {
            $this->items = [];
            return;
        }

        $decoded = json_decode($raw, true);
        $this->items = is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    private function persist(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }

        @file_put_contents(
            $this->storageFile,
            json_encode($this->items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
