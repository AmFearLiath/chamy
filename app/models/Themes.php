<?php
/**
 * Name: Themes
 * Beschreibung: Modell für Anwendungsthemen.
 *
 * Author: Markus
 * Datei: app/models/Themes.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Dieses Modell verwaltet Anwendungsthemen aus der Datenbank.
 * Modell: Themes
 * Methoden: - getAllThemes
 */

namespace App\Models;

use Core\Classes\Database;
use Core\Manager\ThemeManager;

class Themes {
    private $database;

    /**
     * Konstruktor.
     * Initialisiert eine Instanz der Database-Klasse.
     */
    public function __construct() {
        $this->database = new Database();
    }
    private $inlineCSS = array();

    /**
     * Holt alle Anwendungsthemen aus der Datenbank.
     *
     * Diese Methode ruft alle gespeicherten Anwendungsthemen aus der Datenbank ab.
     *
     * @return array Ein Array von Thema-Objekten.
     */
    public function getAllThemes() {
        // Erstelle eine Instanz der Database-Klasse
        $db = new Database();

        // Führe die Abfrage aus, um alle Themen zu holen
        $query = "SELECT * FROM themes";
        $themes = $db->fetchAll($query);

        // Weitere Logik und Datenverarbeitung hier

        return $themes;
    }

    /**
     * Lädt eine Teilansicht in die Theme-Index-Datei.
     *
     * Diese Methode ermöglicht das Laden von Teilansichten in die Theme-Index-Datei.
     *
     * @param string $partialName Der Name der Teilansicht (ohne Dateiendung).
     * @param string $theme Das aktive Theme-Verzeichnis.
     * @param string $context Der Kontext des Aufrufs (z.B. "backend" oder "frontend").
     */
    public function loadPartial($partialName, $theme) {
        $basePath = $_SERVER['REQUEST_URI'];
        $context = strpos($basePath, '/backend') !== false ? 'backend' : 'frontend';
        $partialPath = "themes/$context/$theme/_partials/$partialName.php";

        // Überprüfe, ob die Teilansicht existiert
        if (file_exists($partialPath)) {
            include($partialPath);
        } else {
            echo "Partial not found: $partialPath";
        }
    }

    /**
     * Lädt eine Bilddatei aus dem aktuellen Theme.
     *
     * @param string $imageName Der Name der Bilddatei (ohne Erweiterung).
     * @param string $theme Das aktive Theme-Verzeichnis.
     * @return string|null Der URL-Pfad zur Bilddatei oder null, wenn die Datei nicht gefunden wird.
     */
    public function loadIMG($imageName, $theme) {
        $basePath = $_SERVER['REQUEST_URI'];
        $context = strpos($basePath, '/backend') !== false ? 'backend' : 'frontend';

        // Erstelle den Pfad zur Bilddatei basierend auf dem Kontext und dem angegebenen Theme
        $imageUrl = "themes/$context/$theme/$imageName";
        
        // Überprüfe, ob die Bilddatei existiert
        if (file_exists($imageUrl)) {
            return $imageUrl;
        } else {
            return null; // Rückgabe von null, wenn die Datei nicht gefunden wird
        }
    }

    /**
     * Lädt CSS-Dateien aus dem Theme-Verzeichnis.
     *
     * Diese Methode lädt eine oder mehrere CSS-Dateien aus dem angegebenen Theme-Verzeichnis.
     *
     * @param string|array $cssFiles Der Dateiname oder ein Array von Dateinamen der CSS-Dateien.
     * @param string $theme Das aktive Theme-Verzeichnis.
     * @return void
     */
    public function loadCSS($cssFiles, $theme) {
        $basePath = $_SERVER['REQUEST_URI'];
        $context = strpos($basePath, '/backend') !== false ? 'backend' : 'frontend';

        // Prüfe, ob $cssFiles ein Array ist
        if (is_array($cssFiles)) {
            $cssTags = '';
            foreach ($cssFiles as $cssFile) {
                $cssPath = "themes/$context/$theme/$cssFile";
                $cssTags .= '<link rel="stylesheet" type="text/css" href="' . $cssPath . '">';
            }
            echo $cssTags;
        } else if (is_string($cssFiles)) {
            $cssPath = "themes/$context/$theme/$cssFiles";
            echo '<link rel="stylesheet" type="text/css" href="' . $cssPath . '">';
        } else {
            return ''; // Rückgabe eines leeren Strings, wenn $cssFiles nicht gültig ist
        }
    }

    /**
     * Lädt JS-Dateien aus dem Theme-Verzeichnis.
     *
     * Diese Methode lädt eine oder mehrere JS-Dateien aus dem angegebenen Theme-Verzeichnis.
     *
     * @param string|array $jsFiles Der Dateiname oder ein Array von Dateinamen der JS-Dateien.
     * @param string $theme Das aktive Theme-Verzeichnis.
     * @return void
     */
    public function loadJS($jsFiles, $theme) {
        $basePath = $_SERVER['REQUEST_URI'];
        $context = strpos($basePath, '/backend') !== false ? 'backend' : 'frontend';
        $theme = "$context/$theme/";

        // Prüfe, ob $jsFiles ein Array ist
        if (is_array($jsFiles)) {
            $jsTags = '';
            foreach ($jsFiles as $jsFile) {
                $jsPath = 'themes/' . $theme . '/' . $jsFile;
                $jsTags .= '<script src="' . $jsPath . '"></script>';
            }
            echo $jsTags;
        } else if (is_string($jsFiles)) {
            $jsPath = 'themes/' . $theme . '/' . $jsFiles;
            echo '<script src="' . $jsPath . '"></script>';
        } else {
            return ''; // Rückgabe eines leeren Strings, wenn $jsFiles nicht gültig ist
        }
    }


    /**
     * Lädt eine Schriftart aus dem Theme-Verzeichnis und bindet sie per CSS ein.
     *
     * @param string $fontName Der Name der Schriftart (ohne Dateiendung).
     * @param string $fontExtension Die Dateiendung der Schriftart (z.B. "woff2").
     * @param string $fontPath Das Verzeichnis im Theme, in dem die Schriftart liegt.
     */
    public function loadFont($fontName, $fontExtension, $theme) {
        $basePath = $_SERVER['REQUEST_URI'];
        $context = strpos($basePath, '/backend') !== false ? 'backend' : 'frontend';
        $fontUrl = "$context/$theme/";

        $css = "@font-face {
            font-family: '$fontName';
            src: url('$fontUrl') format('$fontExtension');
        }";
        
        $this->addInlineCSS($css);
    }

    /**
     * Fügt CSS-Code inline zur aktuellen Seite hinzu.
     *
     * Diese Methode fügt den angegebenen CSS-Code direkt in das HTML der aktuellen Seite ein.
     *
     * @param string $cssCode Der CSS-Code, der eingefügt werden soll.
     * @return void
     */
    public function addInlineCSS($cssCode) {
        $this->inlineCSS[] = $cssCode;
    }

    /**
     * Gibt den eingefügten Inline-CSS-Code zurück.
     *
     * Diese Methode gibt den zuvor mit `addInlineCSS()` eingefügten CSS-Code zurück.
     *
     * @return string Der gesammelte Inline-CSS-Code.
     */
    public function getInlineCSS() {
        return implode("\n", $this->inlineCSS);
    }
}

