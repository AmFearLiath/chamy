<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;
use RuntimeException;

final class StateManager implements ManagerInterface
{
    /** @var array<string, array{label: string, is_public: bool}> */
    private array $states = [];

    /** @var array<string, string[]> */
    private array $transitions = [];

    public function __construct()
    {
    }

    public function getName(): string
    {
        return 'state';
    }

    public function boot(): void
    {
        $this->registerDefaults();
    }

    public function defineState(string $state, string $label, bool $isPublic = false): void
    {
        $this->states[$state] = [
            'label'     => $label,
            'is_public' => $isPublic,
        ];
    }

    public function defineTransition(string $from, string $to): void
    {
        if (!isset($this->transitions[$from])) {
            $this->transitions[$from] = [];
        }

        if (!in_array($to, $this->transitions[$from], true)) {
            $this->transitions[$from][] = $to;
        }
    }

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, $this->transitions[$from] ?? [], true);
    }

    public function transition(string $currentState, string $targetState): string
    {
        if (!$this->canTransition($currentState, $targetState)) {
            throw new RuntimeException(
                "Transition from '{$currentState}' to '{$targetState}' is not allowed."
            );
        }

        return $targetState;
    }

    public function getAvailableTransitions(string $state): array
    {
        return $this->transitions[$state] ?? [];
    }

    public function getStates(): array
    {
        return $this->states;
    }

    public function isPublicState(string $state): bool
    {
        return $this->states[$state]['is_public'] ?? false;
    }

    public function getPublicStates(): array
    {
        return array_filter($this->states, fn(array $s) => $s['is_public']);
    }

    // ------------------------------------------------------------------

    private function registerDefaults(): void
    {
        $this->defineState('draft', 'Entwurf', false);
        $this->defineState('review', 'In Prüfung', false);
        $this->defineState('published', 'Veröffentlicht', true);
        $this->defineState('archived', 'Archiviert', false);
        $this->defineState('deleted', 'Gelöscht', false);

        $this->defineTransition('draft', 'review');
        $this->defineTransition('draft', 'published');
        $this->defineTransition('draft', 'deleted');
        $this->defineTransition('review', 'published');
        $this->defineTransition('review', 'draft');
        $this->defineTransition('review', 'deleted');
        $this->defineTransition('published', 'archived');
        $this->defineTransition('published', 'draft');
        $this->defineTransition('archived', 'draft');
        $this->defineTransition('archived', 'deleted');
    }
}
