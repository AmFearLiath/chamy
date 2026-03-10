<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

abstract class BaseApiController
{
    protected Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): Response
    {
        $payload = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        return Response::json($payload, $status);
    }

    protected function created(mixed $data = null, string $message = 'Created'): Response
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $code, string $message, int $status = 400, array $details = []): Response
    {
        $payload = ['success' => false, 'error' => $code, 'message' => $message];
        if (!empty($details)) {
            $payload['details'] = $details;
        }
        return Response::json($payload, $status);
    }

    protected function notFound(string $message = 'Resource not found.'): Response
    {
        return $this->error('not_found', $message, 404);
    }

    protected function forbidden(string $message = 'Access denied.'): Response
    {
        return $this->error('forbidden', $message, 403);
    }

    protected function paginate(array $items, int $total, int $page, int $perPage): Response
    {
        return $this->success([
            'items'        => $items,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    protected function apiUserId(Request $request): ?int
    {
        $id = $request->getRouteParam('_api_user_id');
        return $id !== null ? (int) $id : null;
    }
}
