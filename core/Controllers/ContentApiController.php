<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;

final class ContentApiController extends BaseApiController
{
    public function list(Request $request): Response
    {
        $typeKey = $request->getRouteParam('type');
        $type    = $this->kernel->contentTypes()->getType($typeKey);

        if (!$type) {
            return $this->notFound('Content type not found.');
        }

        $page    = max(1, (int) ($request->getQuery('page', '1')));
        $perPage = min(100, max(1, (int) ($request->getQuery('per_page', '20'))));
        $offset  = ($page - 1) * $perPage;
        $status  = $request->getQuery('status');

        $data  = $this->kernel->data();
        $total = $data->countContent($typeKey, $status ?: null);
        $entries = $data->getContentEntries($typeKey, $status ?: null, $perPage, $offset);

        $items = array_map(fn($e) => $this->formatEntry($e), $entries);

        return $this->paginate($items, $total, $page, $perPage);
    }

    public function show(Request $request): Response
    {
        $id    = (int) $request->getRouteParam('id');
        $entry = $this->kernel->data()->getContentById($id);

        if (!$entry || ($entry['status'] ?? '') === 'deleted') {
            return $this->notFound('Entry not found.');
        }

        return $this->success($this->formatEntry($entry));
    }

    public function store(Request $request): Response
    {
        $typeKey = $request->getRouteParam('type');
        $type    = $this->kernel->contentTypes()->getType($typeKey);

        if (!$type) {
            return $this->notFound('Content type not found.');
        }

        $body = $request->getJsonBody();
        $data = $body['data'] ?? [];

        if (empty($data)) {
            return $this->error('validation_error', 'Field "data" is required.');
        }

        $userId = $this->apiUserId($request);

        $entry = $this->kernel->data()->createContent($typeKey, $data, $userId);

        return $this->created($this->formatEntry($entry));
    }

    public function update(Request $request): Response
    {
        $id    = (int) $request->getRouteParam('id');
        $entry = $this->kernel->data()->getContentById($id);

        if (!$entry || ($entry['status'] ?? '') === 'deleted') {
            return $this->notFound('Entry not found.');
        }

        $body   = $request->getJsonBody();
        $data   = $body['data'] ?? [];
        $status = $body['status'] ?? null;

        if (!empty($data)) {
            $userId = $this->apiUserId($request);
            $this->kernel->data()->updateContent($id, $data, $userId);
        }

        if ($status && $status !== $entry['status']) {
            $source = $this->kernel->config()->get('DATA_SOURCE', 'mock');
            if ($source === 'live') {
                $db = $this->kernel->db();
                $updateData = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
                if ($status === 'published' && empty($entry['published_at'])) {
                    $updateData['published_at'] = date('Y-m-d H:i:s');
                }
                $db->update('content_entries', $updateData, 'id = :id', ['id' => $id]);
            }
        }

        $updated = $this->kernel->data()->getContentById($id);
        return $this->success($this->formatEntry($updated));
    }

    public function destroy(Request $request): Response
    {
        $id    = (int) $request->getRouteParam('id');
        $entry = $this->kernel->data()->getContentById($id);

        if (!$entry || ($entry['status'] ?? '') === 'deleted') {
            return $this->notFound('Entry not found.');
        }

        $this->kernel->data()->deleteContent($id);

        return $this->success(null, 'Deleted.');
    }

    public function types(Request $request): Response
    {
        $types = $this->kernel->contentTypes()->getAllTypes();

        $result = [];
        foreach ($types as $key => $type) {
            $result[] = [
                'id'           => $key,
                'label'        => $type['label'] ?? $key,
                'label_plural' => $type['label_plural'] ?? $key,
                'group'        => $type['group'] ?? 'content',
                'icon'         => $type['icon'] ?? null,
                'fields_count' => count($type['fields'] ?? []),
            ];
        }

        return $this->success($result);
    }

    private function formatEntry(array $entry): array
    {
        $data = is_array($entry['data'])
            ? $entry['data']
            : json_decode($entry['data'] ?? '{}', true);

        return [
            'id'           => (int) $entry['id'],
            'uuid'         => $entry['uuid'] ?? null,
            'content_type' => $entry['content_type'],
            'status'       => $entry['status'],
            'locale'       => $entry['locale'] ?? null,
            'version'      => (int) ($entry['version'] ?? 1),
            'data'         => $data,
            'created_at'   => $entry['created_at'] ?? null,
            'updated_at'   => $entry['updated_at'] ?? null,
            'published_at' => $entry['published_at'] ?? null,
        ];
    }
}
