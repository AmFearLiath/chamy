<?php

declare(strict_types=1);

namespace Chamy\Core\Editor;

use Chamy\Core\Kernel;

/**
 * Central definition registry for the visual content editor.
 *
 * Collects and merges definitions from Core, Theme, and Module sources.
 * Serves as the single source of truth for:
 *   - Layout definitions
 *   - Block definitions
 *   - Component definitions
 *   - Snippet definitions
 *
 * Priority: Core < Theme < Module (higher overrides lower on ID conflicts).
 */
final class DefinitionRegistry
{
    /** @var array<string, array> */
    private array $layouts = [];

    /** @var array<string, array> */
    private array $blocks = [];

    /** @var array<string, array> */
    private array $components = [];

    /** @var array<string, array> */
    private array $snippets = [];

    private bool $loaded = false;

    public function __construct(private readonly Kernel $kernel) {}

    // ─── Public API ───────────────────────────────────────────

    /**
     * Load all definitions. Idempotent – no-op after first call.
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loadCoreLayouts();
        $this->loadCoreBlocks();
        $this->loadCoreComponents();
        $this->loadCoreSnippets();
        $this->loadFromTheme();
        $this->loadFromModules();
        $this->loadCustomDefinitions();
        $this->loadUserComponents();

        $this->loaded = true;
    }

    /**
     * Return the full registry as an associative array.
     *
     * @return array{layouts: array, blocks: array, components: array, snippets: array}
     */
    public function getAll(): array
    {
        $this->load();

        return [
            'layouts'    => $this->layouts,
            'blocks'     => $this->blocks,
            'components' => $this->components,
            'snippets'   => $this->snippets,
        ];
    }

    /** @return array<string, array> */
    public function getLayouts(): array
    {
        $this->load();
        return $this->layouts;
    }

    /** @return array<string, array> */
    public function getBlocks(): array
    {
        $this->load();
        return $this->blocks;
    }

    /** @return array<string, array> */
    public function getComponents(): array
    {
        $this->load();
        return $this->components;
    }

    /** @return array<string, array> */
    public function getSnippets(): array
    {
        $this->load();
        return $this->snippets;
    }

    /**
     * Retrieve a single definition by type and ID.
     *
     * @param string $type  One of: layout, block, component, snippet
     * @param string $id    Definition ID (e.g. "hero", "text", "section")
     */
    public function getDefinition(string $type, string $id): ?array
    {
        $this->load();

        return match ($type) {
            'layout'    => $this->layouts[$id] ?? null,
            'block'     => $this->blocks[$id] ?? null,
            'component' => $this->components[$id] ?? null,
            'snippet'   => $this->snippets[$id] ?? null,
            default     => null,
        };
    }

    /**
     * Get all definitions filtered by content type and user role.
     */
    public function getFiltered(string $contentType, array $userRoles = []): array
    {
        $all = $this->getAll();
        $filtered = [];

        foreach ($all as $group => $defs) {
            $filtered[$group] = [];
            foreach ($defs as $id => $def) {
                if (!$this->isVisible($def, $contentType, $userRoles)) {
                    continue;
                }
                $filtered[$group][$id] = $def;
            }
        }

        return $filtered;
    }

    private function isVisible(array $def, string $contentType, array $userRoles): bool
    {
        // Check content type restriction
        $allowedTypes = $def['allowedContentTypes'] ?? [];
        if (!empty($allowedTypes) && !in_array($contentType, $allowedTypes, true)) {
            return false;
        }

        // Check role restriction
        $requiredRoles = $def['requiredRoles'] ?? [];
        if (!empty($requiredRoles) && empty(array_intersect($requiredRoles, $userRoles))) {
            return false;
        }

        return true;
    }

    /**
     * Register a definition (used by themes/modules at boot time).
     */
    public function register(string $type, string $id, array $definition): void
    {
        $definition['id'] = $id;
        $definition['type'] = $type;

        match ($type) {
            'layout'    => $this->layouts[$id] = $definition,
            'block'     => $this->blocks[$id] = $definition,
            'component' => $this->components[$id] = $definition,
            'snippet'   => $this->snippets[$id] = $definition,
            default     => null,
        };
    }

