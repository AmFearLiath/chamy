<?php
/**
 * Name: routes
 * Beschreibung: Definiert die Routen.
 *
 * Author: Markus
 * Datei: app/routes.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Datei definiert die Routen im Framework.
 *  
 */

use Core\Classes\Database;
use Core\Classes\Router;

// Holen des Backend-Pfads aus der Datenbank
$database = new Database;
$router = new Router();

$frontendPath = $database->getSetting('frontend_path');
$backendPath = $database->getSetting('backend_path');

if ($frontendPath) {
    $frontendPath = "\\$frontendPath";
} else {
    $frontendPath = "\\";
}

if ($backendPath && $frontendPath) {
    $backendPath = "$frontendPath\\$backendPath";
} else {
    $backendPath = '\/backend'; // Standardpfad, falls nichts in der Datenbank gefunden wird
}

// Füge einige Routen hinzu
$router->add($frontendPath . '\/{page}?', array ( // Verwenden Sie geschweifte Klammern für das dynamische Segment
    'controller' => 'HomeController',
    'action' => 'index'
), array ('GET', 'POST'));
$router->add($backendPath.'\/{page}?', array ( // Verwenden Sie geschweifte Klammern für das dynamische Segment
    'controller' => 'AdminController',
    'action' => 'index'
), array ('GET', 'POST'));

/*
// Verwende den Router, um Routen zu definieren
\Core\Classes\Router::get($frontendPath . '/', 'HomeController', 'index');
\Core\Classes\Router::get($frontendPath . '/register', 'UserController', 'register');
\Core\Classes\Router::get($frontendPath . '/login', 'UserController', 'login');
\Core\Classes\Router::get($frontendPath . '/logout', 'UserController', 'logout');
\Core\Classes\Router::get($frontendPath . '/user/{username}', 'UserController', 'profile');
\Core\Classes\Router::get($frontendPath . '/settings/{name}', 'SettingsController', 'getSetting');

// Neue Route für das Backend mit dem dynamischen Pfad
\Core\Classes\Router::get($backendPath, 'AdminController', 'index');
\Core\Classes\Router::get($backendPath.'/', 'AdminController', 'index');
*/