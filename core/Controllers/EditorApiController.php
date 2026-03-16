<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Editor\DefinitionRegistry;
use Chamy\Core\Editor\EditorRenderer;
use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

/**
 * Editor API Controller – Handles all /api/v1/editor/* routes.
 *
 * Provides JSON endpoints for the visual content editor:
 * - Load editor data (content + type + definitions)
 * - Save editor structure
 * - Preview rendering
 * - State transitions
 * - Definition registry
 */
final class EditorApiController extends BaseApiController
{
    private DefinitionRegistry $registry;
    private EditorRenderer $renderer;

    public function __construct(Kernel $kernel)
    {
        parent::__construct($kernel);
        $this->registry = new DefinitionRegistry($kernel);
        $this->renderer = new EditorRenderer($kernel);
    }

    /**
     * GET /api/v1/editor/definitions
     * Returns all available editor definitions (layouts, blocks, components, snippets).
     */
    public function definitions(Request $request): Response
    {
        return $this->success($this->registry->getAll());
    }

    /**
     * GET /api/v1/editor/{contentId}
     * Loads editor data for a specific content entry.
     */
    public function load(Request $request): Response
    {
        $contentId = (int) $request->getRouteParam('contentId');

        $entry = $this->kernel->data()->getContentById($contentId);
        if (!$entry) {
            return $this->error('not_found', 'Content entry not found.', 404);
        }

        $typeKey = $entry['content_type'];
        $type = $this->kernel->contentTypes()->getType($typeKey);
        if (!$type) {
            return $this->error('invalid_type', 'Content type not found.', 404);
        }

        $entryData = $entry['_data'] ?? (is_array($entry['data']) ? $entry['data'] : json_decode($entry['data'] ?? '{}', true));

        $emptyEditorData = [
            'version'     => 1,
            'contentType' => $typeKey,
            'root'        => [
                'id'       => 'root_1',
                'type'     => 'root',
                'children' => [],
            ],
        ];

        // Extract editor structure from data (new key: editor_data, legacy key: editor)
        $editorData = $entryData['editor_data'] ?? $entryData['editor'] ?? $emptyEditorData;

        // Legacy prefill: if no editor tree exists yet, bootstrap one from body HTML.
        // This allows older page/article entries to open with existing content instead of an empty canvas.
        if ($editorData === $emptyEditorData && !empty($entryData['body']) && is_string($entryData['body'])) {
            $editorData['root']['children'][] = [
                'id'         => 'node_legacy_body',
                'type'       => 'block',
                'definition' => 'rich_text',
                'props'      => [
                    'content' => $entryData['body'],
                    'align'   => 'left',
                ],
                'children'   => [],
            ];
        }

        // Normalize legacy/malformed payloads so the editor UI always receives a valid tree.
        if (!is_array($editorData)) {
            $editorData = [
                'version'     => 1,
                'contentType' => $typeKey,
                'root'        => [
                    'id'       => 'root_1',
                    'type'     => 'root',
                    'children' => [],
                ],
            ];
        }
        if (!isset($editorData['version'])) {
            $editorData['version'] = 1;
        }
        if (!isset($editorData['contentType'])) {
            $editorData['contentType'] = $typeKey;
        }
        if (!isset($editorData['root']) || !is_array($editorData['root'])) {
            $editorData['root'] = [
                'id'       => 'root_1',
                'type'     => 'root',
                'children' => [],
            ];
        }
        if (empty($editorData['root']['children']) && !empty($entryData['body']) && is_string($entryData['body'])) {
            $editorData['root']['children'] = [[
                'id'         => 'node_legacy_body',
                'type'       => 'block',
                'definition' => 'rich_text',
                'props'      => [
                    'content' => $entryData['body'],
                    'align'   => 'left',
                ],
                'children'   => [],
            ]];
        }

        $versions = $this->kernel->versions()->getVersions($contentId);

        $userRoles = $this->kernel->session()->get('user_roles', []);
        $definitions = $this->registry->getFiltered($typeKey, $userRoles);

        $currentStatus = $entry['status'] ?? 'draft';
        $availableTransitions = $this->kernel->states()->getAvailableTransitions($currentStatus);

        return $this->success([
            'content'     => [
                'id'          => $entry['id'],
                'uuid'        => $entry['uuid'] ?? null,
                'contentType' => $typeKey,
                'status'      => $currentStatus,
                'version'     => $entry['version'] ?? 1,
                'createdAt'   => $entry['created_at'] ?? null,
                'updatedAt'   => $entry['updated_at'] ?? null,
                'publishedAt' => $entry['published_at'] ?? null,
                'title'       => $entryData['title'] ?? '',
                'slug'        => $entryData['slug'] ?? '',
            ],
            'contentType' => $type,
            'editor'      => $editorData,
            'definitions' => $definitions,
            'availableTransitions' => $availableTransitions,
            'versions'    => array_map(fn($v) => [
                'version'   => $v['version_number'] ?? $v['version'] ?? null,
                'createdAt' => $v['created_at'] ?? null,
                'note'      => $v['note'] ?? '',
            ], $versions),
        ]);
    }

