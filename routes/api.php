<?php

use Chamy\Core\Controllers\SystemApiController;
use Chamy\Core\Controllers\ContentApiController;
use Chamy\Core\Controllers\EditorApiController;

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

// Editor API (session-auth – used from admin UI)
$router->get('/api/v1/editor/definitions', [EditorApiController::class, 'definitions'], 'api.editor.definitions', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/editor/{contentId}', [EditorApiController::class, 'load'], 'api.editor.load', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->put('/api/v1/editor/{contentId}', [EditorApiController::class, 'save'], 'api.editor.save', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->post('/api/v1/editor/{contentId}/preview', [EditorApiController::class, 'preview'], 'api.editor.preview', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->post('/api/v1/editor/{contentId}/state', [EditorApiController::class, 'changeState'], 'api.editor.state', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->post('/api/v1/editor/{contentId}/restore', [EditorApiController::class, 'restore'], 'api.editor.restore', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->post('/api/v1/editor/components/save', [EditorApiController::class, 'saveComponent'], 'api.editor.saveComponent', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/editor/globals', [EditorApiController::class, 'listGlobals'], 'api.editor.globals', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->post('/api/v1/editor/globals/save', [EditorApiController::class, 'saveGlobal'], 'api.editor.saveGlobal', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/editor/globals/{referenceId}', [EditorApiController::class, 'loadGlobal'], 'api.editor.loadGlobal', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
$router->get('/api/v1/editor/content-search', [EditorApiController::class, 'searchContent'], 'api.editor.contentSearch', ['Chamy\\Core\\Http\\Middleware\\CorsMiddleware']);
