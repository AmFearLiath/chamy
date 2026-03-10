<?php

declare(strict_types=1);

namespace Chamy\Core\Http\Middleware;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
