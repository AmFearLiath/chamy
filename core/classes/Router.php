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
    public function add_______($route, $params)
    {
        // Ersetzen Sie alle dynamischen Segmente mit regulären Ausdrücken
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        $route = preg_replace('/\?/', '\?', $route);

        $route = '/^' . $route . '/i';
        $this->routes[$route] = $params;
    }

    // Eine Methode, die eine Route hinzufügt
    public function add($route, $params = [])
    {
        // Konvertiere die Route zu einem regulären Ausdruck: entkomme Schrägstrichen
        $route = preg_replace('/\//', '\\/', $route);

        // Konvertiere Variablen z.B. {controller}
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);

        // Konvertiere Variablen mit benutzerdefinierten regulären Ausdrücken z.B. {id:\d+}
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

        // Füge Start- und Endzeichen hinzu und markiere den Parameter als optional
        $route = '/^' . $route . '((\\?P[a-z-]+)\?)?$/i';

        // Füge die Route zum Array der Routen hinzu
        $this->routes[$route] = $params;
    }

    // Eine Methode, die die Anfrage an die entsprechende Route weiterleitet
    public function dispatch($url) {

        echo "<p>URL: $url</p>";

        // Zerlege die URL in ihre Bestandteile
        $url_parts = parse_url($url);
        echo '<br>URL Parts';
        parse($url_parts);

        // Extrahiere den Pfad aus der URL
        $url_path = $url_parts['path'];
        echo '<br>URL Path';
        parse($url_path);

        // Extrahiere die Parameter aus der URL
        $url_params = array();
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $url_params);
        }

        foreach ($this->routes as $route => $params) {
            $action = $params['action'];
            echo '<br><br><br>Durchlaufe Routen';
            echo "<br>Route: $route";
            echo "<br>Url-Path: $url_path";
            if (preg_match($route, $url_path, $matches)) {
                echo "<h1>Routen:</h1>";
                parse($this->routes);
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                // Füge die zusätzlichen Parameter aus der URL hinzu
                $params = array_merge($params, $url_params);

                $controller = 'App\\Controllers\\' . $params['controller'];
                echo "<p>Controller: $controller</p>";
                echo "<p>Method: $action</p>";
                $controller = new $controller;

                if (method_exists($controller, $action)) {
                call_user_func_array([$controller, $action], array_slice($params, 2));
                } else {
                    throw new \Exception("No such action: {$action}");
                }
                break;
            }
        }
    }
    
    public function dispatch_($url)
    {        
        // Entfernen Sie alle führenden und nachfolgenden Schrägstriche aus der URL
        //$url = trim($url, '/');

        // Überprüfen Sie, ob die URL mit einer der Routen übereinstimmt
        foreach ($this->routes as $route => $params) {

            $action = $params['action'];

            if (preg_match($route, $url, $matches)) {
                echo "<h1>Routen:</h1>";
                parse($this->routes);

                // Extrahieren Sie die benannten Parameter aus den Übereinstimmungen
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                // Erstellen Sie ein Controller-Objekt basierend auf dem Controller-Namen in den Parametern
                $controller = 'App\\Controllers\\' . $params['controller'];
                echo "<p>Controller: $controller</p>";
                echo "<p>Method: $action</p>";
                $controller = new $controller;

                // Überprüfen Sie, ob der Controller eine Methode hat, die dem Aktionsnamen in den Parametern entspricht
                if (method_exists($controller, $action)) {
                    //echo "<br>Controller: $controller - Methode: $action";
                    // Rufen Sie die Methode auf und übergeben Sie ihr alle verbleibenden Parameter als Argumente
                    call_user_func_array([$controller, $action], array_slice($params, 2));
                } else {
                    // Werfen Sie eine Ausnahme, wenn die Methode nicht existiert
                    throw new \Exception("No such action: {$action}");
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
