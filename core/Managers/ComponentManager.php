<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Kernel;

final class ComponentManager implements ManagerInterface
{
    private Kernel $kernel;

    /** @var array<string, array> */
    private array $components = [];

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getName(): string
    {
        return 'component';
    }

    public function boot(): void
    {
        // Components ready
    }

    public function registerComponent(array $definition): void
    {
        $id = $definition['id'] ?? null;

        if ($id === null) {
            return;
        }

        $this->components[$id] = array_merge([
            'id'          => $id,
            'label'       => $id,
            'description' => '',
            'category'    => 'general',
            'source'      => 'system',
            'template'    => '',
            'fields'      => [],
            'icon'        => '',
        ], $definition);
    }

    public function getComponent(string $id): ?array
    {
        return $this->components[$id] ?? null;
    }

    public function getAllComponents(): array
    {
        return $this->components;
    }

    public function getByCategory(string $category): array
    {
        return array_filter($this->components, fn(array $c) => $c['category'] === $category);
    }

    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->components as $component) {
            $cat = $component['category'];
            if (!in_array($cat, $categories, true)) {
                $categories[] = $cat;
            }
        }

        return $categories;
    }

    public function removeComponent(string $id): void
    {
        unset($this->components[$id]);
    }
}
