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
$router->add($frontendPath . '\/{page}?', array (
    'controller' => 'HomeController',
    'action' => 'index'
), array ('GET', 'POST'));
$router->add($backendPath.'\/{page}?', array (
    'controller' => 'AdminController',
    'action' => 'index'
), array ('GET', 'POST'));
