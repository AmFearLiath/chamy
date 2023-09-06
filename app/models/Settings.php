<?php
/**
 * Name: Settings
 * Beschreibung: Modell für Anwendungseinstellungen.
 *
 * Author: Markus
 * Datei: app/models/Settings.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Dieses Modell verwaltet Anwendungseinstellungen aus der Datenbank.
 * Modell: Settings
 * Methoden: - getSettingByName, - getAllSettings
 */

namespace App\Models;

use Core\Classes\Database;

class Settings {
    /**
     * Holt eine bestimmte Einstellung aus der Datenbank anhand des Namens.
     *
     * Diese Methode ruft eine bestimmte Anwendungseinstellung aus der Datenbank ab.
     *
     * @param string $name Der Name der Einstellung.
     * @return mixed|null Das Einstellungsobjekt oder null, wenn nicht gefunden.
     */
    public function getSettingByName($name) {
        // Erstelle eine Instanz der Database-Klasse
        $db = new Database();

        // Führe die Abfrage aus, um die Einstellung zu holen
        $query = "SELECT * FROM settings WHERE name = ?";
        $setting = $db->fetch($query, [$name]);

        // Weitere Logik und Datenverarbeitung hier

        return $setting;
    }

    /**
     * Holt alle Anwendungseinstellungen aus der Datenbank.
     *
     * Diese Methode ruft alle gespeicherten Anwendungseinstellungen aus der Datenbank ab.
     *
     * @return array Ein Array von Einstellungen.
     */
    public function getAllSettings() {
        // Erstelle eine Instanz der Database-Klasse
        $db = new Database();

        // Führe die Abfrage aus, um alle Einstellungen zu holen
        $query = "SELECT * FROM settings";
        $settings = $db->fetchAll($query);

        // Weitere Logik und Datenverarbeitung hier

        return $settings;
    }
}