    /**
     * Check whether a child type may be placed inside a parent definition.
     */
    public function canAcceptChild(string $parentType, string $parentDefinition, string $childType): bool
    {
        $parentDef = $this->getDefinition($parentType, $parentDefinition);
        if (!$parentDef) {
            // Root always accepts layouts, blocks, components, snippets
            return $parentType === 'root';
        }

        $allowed = $parentDef['allowedChildren'] ?? [];
        if (empty($allowed)) {
            return false;
        }

        return in_array($childType, $allowed, true);
    }

    /**
     * Validate a full editor tree against definition constraints.
     *
     * @return array<int, string> List of validation errors (empty = valid)
     */
    public function validateTree(array $root): array
    {
        $this->load();
        $errors = [];
        $this->validateNode($root, null, $errors);
        return $errors;
    }

    // ─── Core Definitions ─────────────────────────────────────

    private function loadCoreLayouts(): void
    {
        // Import system layouts from LayoutManager
        foreach ($this->kernel->layouts()->getAllLayouts() as $layout) {
            $this->layouts[$layout['id']] = [
                'id'              => $layout['id'],
                'type'            => 'layout',
                'label'           => $layout['label'],
                'description'     => $layout['description'] ?? '',
                'category'        => 'layout',
                'source'          => $layout['source'] ?? 'core',
                'icon'            => 'layout',
                'allowedChildren' => ['layout', 'block', 'component', 'snippet'],
                'fields'          => [],
                'defaultProps'    => [],
            ];
        }

        // Core editor-specific layout types
        $coreLayouts = [
            [
                'id'          => 'section',
                'label'       => 'Sektion',
                'description' => 'Horizontaler Inhaltsbereich',
                'icon'        => 'section',
                'fields'      => [
                    ['name' => 'variant', 'type' => 'select', 'label' => 'Variante', 'options' => ['default' => 'Standard', 'wide' => 'Breit', 'narrow' => 'Schmal']],
                    ['name' => 'background', 'type' => 'select', 'label' => 'Hintergrund', 'options' => ['none' => 'Keiner', 'light' => 'Hell', 'dark' => 'Dunkel', 'accent' => 'Akzent']],
                    ['name' => 'padding', 'type' => 'select', 'label' => 'Abstand', 'options' => ['none' => 'Kein', 'sm' => 'Klein', 'md' => 'Mittel', 'lg' => 'Groß']],
                ],
                'defaultProps' => ['variant' => 'default', 'background' => 'none', 'padding' => 'md'],
            ],
            [
                'id'          => 'container',
                'label'       => 'Container',
                'description' => 'Zentrierter Inhaltscontainer',
                'icon'        => 'container',
                'fields'      => [
                    ['name' => 'maxWidth', 'type' => 'select', 'label' => 'Maximale Breite', 'options' => ['sm' => 'Klein (640px)', 'md' => 'Mittel (960px)', 'lg' => 'Groß (1200px)', 'full' => 'Voll']],
                ],
                'defaultProps' => ['maxWidth' => 'lg'],
            ],
            [
                'id'          => 'grid',
                'label'       => 'Grid',
                'description' => 'Flexibles Rastersystem',
                'icon'        => 'grid',
                'fields'      => [
                    ['name' => 'columns', 'type' => 'select', 'label' => 'Spalten', 'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4']],
                    ['name' => 'gap', 'type' => 'select', 'label' => 'Abstand', 'options' => ['none' => 'Kein', 'sm' => 'Klein', 'md' => 'Mittel', 'lg' => 'Groß']],
                ],
                'defaultProps' => ['columns' => '2', 'gap' => 'md'],
            ],
            [
                'id'          => 'columns',
                'label'       => 'Spalten',
                'description' => 'Flexible Spaltenanordnung',
                'icon'        => 'columns',
                'fields'      => [
                    ['name' => 'ratio', 'type' => 'select', 'label' => 'Verhältnis', 'options' => ['1:1' => '50/50', '1:2' => '33/66', '2:1' => '66/33', '1:1:1' => '33/33/33']],
                ],
                'defaultProps' => ['ratio' => '1:1'],
            ],
        ];