    /**
     * PUT /api/v1/editor/{contentId}
     * Saves the editor structure for a content entry.
     */
    public function save(Request $request): Response
    {
        $contentId = (int) $request->getRouteParam('contentId');

        $entry = $this->kernel->data()->getContentById($contentId);
        if (!$entry) {
            return $this->error('not_found', 'Content entry not found.', 404);
        }

        $body = $request->getJsonBody();
        if (!$body || !isset($body['editor'])) {
            return $this->error('invalid_payload', 'Missing editor data.', 422);
        }

        // Validate editor structure
        $editorData = $body['editor'];
        if (!isset($editorData['root']) || !isset($editorData['version'])) {
            return $this->error('invalid_structure', 'Editor data must contain root and version.', 422);
        }

        // Validate editor tree against definition constraints
        $treeErrors = $this->registry->validateTree($editorData['root']);
        if (!empty($treeErrors)) {
            return $this->error('validation_error', 'Editor tree contains invalid nodes.', 422, [
                'fields' => $treeErrors,
            ]);
        }

        // Merge editor data into existing content data.
        // Canonical key is editor_data; keep legacy editor key for compatibility.
        $entryData = $entry['_data'] ?? (is_array($entry['data']) ? $entry['data'] : json_decode($entry['data'] ?? '{}', true));
        $entryData['editor_data'] = $editorData;
        $entryData['editor'] = $editorData;

        $userId = $this->kernel->session()->get('user_id');
        $comment = $body['comment'] ?? '';

        // Update content
        $this->kernel->data()->updateContent($contentId, $entryData, $userId);

        // Create version
        $this->kernel->versions()->createVersion($contentId, $entryData, $userId, $comment ?: 'Editor update');

        return $this->success([
            'saved'   => true,
            'version' => ($entry['version'] ?? 0) + 1,
        ], 'Content saved successfully.');
    }

    /**
     * POST /api/v1/editor/{contentId}/state
     * Changes the state of a content entry.
     */
    public function changeState(Request $request): Response
    {
        $contentId = (int) $request->getRouteParam('contentId');

        $entry = $this->kernel->data()->getContentById($contentId);
        if (!$entry) {
            return $this->error('not_found', 'Content entry not found.', 404);
        }

        $body = $request->getJsonBody();
        $newState = $body['newState'] ?? null;

        if (!$newState) {
            return $this->error('missing_state', 'newState is required.', 422);
        }

        $currentState = $entry['status'] ?? 'draft';

        if (!$this->kernel->states()->canTransition($currentState, $newState)) {
            return $this->error('invalid_transition', "Cannot transition from '{$currentState}' to '{$newState}'.", 422);
        }

        // Apply state change
        $source = $this->kernel->config()->get('DATA_SOURCE', 'mock');
        if ($source === 'live') {
            $updateData = ['status' => $newState, 'updated_at' => date('Y-m-d H:i:s')];
            if ($newState === 'published' && empty($entry['published_at'])) {
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }
            $this->kernel->db()->update('content_entries', $updateData, 'id = :id', ['id' => $contentId]);
        }

        return $this->success([
            'previousState'        => $currentState,
            'newState'             => $newState,
            'availableTransitions' => $this->kernel->states()->getAvailableTransitions($newState),
        ], 'State changed successfully.');
    }

