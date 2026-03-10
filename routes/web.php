<?php

use Chamy\Core\Controllers\FrontendController;

// Home
$router->get('/', [FrontendController::class, 'home'], 'home');

// Pages
$router->get('/seiten', [FrontendController::class, 'pagesList'], 'pages.list');
$router->get('/seite/{slug}', [FrontendController::class, 'pageShow'], 'pages.show');

// Articles
$router->get('/artikel', [FrontendController::class, 'articlesList'], 'articles.list');
$router->get('/artikel/{slug}', [FrontendController::class, 'articleShow'], 'articles.show');
