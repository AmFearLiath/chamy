<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Kernel;
use RuntimeException;

final class ContentTypeManager implements ManagerInterface
{
    private Kernel $kernel;

    /** @var array<string, array> */
    private array $types = [];

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getName(): string
    {
        return 'content_type';
    }

    public function boot(): void
    {
        $this->loadSystemTypes();
    }

    public function registerType(array $definition): void
    {
        if (empty($definition['id'])) {
            throw new RuntimeException('Content type definition must have an "id".');
        }

        $id = $definition['id'];

        if (isset($this->types[$id])) {
            throw new RuntimeException("Content type '{$id}' is already registered.");
        }

        $this->types[$id] = array_merge([
            'id'                   => $id,
            'label'                => $id,
            'description'          => '',
            'source'               => 'system',
            'version'              => '1.0.0',
            'fields'               => [],
            'is_translatable'      => true,
            'is_revisionable'      => true,
            'is_publicly_queryable' => false,
            'group'                => 'general',
            'permissions'          => [],
        ], $definition);

        $this->kernel->events()->dispatch('content_type.registered', ['type_id' => $id]);
    }

    public function extendType(string $typeId, array $fields): void
    {
        if (!isset($this->types[$typeId])) {
            throw new RuntimeException("Content type '{$typeId}' does not exist.");
        }

        foreach ($fields as $fieldId => $field) {
            if (isset($this->types[$typeId]['fields'][$fieldId])) {
                throw new RuntimeException("Field '{$fieldId}' already exists on content type '{$typeId}'.");
            }

            $this->types[$typeId]['fields'][$fieldId] = $field;
        }
    }

    public function getType(string $id): ?array
    {
        return $this->types[$id] ?? null;
    }

    public function getAllTypes(): array
    {
        return $this->types;
    }

    public function getTypesByGroup(): array
    {
        $grouped = [];

        foreach ($this->types as $id => $type) {
            $group = $type['group'] ?? 'general';
            $grouped[$group][$id] = $type;
        }

        return $grouped;
    }

    public function getFields(string $typeId): array
    {
        return $this->types[$typeId]['fields'] ?? [];
    }

    public function hasType(string $id): bool
    {
        return isset($this->types[$id]);
    }

    // ------------------------------------------------------------------

    private function loadSystemTypes(): void
    {
        $systemPath = $this->kernel->path('system', 'content-types');

        if (!is_dir($systemPath)) {
            return;
        }

        $files = glob($systemPath . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $definition = require $file;
            if (is_array($definition)) {
                $this->registerType($definition);
            }
        }
    }
}
