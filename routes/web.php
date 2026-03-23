<?php

use Chamy\Core\Controllers\FrontendController;

// Home
$router->get('/', [FrontendController::class, 'home'], 'home');

// Services (Leistungen)
$router->get('/leistungen', [FrontendController::class, 'servicesList'], 'services.list');
$router->get('/leistungen/{slug}', [FrontendController::class, 'serviceShow'], 'services.show');

// Contact (Kontakt)
$router->get('/kontakt', [FrontendController::class, 'contact'], 'contact');
$router->post('/kontakt', [FrontendController::class, 'contactSubmit'], 'contact.submit');

// References (Referenzen)
$router->get('/referenzen', [FrontendController::class, 'references'], 'references');

// Legal pages are handled by the legal_manager module.
// The module renders legal/frontend_imprint.twig and legal/frontend_privacy.twig
// which are overridden in the elektro-keilitz theme.

// Pages (generic)
$router->get('/seiten', [FrontendController::class, 'pagesList'], 'pages.list');
$router->get('/seite/{slug}', [FrontendController::class, 'pageShow'], 'pages.show');

// Articles
$router->get('/artikel', [FrontendController::class, 'articlesList'], 'articles.list');
$router->get('/artikel/{slug}', [FrontendController::class, 'articleShow'], 'articles.show');
