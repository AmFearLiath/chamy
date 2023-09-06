<?php

namespace Core\Classes;

class Router
{
    // Array der Routen
    protected $routes = [];

    // Array der Parameter
    protected $params = [];<?php

namespace Core\Classes;

class Router
{
    // Array der Routen
    protected $routes = [];

    // Array der Parameter
    protected $params = [];

    // Füge eine Route zum Array der Routen hinzu
    public function add($route, $params = [])
    {
        // Konvertiere die Route zu einem regulären Ausdruck: entkomme Schrägstrichen
        $route = preg_replace('/\//', '\\/', $route);

        // Konvertiere Variablen z.B. {controller}
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);

        // Konvertiere Variablen mit benutzerdefinierten regulären Ausdrücken z.B. {id:\d+}
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

        // Füge Start- und Endzeichen hinzu und markiere den Parameter als optional
        $route = '/^' . $route . '((\\?P[a-z-]+)\\?)?$/i';

        // Füge die Route zum Array der Routen hinzu
        $this->routes[$route] = $params;
    }

    // Überprüfe, ob die URL mit einer Route übereinstimmt und rufe die entsprechende Funktion auf
    public function dispatch($url_path)
    {
        // Entferne alle führenden und nachfolgenden Schrägstriche aus der URL
        $url_path = trim($url_path, '/');

        // Überprüfe, ob die Route übereinstimmt
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url_path, $matches)) {
                // Hole alle benannten Parameter aus dem Array
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                // Setze die Parameter für den Router
                $this->params = $params;

                // Rufe die Funktion auf, die der Route zugeordnet ist
                call_user_func($this->routes[$route]);

                return true;
            }
        }

        return false;
    }


    // Gib das Array der Parameter zurück
    public function getParams()
    {
        // Hole die Parameter aus dem Router
        $params = $this->params;

        // Überprüfe, ob ein Parameter in der URL vorhanden ist
        if (isset($params['?P'])) {
            // Entferne das Fragezeichen und den Buchstaben P aus dem Parameter
            $params['?P'] = str_replace('?P', '', $params['?P']);

            // Füge den Parameter zum Array der Parameter hinzu
            $params[] = $params['?P'];

            // Entferne den Parameter mit dem Schlüssel '?P' aus dem Array
            unset($params['?P']);
        }

        // Gib das Array der Parameter zurück
        return $params;
    }
}

    // Füge eine Route zum Array der Routen hinzu
    public function add($route, $params = [])
    {
        // Konvertiere die Route zu einem regulären Ausdruck: entkomme Schrägstrichen
        $route = preg_replace('/\//', '\\/', $route);

        // Konvertiere Variablen z.B. {controller}
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);

        // Konvertiere Variablen mit benutzerdefinierten regulären Ausdrücken z.B. {id:\d+}
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

        // Füge Start- und Endzeichen hinzu und markiere den Parameter als optional
        $route = '/^' . $route . '((\\?P[a-z-]+)\\?)?$/i';

        // Füge die Route zum Array der Routen hinzu
        $this->routes[$route] = $params;
    }

    // Überprüfe, ob die URL mit einer Route übereinstimmt und rufe die entsprechende Funktion auf
    public function dispatch($url_path)
    {
        // Entferne alle führenden und nachfolgenden Schrägstriche aus der URL
        $url_path = trim($url_path, '/');

        // Überprüfe, ob die Route übereinstimmt
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url_path, $matches)) {
                // Hole alle benannten Parameter aus dem Array
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }

                // Setze die Parameter für den Router
                $this->params = $params;

                // Rufe die Funktion auf, die der Route zugeordnet ist
                call_user_func($this->routes[$route]);

                return true;
            }
        }

        return false;
    }

    // Gib das Array der Parameter zurück
    public function getParams()
    {
        // Hole die Parameter aus dem Router
        $params = $this->params;

        // Überprüfe, ob ein Parameter in der URL vorhanden ist
        if (isset($params['?P'])) {
            // Entferne das Fragezeichen und den Buchstaben P aus dem Parameter
            $params['?P'] = str_replace('?P', '', $params['?P']);

            // Füge den Parameter zum Array der Parameter hinzu
            $params[] = $params['?P'];

            // Entferne den Parameter mit dem Schlüssel '?P' aus dem Array
            unset($params['?P']);
        }

        // Gib das Array der Parameter zurück
        return $params;
    }
}
