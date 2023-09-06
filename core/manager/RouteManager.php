<?php
namespace Core\Manager;
use Core\Classes\Database;

class RouteManager
{
    // Objekt der Klasse PDO
    protected $pdo;

    // Konstruktor der Klasse RouteManager
    public function __construct()
    {

        // Erstelle ein Objekt der Klasse PDO mit den Datenbankverbindungsdaten
        $this->pdo = new PDO('mysql:host=localhost;dbname=chamy', 'root', '');
    }

    // Methode zum Hinzufügen einer Route zur Datenbank
    public function addRoute($path, $controller, $method)
    {
        // Bereite eine SQL-Anweisung zum Einfügen einer Route vor
        $stmt = $this->pdo->prepare('INSERT INTO routes (path, controller, method) VALUES (:path, :controller, :method)');

        // Führe die SQL-Anweisung aus und binde die Parameter
        $stmt->execute([':path' => $path, ':controller' => $controller, ':method' => $method]);
    }

    // Methode zum Bearbeiten einer Route in der Datenbank
    public function editRoute($id, $path, $controller, $method)
    {
        // Bereite eine SQL-Anweisung zum Aktualisieren einer Route vor
        $stmt = $this->pdo->prepare('UPDATE routes SET path = :path, controller = :controller, method = :method WHERE id = :id');

        // Führe die SQL-Anweisung aus und binde die Parameter
        $stmt->execute([':id' => $id, ':path' => $path, ':controller' => $controller, ':method' => $method]);
    }

    // Methode zum Löschen einer Route aus der Datenbank
    public function deleteRoute($id)
    {
        // Bereite eine SQL-Anweisung zum Löschen einer Route vor
        $stmt = $this->pdo->prepare('DELETE FROM routes WHERE id = :id');

        // Führe die SQL-Anweisung aus und binde den Parameter
        $stmt->execute([':id' => $id]);
    }

    // Methode zum Abrufen aller Routen aus der Datenbank
    public function getRoutes()
    {
        // Bereite eine SQL-Anweisung zum Abrufen aller Routen vor
        $stmt = $this->pdo->prepare('SELECT * FROM routes');

        // Führe die SQL-Anweisung aus
        $stmt->execute();

        // Hole alle Routen als ein assoziatives Array
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
