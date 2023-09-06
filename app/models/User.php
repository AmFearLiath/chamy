<?php
/**
 * Name: User
 * Beschreibung: Modell für Benutzerinformationen.
 *
 * Author: Markus
 * Datei: app/models/User.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Dieses Modell repräsentiert Benutzerinformationen aus der Datenbank.
 * Modell: User
 * Methoden: - getUserByUsername, - getUserById
 */

namespace App\Models;

use Core\Classes\Database;

class User {
    /**
     * Holt einen Benutzer aus der Datenbank anhand des Benutzernamens.
     *
     * Diese Methode ruft einen Benutzer aus der Datenbank anhand des Benutzernamens ab.
     *
     * @param string $username Der Benutzername.
     * @return mixed|null Ein Benutzerobjekt oder null, wenn nicht gefunden.
     */
    public function getUserByUsername($username) {
        // Erstelle eine Instanz der Database-Klasse
        $db = new Database();

        // Führe die Abfrage aus, um den Benutzer zu holen
        $query = "SELECT * FROM users WHERE username = ?";
        $user = $db->fetch($query, [$username]);

        // Weitere Logik und Datenverarbeitung hier

        return $user;
    }

    /**
     * Holt einen Benutzer aus der Datenbank anhand der Benutzer-ID.
     *
     * Diese Methode ruft einen Benutzer aus der Datenbank anhand der Benutzer-ID ab.
     *
     * @param int $userId Die ID des Benutzers.
     * @return mixed|null Ein Benutzerobjekt oder null, wenn nicht gefunden.
     */
    public function getUserById($userId) {
        // Erstelle eine Instanz der Database-Klasse
        $db = new Database();

        // Führe die Abfrage aus, um den Benutzer zu holen
        $query = "SELECT * FROM users WHERE id = ?";
        $user = $db->fetch($query, [$userId]);

        // Weitere Logik und Datenverarbeitung hier

        return $user;
    }
}
