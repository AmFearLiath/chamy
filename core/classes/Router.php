<?php
/**
 * Name: Chamy
 * Beschreibung: Routerklasse für das Chamy-Framework.
 *
 * Author: Liath <amfearliath@googlemail.com>
 * License: All Rights Reserved
 * 
 * Datei: core/classes/Router.php
 * Dateityp: Klasse
 * Erstellt: 15:30 - 25.08.2023
 * Zuletzt aktualisiert: 09:15 - 01.09.2023
 * 
 * Beschreibung: Diese Klasse bietet Methoden für das Routing.
 * Klasse: Database
 * Methoden: - get, - dispatch, - addRoute, - matchPath
 * Funktionen: -
*/

namespace Core\Classes;

class Router {
    private static $routes = [];

    /**
     * Fügt eine Route zur Liste hinzu.
     *
     * @param string $path Der Pfad der Route.
     * @param string $controller Der Name des Controllers.
     * @param string $method Die Methode des Controllers.
     */
    public static function get($path, $controller, $method) {
        self::$routes[] = [
            'path' => $path,
            'controller' => $controller,
            'method' => $method
        ];
    }

    /**
     * Verarbeitet die eingehende Anfrage und führt die entsprechende Aktion aus.
     */
    public static function dispatch() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        
        foreach (self::$routes as $route) {
            if ($route['path'] === $uri && $method === 'GET') {
                $controllerName = '\\App\\Controllers\\' . $route['controller'];
                $controller = new $controllerName();
                $methodName = $route['method'];
                $controller->$methodName();
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    /**
     * Fügt eine Route zur Liste hinzu.
     *
     * @param string $path Der Pfad der Route.
     * @param string $controller Der Name des Controllers.
     * @param string $method Die Methode des Controllers.
     */
    public static function addRoute($path, $controller, $method) {
        self::get($path, $controller, $method);
    }

    /**
     * Überprüft, ob der angegebene Pfad mit einem der definierten Routen übereinstimmt.
     *
     * @param string $path Der zu überprüfende Pfad.
     * @return bool True, wenn ein Übereinstimmung gefunden wurde, sonst false.
     */
    public static function matchPath($path) {
        foreach (self::$routes as $route) {
            if ($route['path'] === $path) {
                return true;
            }
        }
        return false;
    }
}