    /**
     * POST /api/v1/editor/{contentId}/preview
     * Renders a preview of the editor structure via the theme system.
     */
    public function preview(Request $request): Response
    {
        $contentId = (int) $request->getRouteParam('contentId');

        $entry = $this->kernel->data()->getContentById($contentId);
        if (!$entry) {
            return $this->error('not_found', 'Content entry not found.', 404);
        }

        $body = $request->getJsonBody();
        $editorData = $body['editor'] ?? null;
        if (!$editorData) {
            return $this->error('missing_editor', 'Editor data is required for preview.', 422);
        }

        $typeKey = $entry['content_type'] ?? 'page';
        $html = $this->renderer->render($editorData, $typeKey);

        return $this->success([
            'html'       => $html,
            'successful' => true,
        ]);
    }

    /**
     * POST /api/v1/editor/{contentId}/restore
     * Restores a specific version of the content.
     */
    public function restore(Request $request): Response
    {
        $contentId = (int) $request->getRouteParam('contentId');

        $entry = $this->kernel->data()->getContentById($contentId);
        if (!$entry) {
            return $this->error('not_found', 'Content entry not found.', 404);
        }

        $body = $request->getJsonBody();
        $version = $body['version'] ?? null;

        if (!$version) {
            return $this->error('missing_version', 'Version number is required.', 422);
        }

        $versionData = $this->kernel->versions()->getVersion($contentId, (int) $version);
        if (!$versionData) {
            return $this->error('version_not_found', 'Version not found.', 404);
        }

        $restoredData = $versionData['data'] ?? $versionData['_data'] ?? null;
        if (is_string($restoredData)) {
            $restoredData = json_decode($restoredData, true);
        }

        $restoredEditor = $restoredData['editor_data'] ?? $restoredData['editor'] ?? null;
        if (!$restoredData || !is_array($restoredEditor)) {
            return $this->error('no_editor_data', 'Version does not contain editor data.', 422);
        }

        return $this->success([
            'editor'  => $restoredEditor,
            'version' => $version,
        ], 'Version restored.');
    }

