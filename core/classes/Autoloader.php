<?php
/**
 * Name: Autoloader
 * Beschreibung: Autoloader der Anwendung.
 *
 * Author: Markus
 * Datei: core/classes/Autoloader.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Stellt den Autoloader der Anwendung bereit.
 * Klasse: Autoloader
 * Methoden: - register - load
 */

namespace Core\Classes;

class Autoloader {
    public static function register() {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load($class) {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = __DIR__ . DIRECTORY_SEPARATOR . '..\..' . DIRECTORY_SEPARATOR . $class . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}

// Registriere den Autoloader
Autoloader::register();
