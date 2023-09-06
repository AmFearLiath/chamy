<?php
/**
 * Name: Session
 * Beschreibung: Klasse zur Verwaltung von Benutzersitzungen.
 *
 * Author: Markus
 * Datei: core/classes/Session.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Klasse bietet Methoden zur Verwaltung von Benutzersitzungen.
 * Klasse: Session
 * Methoden: - login, - logout, - isLoggedIn, - getCurrentUserId, - getSessionByUserId
 */

namespace Core\Classes;

use Core\Classes\Database;
use Core\Classes;

class Session {
    /**
     * Startet eine Benutzersitzung.
     *
     * Diese Methode startet eine Benutzersitzung, indem die Benutzer-ID in der Sitzung gespeichert wird.
     *
     * @param int $userId Die ID des angemeldeten Benutzers.
     * @return void
     */
    public static function login($userId) {
        $_SESSION['user_id'] = $userId;
    }

    /**
     * Beendet die aktuelle Benutzersitzung.
     *
     * Diese Methode beendet die aktuelle Benutzersitzung, indem alle Sitzungsdaten gelöscht werden.
     *
     * @return void
     */
    public static function logout() {
        session_unset();
        session_destroy();
    }

    /**
     * Prüft, ob ein Benutzer angemeldet ist.
     *
     * Diese Methode prüft, ob ein Benutzer angemeldet ist, indem überprüft wird, ob die Benutzer-ID in der Sitzung vorhanden ist.
     *
     * @return bool True, wenn ein Benutzer angemeldet ist, sonst false.
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Gibt den aktuellen Benutzer zurück, sofern angemeldet.
     *
     * Diese Methode gibt das Benutzerobjekt des aktuellen Benutzers zurück, sofern dieser angemeldet ist.
     *
     * @return mixed|null Das Benutzerobjekt oder null, wenn kein Benutzer angemeldet ist.
     */
    public static function getUser() {
        $userId = self::getCurrentUserId();
        if ($userId !== null) {
            // Hier sollte die Logik zur Abfrage des Benutzers aus der Datenbank erfolgen
            // Beispiel: $db = new Database(); $user = $db->getUserById($userId);
            // return $user;
            return null; // Rückgabe sollte angepasst werden
        }
        return null;
    }

    /**
     * Gibt die ID des aktuell angemeldeten Benutzers zurück.
     *
     * Diese Methode gibt die ID des aktuell angemeldeten Benutzers zurück.
     *
     * @return int|null Die Benutzer-ID oder null, wenn kein Benutzer angemeldet ist.
     */
    public static function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    /**
     * Holt eine Benutzersitzung aus der Datenbank anhand der Benutzer-ID.
     *
     * Diese Methode ruft eine Benutzersitzung aus der Datenbank anhand der Benutzer-ID ab.
     *
     * @param int $userId Die ID des Benutzers.
     * @return mixed|null Ein Objekt, das die Benutzersitzung repräsentiert, oder null, wenn nicht gefunden.
     */
    public static function getSessionByUserId($userId) {
        $db = new Database();

        $query = "SELECT * FROM sessions WHERE user_id = ?";
        $session = $db->fetch($query, [$userId]);

        return $session;
    }
}
?>