    /**
     * POST /api/v1/editor/components/save
     * Saves a node tree as a user-defined component.
     */
    public function saveComponent(Request $request): Response
    {
        $body = $request->getJsonBody();

        $name = trim($body['name'] ?? '');
        $node = $body['node'] ?? null;

        if (!$name) {
            return $this->error('missing_name', 'Component name is required.', 422);
        }
        if (!$node || !isset($node['type'])) {
            return $this->error('missing_node', 'Node data is required.', 422);
        }

        $id = preg_replace('/[^a-z0-9_]/', '_', strtolower($name));
        $category = $body['category'] ?? 'Eigene Komponenten';

        $definition = [
            'id'       => $id,
            'type'     => 'component',
            'label'    => $name,
            'icon'     => 'star',
            'category' => $category,
            'source'   => 'user',
            'template' => $node,
            'fields'   => [],
        ];

        // Store in user-components JSON file
        $storagePath = $this->kernel->config()->get('STORAGE_PATH', 'storage');
        $filePath = $storagePath . '/editor/user-components.json';
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $existing = [];
        if (file_exists($filePath)) {
            $existing = json_decode(file_get_contents($filePath), true) ?: [];
        }
        $existing[$id] = $definition;
        file_put_contents($filePath, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Register in runtime registry
        $this->registry->register('component', $id, $definition);

        return $this->success([
            'id'         => $id,
            'definition' => $definition,
        ], 'Component saved.');
    }

    /**
     * GET /api/v1/editor/globals
     * Returns all global components.
     */
    public function listGlobals(Request $request): Response
    {
        $storagePath = $this->kernel->config()->get('STORAGE_PATH', 'storage');
        $filePath = $storagePath . '/editor/global-components.json';

        $globals = [];
        if (file_exists($filePath)) {
            $globals = json_decode(file_get_contents($filePath), true) ?: [];
        }

        return $this->success(['globals' => $globals]);
    }

    /**
     * POST /api/v1/editor/globals/save
     * Saves or updates a global component.
     */
    public function saveGlobal(Request $request): Response
    {
        $body = $request->getJsonBody();

        $name = trim($body['name'] ?? '');
        $node = $body['node'] ?? null;
        $referenceId = $body['referenceId'] ?? null;

        if (!$name) {
            return $this->error('missing_name', 'Name is required.', 422);
        }
        if (!$node || !isset($node['type'])) {
            return $this->error('missing_node', 'Node data is required.', 422);
        }

        $storagePath = $this->kernel->config()->get('STORAGE_PATH', 'storage');
        $filePath = $storagePath . '/editor/global-components.json';
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $globals = [];
        if (file_exists($filePath)) {
            $globals = json_decode(file_get_contents($filePath), true) ?: [];
        }

        $id = $referenceId ?: 'global_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($name)) . '_' . time();

        $globals[$id] = [
            'referenceId' => $id,
            'name'        => $name,
            'node'        => $node,
            'updatedAt'   => date('Y-m-d H:i:s'),
        ];

        file_put_contents($filePath, json_encode($globals, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $this->success([
            'referenceId' => $id,
            'global'      => $globals[$id],
        ], 'Global component saved.');
    }

    /**
     * GET /api/v1/editor/globals/{referenceId}
     * Loads a specific global component by its reference ID.
     */
    public function loadGlobal(Request $request): Response
    {
        $referenceId = $request->getRouteParam('referenceId');

        $storagePath = $this->kernel->config()->get('STORAGE_PATH', 'storage');
        $filePath = $storagePath . '/editor/global-components.json';

        if (!file_exists($filePath)) {
            return $this->error('not_found', 'Global component not found.', 404);
        }

        $globals = json_decode(file_get_contents($filePath), true) ?: [];
        $global = $globals[$referenceId] ?? null;

        if (!$global) {
            return $this->error('not_found', 'Global component not found.', 404);
        }

        return $this->success($global);
    }

    /**
     * GET /api/v1/editor/content-search?type=page&q=keyword
     * Searches content entries for reference picking.
     */
    public function searchContent(Request $request): Response
    {
        $type = $request->getQuery('type', '');
        $query = $request->getQuery('q', '');
        $limit = (int) $request->getQuery('limit', '20');

        if (!$type) {
            // Return all types
            $types = $this->kernel->contentTypes()->getAllTypes();
            $results = [];
            foreach ($types as $key => $typeDef) {
                $entries = $this->kernel->data()->getContentEntries($key, null, min($limit, 50));
                foreach ($entries as $entry) {
                    $data = $entry['_data'] ?? (is_array($entry['data']) ? $entry['data'] : []);
                    $title = $data['title'] ?? $entry['title'] ?? 'Untitled';

                    if ($query && stripos($title, $query) === false) {
                        continue;
                    }

                    $results[] = [
                        'id'          => $entry['id'],
                        'contentType' => $key,
                        'title'       => $title,
                        'status'      => $entry['status'] ?? 'draft',
                    ];
                }
            }

            return $this->success(['results' => array_slice($results, 0, $limit)]);
        }

        $entries = $this->kernel->data()->getContentEntries($type, null, min($limit, 50));
        $results = [];
        foreach ($entries as $entry) {
            $data = $entry['_data'] ?? (is_array($entry['data']) ? $entry['data'] : []);
            $title = $data['title'] ?? $entry['title'] ?? 'Untitled';

            if ($query && stripos($title, $query) === false) {
                continue;
            }

            $results[] = [
                'id'          => $entry['id'],
                'contentType' => $type,
                'title'       => $title,
                'status'      => $entry['status'] ?? 'draft',
            ];
        }

        return $this->success(['results' => $results]);
    }

}
