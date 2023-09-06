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
include HELPER_PATH . '/helper.php';

class Router {
    // Ein Array, das die Routen speichert
    private $routes = [];
    //private static $routes = [];

    // Eine Methode, die eine Route hinzufügt
    public function add($route, $params)
    {
        echo "<h1>\$router->add wurde aufgerufen:</h1><p>$route</p>";
        echo "<p>\Parameter:</p>";
        parse($params);

        // Ersetzen Sie alle dynamischen Segmente mit regulären Ausdrücken
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);

        // Fügen Sie einen Start- und Endbegrenzer hinzu und ignorieren Sie die Groß-/Kleinschreibung
        $route = '/^' . $route . '$/i';

        // Fügen Sie die Route und die Parameter zum Array hinzu
        $this->routes[$route] = $params;
        echo "<br>Route hinzugefügt: $route";
        echo "<br>Routen Objekt: ";
        parse($this->routes);
    }

    // Eine Methode, die die Anfrage an die entsprechende Route weiterleitet
    public function dispatch($url)
    {
        echo "<h1>dispatch wurde aufgerufen: $url";
        echo "<br><br><br>URL vor trim: $url";
        
        // Entfernen Sie alle führenden und nachfolgenden Schrägstriche aus der URL
        $url = trim($url, '/');

        echo "<br>URL nach trim: $url";

        echo "<p>Schleife Routen durch</p>";
        parse($this->routes);

        // Überprüfen Sie, ob die URL mit einer der Routen übereinstimmt
        foreach ($this->routes as $route => $params) { 
            if (preg_match($route, $url, $matches)) {
                echo "<br>Extrahierte Route: ";
                parse($this->routes);
                // Extrahieren Sie die benannten Parameter aus den Übereinstimmungen
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                // Erstellen Sie ein Controller-Objekt basierend auf dem Controller-Namen in den Parametern
                $controller = new $params['controller'];

                // Überprüfen Sie, ob der Controller eine Methode hat, die dem Aktionsnamen in den Parametern entspricht
                if (method_exists($controller, $params['action'])) {
                    // Rufen Sie die Methode auf und übergeben Sie ihr alle verbleibenden Parameter als Argumente
                    call_user_func_array([$controller, $params['action']], array_slice($params, 2));
                } else {
                    // Werfen Sie eine Ausnahme, wenn die Methode nicht existiert
                    throw new \Exception("No such action: {$params['action']}");
                }

                // Beenden Sie die Schleife, wenn eine Übereinstimmung gefunden wurde
                break;
            }
        }
    }

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
    public static function dispatch_old_but_working() {
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
