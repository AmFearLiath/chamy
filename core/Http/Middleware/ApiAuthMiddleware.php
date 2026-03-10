<?php

declare(strict_types=1);

namespace Chamy\Core\Http\Middleware;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

final class ApiAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $kernel = Kernel::getInstance();

        // Bearer Token
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $db     = $kernel->db();
            $prefix = $db->getPrefix();

            $apiKey = $db->fetchOne(
                "SELECT * FROM {$prefix}api_keys WHERE token = ? AND active = 1 LIMIT 1",
                [$token]
            );

            if ($apiKey) {
                $db->query(
                    "UPDATE {$prefix}api_keys SET last_used_at = NOW() WHERE id = ?",
                    [$apiKey['id']]
                );
                $request->setRouteParams(array_merge(
                    $request->getRouteParams(),
                    ['_api_user_id' => $apiKey['user_id']]
                ));
                return $next($request);
            }
        }

        // X-Api-Key
        $apiKeyHeader = $request->getHeader('X-Api-Key');
        if ($apiKeyHeader) {
            $db     = $kernel->db();
            $prefix = $db->getPrefix();

            $apiKey = $db->fetchOne(
                "SELECT * FROM {$prefix}api_keys WHERE token = ? AND active = 1 LIMIT 1",
                [$apiKeyHeader]
            );

            if ($apiKey) {
                $db->query(
                    "UPDATE {$prefix}api_keys SET last_used_at = NOW() WHERE id = ?",
                    [$apiKey['id']]
                );
                $request->setRouteParams(array_merge(
                    $request->getRouteParams(),
                    ['_api_user_id' => $apiKey['user_id']]
                ));
                return $next($request);
            }
        }

        // Session-basiert (für Admin-API-Aufrufe aus dem Browser)
        $userId = $kernel->session()->get('user_id');
        if ($userId) {
            $request->setRouteParams(array_merge(
                $request->getRouteParams(),
                ['_api_user_id' => $userId]
            ));
            return $next($request);
        }

        return Response::apiError('unauthorized', 'Authentication required.', 401);
    }
}