        foreach ($coreLayouts as $def) {
            $this->layouts[$def['id']] = [
                'id'              => $def['id'],
                'type'            => 'layout',
                'label'           => $def['label'],
                'description'     => $def['description'],
                'category'        => 'layout',
                'source'          => 'core',
                'icon'            => $def['icon'],
                'allowedChildren' => ['layout', 'block', 'component', 'snippet'],
                'fields'          => $def['fields'],
                'defaultProps'    => $def['defaultProps'],
            ];
        }
    }

    private function loadCoreBlocks(): void
    {
        $this->blocks = [
            'text' => [
                'id' => 'text', 'type' => 'block', 'label' => 'Text',
                'description' => 'Textblock mit Formatierung', 'category' => 'text',
                'source' => 'core', 'icon' => 'text', 'allowedChildren' => [],
                'fields' => [
                    ['name' => 'content', 'type' => 'richtext', 'label' => 'Inhalt', 'required' => true],
                    ['name' => 'alignment', 'type' => 'select', 'label' => 'Ausrichtung', 'options' => ['left' => 'Links', 'center' => 'Zentriert', 'right' => 'Rechts']],
                ],
                'defaultProps' => ['content' => '', 'alignment' => 'left'],
            ],
            'heading' => [
                'id' => 'heading', 'type' => 'block', 'label' => 'Überschrift',
                'description' => 'Überschriftenblock', 'category' => 'text',
                'source' => 'core', 'icon' => 'heading', 'allowedChildren' => [],
                'fields' => [
                    ['name' => 'text', 'type' => 'text', 'label' => 'Text', 'required' => true],
                    ['name' => 'level', 'type' => 'select', 'label' => 'Ebene', 'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4']],
                    ['name' => 'alignment', 'type' => 'select', 'label' => 'Ausrichtung', 'options' => ['left' => 'Links', 'center' => 'Zentriert', 'right' => 'Rechts']],
                ],
                'defaultProps' => ['text' => 'Neue Überschrift', 'level' => 'h2', 'alignment' => 'left'],
            ],
            'image' => [
                'id' => 'image', 'type' => 'block', 'label' => 'Bild',
                'description' => 'Einzelbild mit optionaler Beschriftung', 'category' => 'media',
                'source' => 'core', 'icon' => 'image', 'allowedChildren' => [],
                'fields' => [
                    ['name' => 'src', 'type' => 'media', 'label' => 'Bild'],
                    ['name' => 'alt', 'type' => 'text', 'label' => 'Alternativtext'],
                    ['name' => 'caption', 'type' => 'text', 'label' => 'Bildunterschrift'],
                ],
                'defaultProps' => ['src' => '', 'alt' => '', 'caption' => ''],
            ],
            'video' => [
                'id' => 'video', 'type' => 'block', 'label' => 'Video',
                'description' => 'Eingebettetes Video', 'category' => 'media',
                'source' => 'core', 'icon' => 'video', 'allowedChildren' => [],
                'fields' => [
                    ['name' => 'url', 'type' => 'text', 'label' => 'Video-URL'],
                    ['name' => 'autoplay', 'type' => 'toggle', 'label' => 'Autoplay'],
                ],
                'defaultProps' => ['url' => '', 'autoplay' => false],
            ],
            'button' => [
                'id' => 'button', 'type' => 'block', 'label' => 'Button',
                'description' => 'Klickbarer Aktionsbutton', 'category' => 'ui',
                'source' => 'core', 'icon' => 'button', 'allowedChildren' => [],
                'fields' => [
                    ['name' => 'label', 'type' => 'text', 'label' => 'Beschriftung', 'required' => true],
                    ['name' => 'url', 'type' => 'text', 'label' => 'Link-URL'],
                    ['name' => 'variant', 'type' => 'select', 'label' => 'Variante', 'options' => ['primary' => 'Primär', 'secondary' => 'Sekundär', 'ghost' => 'Ghost']],
                    ['name' => 'size', 'type' => 'select', 'label' => 'Größe', 'options' => ['sm' => 'Klein', 'md' => 'Mittel', 'lg' => 'Groß']],
                ],
                'defaultProps' => ['label' => 'Klick mich', 'url' => '#', 'variant' => 'primary', 'size' => 'md'],
            ],
            'spacer' => [
                'id' => 'spacer', 'type' => 'block', 'label' => 'Abstand',
                'description' => 'Vertikaler Abstandshalter', 'category' => 'layout',
                'source' => 'core', 'icon' => 'spacer', 'allowedChildren' => [],
                'fields' => [
                    ['name' => 'height', 'type' => 'select', 'label' => 'Höhe', 'options' => ['sm' => 'Klein (16px)', 'md' => 'Mittel (32px)', 'lg' => 'Groß (64px)', 'xl' => 'Sehr groß (96px)']],
                ],
                'defaultProps' => ['height' => 'md'],
            ],
            'divider' => [
                'id' => 'divider', 'type' => 'block', 'label' => 'Trennlinie',
                'description' => 'Horizontale Trennlinie', 'category' => 'layout',
                'source' => 'core', 'icon' => 'divider', 'allowedChildren' => [],
                'fields' => [
                    ['name' => 'style', 'type' => 'select', 'label' => 'Stil', 'options' => ['solid' => 'Durchgehend', 'dashed' => 'Gestrichelt', 'dotted' => 'Gepunktet']],
                ],
                'defaultProps' => ['style' => 'solid'],
            ],
        ];
    }

    private function loadCoreComponents(): void
    {
        // Import from ComponentManager
        foreach ($this->kernel->components()->getAllComponents() as $comp) {
            $this->components[$comp['id']] = [
                'id'              => $comp['id'],
                'type'            => 'component',
                'label'           => $comp['label'],
                'description'     => $comp['description'] ?? '',
                'category'        => $comp['category'] ?? 'general',
                'source'          => $comp['source'] ?? 'core',
                'icon'            => $comp['icon'] ?? 'component',
                'allowedChildren' => [],
                'fields'          => $comp['fields'] ?? [],
                'defaultProps'    => [],
            ];
        }

        // Core editor components
        $coreComponents = [
            [
                'id' => 'hero', 'label' => 'Hero',
                'description' => 'Großer Headerbereich mit Bild und Text',
                'category' => 'marketing', 'icon' => 'hero',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Titel', 'required' => true],
                    ['name' => 'subtitle', 'type' => 'textarea', 'label' => 'Untertitel'],
                    ['name' => 'backgroundImage', 'type' => 'media', 'label' => 'Hintergrundbild'],
                    ['name' => 'ctaLabel', 'type' => 'text', 'label' => 'CTA-Button Text'],
                    ['name' => 'ctaUrl', 'type' => 'text', 'label' => 'CTA-Button Link'],
                    ['name' => 'alignment', 'type' => 'select', 'label' => 'Ausrichtung', 'options' => ['left' => 'Links', 'center' => 'Zentriert', 'right' => 'Rechts']],
                ],
                'defaultProps' => ['title' => 'Willkommen', 'subtitle' => '', 'alignment' => 'center'],
            ],
            [
                'id' => 'cta', 'label' => 'Call-to-Action',
                'description' => 'Aufforderungsbereich mit Button',
                'category' => 'marketing', 'icon' => 'cta',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Titel', 'required' => true],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Text'],
                    ['name' => 'buttonLabel', 'type' => 'text', 'label' => 'Button-Text'],
                    ['name' => 'buttonUrl', 'type' => 'text', 'label' => 'Button-Link'],
                    ['name' => 'variant', 'type' => 'select', 'label' => 'Variante', 'options' => ['default' => 'Standard', 'highlight' => 'Hervorgehoben']],
                ],
                'defaultProps' => ['title' => 'Jetzt starten', 'variant' => 'default'],
            ],
            [
                'id' => 'feature_grid', 'label' => 'Feature Grid',
                'description' => 'Raster mit Feature-Beschreibungen',
                'category' => 'content', 'icon' => 'grid',
                'allowedChildren' => ['block', 'snippet'],
                'fields' => [
                    ['name' => 'columns', 'type' => 'select', 'label' => 'Spalten', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
                    ['name' => 'title', 'type' => 'text', 'label' => 'Titel'],
                ],
                'defaultProps' => ['columns' => '3', 'title' => ''],
            ],
            [
                'id' => 'faq', 'label' => 'FAQ',
                'description' => 'Häufig gestellte Fragen (Akkordeon)',
                'category' => 'content', 'icon' => 'faq',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Titel'],
                    ['name' => 'items', 'type' => 'textarea', 'label' => 'Fragen & Antworten (JSON)'],
                ],
                'defaultProps' => ['title' => 'Häufig gestellte Fragen', 'items' => '[]'],
            ],
        ];

        foreach ($coreComponents as $def) {
            $this->components[$def['id']] = [
                'id'              => $def['id'],
                'type'            => 'component',
                'label'           => $def['label'],
                'description'     => $def['description'],
                'category'        => $def['category'],
                'source'          => 'core',
                'icon'            => $def['icon'],
                'allowedChildren' => $def['allowedChildren'] ?? [],
                'fields'          => $def['fields'],
                'defaultProps'    => $def['defaultProps'],
            ];
        }
    }

    private function loadCoreSnippets(): void
    {
        $this->snippets = [
            'infobox' => [
                'id' => 'infobox', 'type' => 'snippet', 'label' => 'Infobox',
                'description' => 'Hervorgehobener Informationsblock',
                'category' => 'content', 'source' => 'core', 'icon' => 'info',
                'allowedChildren' => [],
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'Titel'],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Text', 'required' => true],
                    ['name' => 'type', 'type' => 'select', 'label' => 'Typ', 'options' => ['info' => 'Info', 'warning' => 'Warnung', 'success' => 'Erfolg', 'error' => 'Fehler']],
                ],
                'defaultProps' => ['title' => '', 'text' => '', 'type' => 'info'],
            ],
            'notice' => [
                'id' => 'notice', 'type' => 'snippet', 'label' => 'Hinweis',
                'description' => 'Kurzer Hinweistext',
                'category' => 'content', 'source' => 'core', 'icon' => 'notice',
                'allowedChildren' => [],
                'fields' => [
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Hinweistext', 'required' => true],
                    ['name' => 'variant', 'type' => 'select', 'label' => 'Variante', 'options' => ['default' => 'Standard', 'important' => 'Wichtig']],
                ],
                'defaultProps' => ['text' => '', 'variant' => 'default'],
            ],
            'contact_short' => [
                'id' => 'contact_short', 'type' => 'snippet', 'label' => 'Kontakt (Kurz)',
                'description' => 'Kompakte Kontaktinformation',
                'category' => 'content', 'source' => 'core', 'icon' => 'contact',
                'allowedChildren' => [],
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Name'],
                    ['name' => 'email', 'type' => 'text', 'label' => 'E-Mail'],
                    ['name' => 'phone', 'type' => 'text', 'label' => 'Telefon'],
                ],
                'defaultProps' => ['name' => '', 'email' => '', 'phone' => ''],
            ],
        ];
    }

    // ─── Theme / Module Extensions ────────────────────────────

    private function loadFromTheme(): void
    {
        $themeManager = $this->kernel->themes();
        $themeId = $themeManager->getFrontendThemeId();
        $activeTheme = $themeManager->getTheme('frontend', $themeId);

        if (!$activeTheme) {
            return;
        }

        // 1. Load from theme config array
        $editorDefs = $activeTheme['editor_definitions'] ?? [];
        $this->registerBulk($editorDefs, 'theme');

        // 2. Load from JSON file in theme directory
        $themePath = $themeManager->getFrontendThemePath();
        $jsonPath = $themePath . '/editor-definitions.json';
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            if (is_array($json)) {
                $this->registerBulk($json, 'theme');
            }
        }
    }

    private function loadFromModules(): void
    {
        if (!method_exists($this->kernel, 'modules')) {
            return;
        }

        $modules = $this->kernel->modules();
        foreach ($modules->getActive() as $moduleId => $module) {
            // 1. Load from module config array
            $editorDefs = $module['editor_definitions'] ?? [];
            $this->registerBulk($editorDefs, 'module');

            // 2. Load from JSON file in module directory
            $modulePath = $module['path'] ?? $modules->getModulePath($moduleId);
            if ($modulePath) {
                $jsonPath = $modulePath . '/editor-definitions.json';
                if (file_exists($jsonPath)) {
                    $json = json_decode(file_get_contents($jsonPath), true);
                    if (is_array($json)) {
                        $this->registerBulk($json, 'module');
                    }
                }
            }
        }
    }

    private function registerBulk(array $editorDefs, string $source): void
    {
        foreach (['layouts', 'blocks', 'components', 'snippets'] as $group) {
            if (!isset($editorDefs[$group])) {
                continue;
            }
            $type = rtrim($group, 's');
            foreach ($editorDefs[$group] as $id => $def) {
                $def['source'] = $source;
                $this->register($type, $id, $def);
            }
        }
    }

    private function loadUserComponents(): void
    {
        $storagePath = $this->kernel->config()->get('STORAGE_PATH', 'storage');
        $filePath = $storagePath . '/editor/user-components.json';

        if (!file_exists($filePath)) {
            return;
        }

        $data = json_decode(file_get_contents($filePath), true);
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $id => $def) {
            $def['source'] = 'user';
            $this->register('component', $id, $def);
        }
    }

    private function loadCustomDefinitions(): void
    {
        $storagePath = $this->kernel->config()->get('STORAGE_PATH', 'storage');
        $filePath = $storagePath . '/editor/custom-definitions.json';

        if (!file_exists($filePath)) {
            return;
        }

        $data = json_decode((string) file_get_contents($filePath), true);
        if (!is_array($data)) {
            return;
        }

        $this->registerBulk($data, 'user');
    }

    // ─── Validation ───────────────────────────────────────────

    private function validateNode(array $node, ?array $parentDef, array &$errors, int $depth = 0): void
    {
        if ($depth > 50) {
            $errors[] = "Maximum nesting depth exceeded at node '{$node['id']}'";
            return;
        }

        $type = $node['type'] ?? 'unknown';
        $definition = $node['definition'] ?? null;

        // Root node check
        if ($type === 'root') {
            foreach ($node['children'] ?? [] as $child) {
                $this->validateNode($child, null, $errors, $depth + 1);
            }
            return;
        }

        // Verify definition exists
        if ($definition) {
            $def = $this->getDefinition($type, $definition);
            if (!$def) {
                $errors[] = "Unknown definition '{$definition}' for type '{$type}' at node '{$node['id']}'";
            }
        }

        // Check parent acceptance
        if ($parentDef !== null) {
            $allowed = $parentDef['allowedChildren'] ?? [];
            if (!empty($allowed) && !in_array($type, $allowed, true)) {
                $errors[] = "Node '{$node['id']}' (type={$type}) not allowed in parent '{$parentDef['id']}'";
            }
        }

        // Validate required fields
        if ($definition) {
            $def = $this->getDefinition($type, $definition);
            if ($def) {
                foreach ($def['fields'] ?? [] as $field) {
                    if (!empty($field['required'])) {
                        $value = $node['props'][$field['name']] ?? null;
                        if ($value === null || $value === '') {
                            $errors[] = "Required field '{$field['name']}' is empty at node '{$node['id']}'";
                        }
                    }
                }
            }
        }

        // Recurse into children
        $currentDef = $definition ? $this->getDefinition($type, $definition) : null;
        foreach ($node['children'] ?? [] as $child) {
            $this->validateNode($child, $currentDef, $errors, $depth + 1);
        }
    }
}
