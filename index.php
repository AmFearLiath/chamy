<?php
/**
 * Name: Chamy
 * Beschreibung: Einstiegspunkt für das Chamy Framework.
 *
 * Author: Liath <amfearliath@googlemail.com>
 * License: All Rights Reserved
 * 
 * Datei: index.php
 * Dateityp: Einstiegspunkt des Frameworks
 * Erstellt: 15:30 - 25.08.2023
 * Zuletzt aktualisiert: 09:15 - 01.09.2023
 * 
 * Beschreibung: 
 * Klasse: 
 * Methoden: - 
 * Funktionen: -
*/

// Lade die Autoloader-Klasse
require_once __DIR__ . '/core/classes/Autoloader.php';
require_once __DIR__ . '/vendor/autoload.php';

// Registriere den Autoloader
\Core\Classes\Autoloader::register();

// Lade die Pfadkonfigurationsdatei
require_once __DIR__ . '/config/paths.php';

// Lade die Routen
require_once __DIR__ . '/app/routes.php';

// Starte die Router-Verarbeitung
global $router;
$router->dispatch($_SERVER['REQUEST_URI']);
