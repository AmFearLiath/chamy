<?php
/**
 * Name: Chamy
 * Beschreibung: Datenbankklasse für das Chamy-Framework.
 *
 * Author: Liath <amfearliath@googlemail.com>
 * License: All Rights Reserved
 * 
 * Datei: core/classes/Database.php
 * Dateityp: Klasse
 * Erstellt: 15:30 - 25.08.2023
 * Zuletzt aktualisiert: 09:15 - 01.09.2023
 * 
 * Beschreibung: Diese Klasse bietet Methoden für grundlegende Datenbankoperationen.
 * Klasse: Database
 * Methoden: - connect, - query, - fetch, - fetchAll, - insert, - update, - delete, - getSetting, - getUserRole
 * Funktionen: -
*/

namespace Core\Classes;

class Database {
    private $pdo;
    
    public function __construct() {
        // Lade die Konfigurationsdatei
        include_once(CONFIG_PATH . '/database.php');

        try {
            $dsn = 'mysql:host=' . \DB_HOST . ';dbname=' . \DB_NAME . ';charset=' . \DB_CHARSET;
            $this->pdo = new \PDO($dsn, \DB_USER, \DB_PASS);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Verbindung zur Datenbank fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * Gibt die zuletzt eingefügte ID in der Datenbank zurück.
     *
     * @return int|null Die zuletzt eingefügte ID oder null, wenn keine ID eingefügt wurde.
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Execute a SQL query with optional parameters.
     *
     * @param string $query The SQL query to execute.
     * @param array $params Optional array of parameters to bind to the query.
     * @return PDOStatement The PDOStatement object representing the result of the query.
     */
    public function query($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Holt eine einzelne Zeile aus der Datenbank.
     *
     * @param string $query Die SQL-Abfrage.
     * @return mixed|null Die gefundene Zeile als Objekt oder null, wenn nichts gefunden wurde.
     */
    public function fetch($query) {
        return $this->query($query)->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Holt alle Zeilen aus der Datenbank.
     *
     * @param string $query Die SQL-Abfrage.
     * @return mixed|null Das Ergebnis als Array von Objekten oder null, wenn nichts gefunden wurde.
     */
    public function fetchAll($query) {
        return $this->query($query)->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Fügt einen Datensatz in die Datenbank ein.
     *
     * @param string $table Die Tabelle, in die eingefügt wird.
     * @param array $data Die Daten, die eingefügt werden sollen.
     * @return bool True, wenn das Einfügen erfolgreich war, sonst false.
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));

        $query = "INSERT INTO $table ($columns) VALUES ($values)";
        $stmt = $this->pdo->prepare($query);

        return $stmt->execute(array_values($data));
    }

    /**
     * Aktualisiert Datensätze in der Datenbank.
     *
     * @param string $table Die Tabelle, in der aktualisiert wird.
     * @param array $data Die neuen Daten.
     * @param array $condition Die Bedingung für das Aktualisieren.
     * @return bool True, wenn das Aktualisieren erfolgreich war, sonst false.
     */
    public function update($table, $data, $condition) {
        $columns = array_map(function ($column) {
            return "$column=?";
        }, array_keys($data));

        $query = "UPDATE $table SET " . implode(', ', $columns) . " WHERE $condition";
        $stmt = $this->pdo->prepare($query);

        return $stmt->execute(array_merge(array_values($data), array_values($condition)));
    }

    /**
     * Löscht Datensätze aus der Datenbank.
     *
     * @param string $table Die Tabelle, aus der gelöscht wird.
     * @param array $condition Die Bedingung für das Löschen.
     * @return bool True, wenn das Löschen erfolgreich war, sonst false.
     */
    public function delete($table, $condition) {
        $query = "DELETE FROM $table WHERE $condition";
        $stmt = $this->pdo->prepare($query);

        return $stmt->execute(array_values($condition));
    }

    /**
     * Get a configuration setting value or an array of settings from the 'settings' table.
     *
     * @param string|null $key The name of the configuration setting to retrieve.
     * @return mixed|null|array The value of the configuration setting, an array of settings, or null if not found.
     */
    public function getSetting($key = null) {
        $query = "SELECT * FROM settings";
        $params = [];

        if ($key !== null) {
            $query .= " WHERE name = ?";
            $params[] = $key;
        }

        $result = $this->query($query, $params);

        if ($key !== null) {
            $return = $result->fetch(\PDO::FETCH_ASSOC);
            return $return['data'];
        }

        $settings = [];
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $settings[$row['name']] = json_decode($row['data'], true); // Korrigierter Teil hier
        }

        return $settings;
    }

    /**
     * Speichert eine Einstellung in der Datenbank.
     *
     * @param string $name Der Name der Einstellung.
     * @param mixed $data Die Daten der Einstellung.
     * @return bool Gibt true zurück, wenn das Speichern erfolgreich war, ansonsten false.
     */
    public function saveSetting($name, $data) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO settings (name, data) VALUES (:name, :data) ON DUPLICATE KEY UPDATE data = :data");
            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->bindParam(':data', $data, \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Handle the exception if needed
            return false;
        }
    }

    /**
     * Löscht eine Einstellung aus der Datenbank.
     *
     * @param string $name Der Name der zu löschenden Einstellung.
     * @return bool Gibt true zurück, wenn das Löschen erfolgreich war, ansonsten false.
     */
    public function deleteSetting($name) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM settings WHERE name = ?");
            return $stmt->execute([$name]);
        } catch (\PDOException $e) {
            // Handle the exception if needed
            return false;
        }
    }

    /**
     * Setzt das aktive Frontend-Theme.
     *
     * @param string $theme Das ausgewählte Frontend-Theme.
     * @return bool Gibt true zurück, wenn das Theme erfolgreich gesetzt wurde, ansonsten false.
     */
    public function setFrontendTheme($theme) {
        return $this->saveSetting('frontend_theme', $theme);
    }

    /**
     * Setzt das aktive Backend-Theme.
     *
     * @param string $theme Das ausgewählte Backend-Theme.
     * @return bool Gibt true zurück, wenn das Theme erfolgreich gesetzt wurde, ansonsten false.
     */
    public function setBackendTheme($theme) {
        return $this->saveSetting('backend_theme', $theme);
    }

    /**
     * Get the role of a user from the database.
     *
     * This method retrieves the role of a user from the database based on their user ID.
     * If no role is found, the default role "user" is returned.
     *
     * @param int $userId The ID of the user whose role is to be retrieved.
     * @return string The role of the user, or "user" if no role is found.
     */
    public function getUserRole($userId) {
        // SQL query to retrieve the role from the 'users' table based on user ID
        $query = "SELECT role FROM users WHERE id = ?";
        $params = [$userId];
        
        // Fetch the role using the query and parameters
        $result = $this->query($query, $params)->fetchColumn();
        
        // Return the fetched role, or 'user' as default if no role is found
        return $result ? $result : 'user';
    }
}

// Änderungen:
// - Namespace aktualisiert zu Core\Classes
// - Zuletzt aktualisiert am 01.09.2023
