<?php
/**
 * Name: PermissionManager
 * Beschreibung: Klasse zur Verwaltung von Berechtigungen.
 *
 * Author: Markus
 * Datei: core/manager/PermissionManager.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Klasse bietet Methoden zur Verwaltung von Berechtigungen.
 * Klasse: PermissionManager
 * Methoden: - getAllPermissions, - getBackendPermissions, - createPermission, - updatePermission, - deletePermission
 */

namespace Core\Manager;

use Core\Classes\Database;

class PermissionManager {
    private $database;

    /**
     * Konstruktor.
     * Initialisiert eine Instanz der Database-Klasse.
     */
    public function __construct() {
        $this->database = new Database();
    }

    /**
     * Holt alle Berechtigungen aus der Datenbank.
     *
     * @return array Ein Array mit allen Berechtigungen.
     */
    public function getAllPermissions() {
        $query = "SELECT * FROM permissions";
        return $this->database->fetchAll($query);
    }

    /**
     * Holt alle Backend-Berechtigungen aus der Datenbank.
     *
     * @return array Ein Array mit Backend-Berechtigungen.
     */
    public function getBackendPermissions() {
        $query = "SELECT * FROM permissions WHERE scope = 'backend'";
        return $this->database->fetchAll($query);
    }

    /**
     * Erstellt eine neue Berechtigung in der Datenbank.
     *
     * @param string $name Der Name der Berechtigung.
     * @param string $description Die Beschreibung der Berechtigung.
     * @param string $scope Der Geltungsbereich der Berechtigung (z.B., 'backend' oder 'frontend').
     * @return bool True, wenn die Berechtigung erfolgreich erstellt wurde, sonst false.
     */
    public function createPermission($name, $description, $scope) {
        $query = "INSERT INTO permissions (name, description, scope) VALUES (?, ?, ?)";
        $params = [$name, $description, $scope];
        return $this->database->query($query, $params);
    }

    /**
     * Aktualisiert eine Berechtigung in der Datenbank.
     *
     * @param int $permissionId Die ID der zu aktualisierenden Berechtigung.
     * @param string $name Der neue Name der Berechtigung.
     * @param string $description Die neue Beschreibung der Berechtigung.
     * @return bool True, wenn die Berechtigung erfolgreich aktualisiert wurde, sonst false.
     */
    public function updatePermission($permissionId, $name, $description) {
        $query = "UPDATE permissions SET name = ?, description = ? WHERE id = ?";
        $params = [$name, $description, $permissionId];
        return $this->database->query($query, $params);
    }

    /**
     * Löscht eine Berechtigung aus der Datenbank.
     *
     * @param int $permissionId Die ID der zu löschenden Berechtigung.
     * @return bool True, wenn die Berechtigung erfolgreich gelöscht wurde, sonst false.
     */
    public function deletePermission($permissionId) {
        $query = "DELETE FROM permissions WHERE id = ?";
        return $this->database->query($query, [$permissionId]);
    }

    // Weitere Methoden für die Verwaltung von Berechtigungen hier...
}
?>
