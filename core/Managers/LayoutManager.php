<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use Chamy\Core\Kernel;

final class LayoutManager implements ManagerInterface
{
    private Kernel $kernel;

    /** @var array<string, array> */
    private array $layouts = [];

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getName(): string
    {
        return 'layout';
    }

    public function boot(): void
    {
        $this->loadSystemLayouts();
    }

    public function registerLayout(array $definition): void
    {
        $id = $definition['id'] ?? null;

        if ($id === null) {
            return;
        }

        $this->layouts[$id] = array_merge([
            'id'          => $id,
            'label'       => $id,
            'description' => '',
            'source'      => 'system',
            'regions'     => [],
            'template'    => '',
        ], $definition);
    }

    public function getLayout(string $id): ?array
    {
        return $this->layouts[$id] ?? null;
    }

    public function getAllLayouts(): array
    {
        return $this->layouts;
    }

    public function getLayoutsBySource(string $source): array
    {
        return array_filter($this->layouts, fn(array $l) => $l['source'] === $source);
    }

    // ------------------------------------------------------------------

    private function loadSystemLayouts(): void
    {
        // System default layouts
        $this->registerLayout([
            'id'          => 'default',
            'label'       => 'Standard',
            'description' => 'Standard-Seitenlayout',
            'source'      => 'system',
            'regions'     => ['header', 'content', 'sidebar', 'footer'],
            'template'    => 'layouts/default.twig',
        ]);

        $this->registerLayout([
            'id'          => 'full-width',
            'label'       => 'Volle Breite',
            'description' => 'Layout ohne Sidebar',
            'source'      => 'system',
            'regions'     => ['header', 'content', 'footer'],
            'template'    => 'layouts/full-width.twig',
        ]);
    }
}
