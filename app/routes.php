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

// Holen des Backend-Pfads aus der Datenbank
$database = new Database;
$frontendPath = $database->getSetting('frontend_path');
$backendPath = $database->getSetting('backend_path');

if ($frontendPath) {
    $frontendPath = $frontendPath;
} else {
    $frontendPath = '';
}

// Verwende den Router, um Routen zu definieren
\Core\Classes\Router::get($frontendPath . '/', 'HomeController', 'index');
\Core\Classes\Router::get($frontendPath . '/register', 'UserController', 'register');
\Core\Classes\Router::get($frontendPath . '/login', 'UserController', 'login');
\Core\Classes\Router::get($frontendPath . '/logout', 'UserController', 'logout');
\Core\Classes\Router::get($frontendPath . '/user/{username}', 'UserController', 'profile');
\Core\Classes\Router::get($frontendPath . '/settings/{name}', 'SettingsController', 'getSetting');

if ($backendPath && $frontendPath) {
    $backendPath = $frontendPath.$backendPath;
} else {
    $backendPath = '/backend'; // Standardpfad, falls nichts in der Datenbank gefunden wird
}

// Neue Route für das Backend mit dem dynamischen Pfad
\Core\Classes\Router::get($backendPath, 'AdminController', 'index');
\Core\Classes\Router::get($backendPath.'/', 'AdminController', 'index');
