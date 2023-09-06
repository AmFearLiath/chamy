<?php
/**
 * Name: UserManager
 * Beschreibung: Klasse zur Verwaltung von Benutzern, Rollen und Berechtigungen.
 *
 * Author: Markus
 * Datei: core/manager/UserManager.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Klasse bietet Methoden zur Verwaltung von Benutzern, Benutzerrollen und Berechtigungen.
 * Klasse: UserManager
 * Methoden: - getUserRoles, - getRolePermissions
 */

namespace Core\Manager;

use Core\Classes\Database;
use Core\Classes\Hash;

class UserManager {
    private $database;

    /**
     * Konstruktor.
     * Initialisiert eine Instanz der Database-Klasse.
     */
    public function __construct() {
        $this->database = new Database();
    }

    /**
     * Holt die Rollen eines Benutzers aus der Datenbank.
     *
     * @param int $userId Die ID des Benutzers.
     * @return array Ein Array mit den Rollen des Benutzers.
     */
    public function getUserRoles($userId) {
        $query = "SELECT roles.* FROM user_roles
                  JOIN roles ON user_roles.role_id = roles.id
                  WHERE user_roles.user_id = ?";
        return $this->database->fetchAll($query, [$userId]);
    }

    /**
     * Holt die Berechtigungen einer Rolle aus der Datenbank.
     *
     * @param int $roleId Die ID der Rolle.
     * @return array Ein Array mit den Berechtigungen der Rolle.
     */
    public function getRolePermissions($roleId) {
        $query = "SELECT permissions.* FROM role_permissions
                  JOIN permissions ON role_permissions.permission_id = permissions.id
                  WHERE role_permissions.role_id = ?";
        return $this->database->fetchAll($query, [$roleId]);
    }

    /**
     * Überprüft, ob ein Benutzername bereits vergeben ist.
     *
     * @param string $username Der zu überprüfende Benutzername.
     * @return bool True, wenn der Benutzername vergeben ist, sonst false.
     */
    public function isUsernameTaken($username) {
        $db = new Database();
        $query = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $result = $db->fetch($query, [$username]);
        return $result->count > 0;
    }

    /**
     * Überprüft, ob eine E-Mail-Adresse bereits vergeben ist.
     *
     * @param string $email Die zu überprüfende E-Mail-Adresse.
     * @return bool True, wenn die E-Mail-Adresse vergeben ist, sonst false.
     */
    public function isEmailTaken($email) {
        $db = new Database();
        $query = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $result = $db->fetch($query, [$email]);
        return $result->count > 0;
    }

    /**
     * Registriert einen neuen Benutzer in der Datenbank.
     *
     * @param string $username Der Benutzername.
     * @param string $email Die E-Mail-Adresse.
     * @param string $password Das Passwort.
     * @return int|null Die ID des registrierten Benutzers oder null bei einem Fehler.
     */
    public function registerUser($username, $email, $password) {
        $db = new Database();
        $hashedPassword = Hash::make($password);
        $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $success = $db->query($query, [$username, $email, $hashedPassword]);
        return $success ? $db->lastInsertId() : null;
    }
}
?>
