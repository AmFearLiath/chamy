<?php

use Chamy\Core\Controllers\SystemApiController;
use Chamy\Core\Controllers\ContentApiController;

// System
$router->get('/api/v1/system/health', [SystemApiController::class, 'health'], 'api.system.health', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/system/info', [SystemApiController::class, 'info'], 'api.system.info', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/system/content-types', [SystemApiController::class, 'contentTypes'], 'api.system.content_types', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/system/states', [SystemApiController::class, 'states'], 'api.system.states', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);

// Content public endpoints
$router->get('/api/v1/content/{type}', [ContentApiController::class, 'list'], 'api.content.list', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/content/{type}/{id}', [ContentApiController::class, 'show'], 'api.content.show', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);

// Content admin endpoints (require API auth)
$router->post('/api/v1/content/{type}', [ContentApiController::class, 'store'], 'api.content.store', ['Chamy\\Core\\Http\\Middleware\\ApiAuthMiddleware','Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->put('/api/v1/content/{type}/{id}', [ContentApiController::class, 'update'], 'api.content.update', ['Chamy\\Core\\Http\\Middleware\\ApiAuthMiddleware','Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->delete('/api/v1/content/{type}/{id}', [ContentApiController::class, 'destroy'], 'api.content.destroy', ['Chamy\\Core\\Http\\Middleware\\ApiAuthMiddleware','Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);

// Types
$router->get('/api/v1/types', [ContentApiController::class, 'types'], 'api.types.list', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
