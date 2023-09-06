<?php
/**
 * Name: Route
 * Beschreibung: Modell für Routeninformationen.
 *
 * Author: Markus
 * Datei: app/models/Route.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Dieses Modell repräsentiert Routeninformationen aus der Datenbank.
 * Modell: Route
 * Methoden: - getAllRoutes
 */

namespace App\Models;

use Core\Classes\Model;
use Core\Classes\Database;

class Route {
    /**
     * Holt alle Routen aus der Datenbank.
     *
     * Diese Methode ruft alle gespeicherten Routen aus der Datenbank ab.
     *
     * @return array Ein Array von Routenobjekten.
     */
    public function getAllRoutes() {
        // Erstelle eine Instanz der Database-Klasse
        $db = new Database();

        // Führe die Abfrage aus, um alle Routen zu holen
        $query = "SELECT * FROM routes";
        $routes = $db->fetchAll($query);

        // Weitere Logik und Datenverarbeitung hier

        return $routes;
    }
}
