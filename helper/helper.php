<?php
/**
 * Name: Chamy
 * Beschreibung: Helperfunktionen für das Chamy-Framework.
 *
 * Author: Liath <amfearliath@googlemail.com>
 * License: All Rights Reserved
 * 
 * Datei: helper/helper.php
 * Dateityp: Helper
 * Erstellt: 15:30 - 25.08.2023
 * Zuletzt aktualisiert: 09:15 - 01.09.2023
 * 
 * Beschreibung: Diese Datei bietet nützliche Helfer für das Chamy Framework.
 * Klasse: Database
 * Methoden: - 
 * Funktionen: - url
*/

require_once __DIR__ . '/../config/paths.php'; // Pfade aus config/paths.php laden

/**
 * Generiere einen vollständigen URL-Pfad anhand des angegebenen Pfades.
 *
 * @param string $path Der relative Pfad.
 * @return string Der vollständige URL-Pfad.
 */
function url($path) {
    return BASE_PATH . $path;
}

function parse($data) {
    echo '<br>';
    echo '<pre><code>';
    print_r($data);
    echo '</code></pre><br>';
}

/**
 * Sortiert ein Array nach einer bestimmten Reihenfolge und gibt den gewünschten Teil des Arrays zurück.
 *
 * Diese Funktion kann verwendet werden, um ein Array basierend auf einem angegebenen Sortierschlüssel
 * und einer gewünschten Teilmengenposition zu sortieren und zurückzugeben.
 *
 * @param mixed $array Das zu sortierende Array (kann eine Variable oder ein Dateiname sein).
 * @param mixed $order Die Reihenfolge, nach der das Array sortiert werden soll (kann ein String oder ein Array sein).
 *                    Wenn 'order' ein Array ist, erfolgt die Sortierung in mehreren Schritten gemäß der Reihenfolge im Array.
 *                    Wenn 'order' ein String ist, erfolgt die Sortierung in einem Schritt nach diesem Schlüssel.
 * @param int|null $view (Optional) Die Position, ab der der Teil des Arrays angezeigt werden soll (null zeigt das gesamte sortierte Array an).
 * @return mixed Das sortierte Array oder null, wenn ein Fehler auftritt.
 */
function sortArray($array, $order, $view = null) {
    // Überprüfen, ob die Eingabe ein Dateiname ist, und die Datei einlesen
    if (is_string($array) && file_exists($array)) {
        $array = include($array);
    }

    // Überprüfen, ob das Array tatsächlich ein Array ist
    if (!is_array($array)) {
        return null;
    }

    // Überprüfen, ob 'order' ein Array ist
    if (is_array($order)) {
        // Mehrere Schritte, um das Array nach der Reihenfolge im 'order'-Array zu sortieren
        foreach ($order as $key) {
            usort($array, function ($a, $b) use ($key) {
                return $a[$key] <=> $b[$key];
            });
        }
    } elseif (is_string($order)) {
        // Ein Schritt, um das Array nach einem einzelnen Schlüssel zu sortieren
        usort($array, function ($a, $b) use ($order) {
            return $a[$order] <=> $b[$order];
        });
    } else {
        return null; // Ungültiger 'order'-Parameter
    }

    // Wenn 'view' angegeben ist und im Array existiert, nur den Inhalt ab diesem Parameter anzeigen
    if ($view !== null && isset($array[$view])) {
        $array = array_slice($array, $view);
    }

    // Rückgabe des sortierten Arrays
    return $array;
}

//include __DIR__ . '/array.php';


?>
