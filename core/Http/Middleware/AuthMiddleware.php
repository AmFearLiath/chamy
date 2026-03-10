<?php

declare(strict_types=1);

namespace Chamy\Core\Http\Middleware;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $kernel = Kernel::getInstance();
        $userId = $kernel->session()->get('user_id');

        if (!$userId) {
            if ($request->expectsJson() || str_starts_with($request->getPath(), '/api/')) {
                return Response::apiError('unauthorized', 'Authentication required.', 401);
            }
            return Response::redirect('/admin/login');
        }

        return $next($request);
    }
}
