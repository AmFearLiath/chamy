<?php
/**
 * Name: User
 * Beschreibung: Klasse zur Verwaltung von Benutzern.
 *
 * Author: Markus
 * Datei: core/classes/User.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Klasse bietet Methoden zur Verwaltung von Benutzern.
 * Klasse: User
 * Methoden: - createUser, - getUserById, - getUserByUsername, - getUserByEmail
 */

namespace Core\Classes;

class User {
    /**
     * Erstellt einen neuen Benutzer in der Datenbank.
     *
     * @param string $username Der Benutzername des neuen Benutzers.
     * @param string $email Die E-Mail-Adresse des neuen Benutzers.
     * @param string $password Das Passwort des neuen Benutzers (verschlüsselt).
     * @return bool True, wenn der Benutzer erfolgreich erstellt wurde, sonst false.
     */
    public static function createUser($username, $email, $password) {
        $db = new Database();

        // Erstelle den Benutzer in der Datenbank
        $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $params = [$username, $email, $password];
        return $db->query($query, $params);
    }

    /**
     * Holt einen Benutzer aus der Datenbank anhand der Benutzer-ID.
     *
     * @param int $userId Die ID des Benutzers.
     * @return mixed|null Ein Objekt, das den Benutzer repräsentiert, oder null, wenn nicht gefunden.
     */
    public static function getUserById($userId) {
        $db = new Database();

        $query = "SELECT * FROM users WHERE id = ?";
        $user = $db->fetch($query, [$userId]);

        return $user;
    }

    /**
     * Holt einen Benutzer aus der Datenbank anhand des Benutzernamens.
     *
     * @param string $username Der Benutzername des Benutzers.
     * @return mixed|null Ein Objekt, das den Benutzer repräsentiert, oder null, wenn nicht gefunden.
     */
    public static function getUserByUsername($username) {
        $db = new Database();

        $query = "SELECT * FROM users WHERE username = ?";
        $user = $db->fetch($query, [$username]);

        return $user;
    }

    /**
     * Holt einen Benutzer aus der Datenbank anhand der E-Mail-Adresse.
     *
     * @param string $email Die E-Mail-Adresse des Benutzers.
     * @return mixed|null Ein Objekt, das den Benutzer repräsentiert, oder null, wenn nicht gefunden.
     */
    public static function getUserByEmail($email) {
        $db = new Database();

        $query = "SELECT * FROM users WHERE email = ?";
        $user = $db->fetch($query, [$email]);

        return $user;
    }
}
?>
