<?php

namespace Core\Classes;
use Core\Manager\RouteManager;

class Router
{
    // Objekt der Klasse RouteManager
    protected $routeManager;

    // Konstruktor der Klasse Router
    public function __construct()
    {
        // Erstelle ein Objekt der Klasse RouteManager
        $this->routeManager = new RouteManager();
    }

    // Methode zum Überprüfen, ob die URL mit einer Route übereinstimmt und zum Aufrufen der entsprechenden Funktion
    public function dispatch()
    {
        // Hole die URL aus dem Server-Array
        $url = $_SERVER['REQUEST_URI'];

        // Entferne alle führenden und nachfolgenden Schrägstriche aus der URL
        $url = trim($url, '/');

        // Hole alle Routen aus dem Objekt routeManager
        $routes = $this->routeManager->getRoutes();

        // Überprüfe jede Route auf Übereinstimmung mit der URL
        foreach ($routes as $route) {
            // Hole den Pfad, den Controller und die Methode aus der Route
            $path = $route['path'];
            $controller = $route['controller'];
            $method = $route['method'];

            // Erstelle einen regulären Ausdruck aus dem Pfad
            $regex = '/^' . str_replace('/', '\/', $path) . '$/';

            // Überprüfe, ob der reguläre Ausdruck mit der URL übereinstimmt
            if (preg_match($regex, $url, $matches)) {
                // Entferne den ersten Eintrag aus dem Array der Übereinstimmungen
                array_shift($matches);

                // Lade die Datei, die den Controller enthält
                require_once 'controllers/' . $controller . '.php';

                // Erstelle ein Objekt des Controllers
                $controller = new $controller();

                // Rufe die Methode auf dem Objekt auf und übergebe die Übereinstimmungen als Parameter
                call_user_func_array([$controller, $method], $matches);

                // Beende die Schleife
                break;
            }
        }
    }
}
