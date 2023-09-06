<?php
/**
 * Name: Hash
 * Beschreibung: Hilfsklasse für das Hashen von Passwörtern im Chamy Framework.
 *
 * Author: Markus
 * Datei: core/classes/Hash.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Klasse bietet Methoden zum Hashen und Überprüfen von Passwörtern.
 * Klasse: Hash
 * Methoden: - make, - verify
 */

namespace Core\Classes;

class Hash {
    /**
     * Hashes a password using the bcrypt algorithm.
     *
     * @param string $password The password to hash.
     * @return string The hashed password.
     */
    public static function make($password) {
        $options = [
            'cost' => 12, // You can adjust the cost parameter to increase or decrease security
        ];
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * Verifies a password against a hash.
     *
     * @param string $password The password to verify.
     * @param string $hash The hash to compare against.
     * @return bool True if the password is valid, false otherwise.
     */
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
}
