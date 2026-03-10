<?php

declare(strict_types=1);

namespace Chamy\Core\Http\Middleware;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $kernel = Kernel::getInstance();
        $cors   = $kernel->config()->get('api.cors', []);

        $allowedOrigins = $cors['allowed_origins'] ?? ['*'];
        $allowedMethods = $cors['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        $allowedHeaders = $cors['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Api-Key'];
        $maxAge         = $cors['max_age'] ?? 86400;

        $origin = $request->getHeader('Origin') ?? '*';
        $originAllowed = in_array('*', $allowedOrigins, true) || in_array($origin, $allowedOrigins, true);
        $headerOrigin  = $originAllowed ? $origin : $allowedOrigins[0];

        // Preflight
        if ($request->getMethod() === 'OPTIONS') {
            return Response::html('', 204)
                ->withHeader('Access-Control-Allow-Origin', $headerOrigin)
                ->withHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $allowedHeaders))
                ->withHeader('Access-Control-Max-Age', (string) $maxAge);
        }

        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $headerOrigin);
    }
}
