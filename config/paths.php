<?php
/**
 * Name: Pfad Konfigurationsdatei für das Chamy-Framework
 * Beschreibung: Enthält die Pfaddefinitionen.
 *
 * Author: Markus
 * Datei: config/paths.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Datei enthält die Pfad Definitionen im Framework.
 *  
 */

// Definiere den Basispfad
define('BASE_PATH', dirname(__DIR__));

// Definiere den Pfad zur Konfigurationsdatei
define('CONFIG_PATH', BASE_PATH . '/config');

// Definiere den Pfad zur App
define('APP_PATH', BASE_PATH . '/app');

// Definiere den Pfad zur Core-Klassen
define('CORE_PATH', BASE_PATH . '/core/classes');

// Definiere den Pfad zu den Ansichten (Views)
define('VIEW_PATH', BASE_PATH . '/app/views');

// Definiere den Pfad zum Verzeichnis der Layout-Dateien
define('LAYOUT_PATH', APP_PATH . '/layouts');

// Definiere den Pfad zu den Modellen
define('MODEL_PATH', APP_PATH . '/models');

// Definiere den Pfad zu den Controllern
define('CONTROLLER_PATH', APP_PATH . '/controllers');

// Definiere den Pfad zu den Routen
define('ROUTE_PATH', APP_PATH . '/routes');

// Definiere den Pfad zu den Themes
define('THEME_PATH', BASE_PATH . '/themes');

// Definiere den Pfad zu den Helper
define('HELPER_PATH', BASE_PATH . '/helper');
