<?php
/**
 * Name: Controller
 * Beschreibung: Basisklasse für Controller im Chamy Framework.
 *
 * Author: Markus
 * Datei: core/classes/Controller.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Klasse dient als Basisklasse für alle Controller im Chamy Framework.
 * Klasse: Controller
 * Methoden: - view, - model
 */

namespace Core\Classes;

class Controller {

    /**
     * Lädt ein Modell.
     *
     * Diese Methode lädt ein Modell und gibt eine Instanz davon zurück.
     *
     * @param string $model Der Name des Modells.
     * @return object Eine Instanz des Modells.
     */
    protected function model($model) {
        $modelClass = '\\App\\Models\\' . $model;
        return new $modelClass();
    }
    
    /**
     * Lädt eine Ansicht.
     *
     * Diese Methode lädt eine Ansicht und stellt die angegebenen Daten dar.
     *
     * @param string $view Die Ansichtsdatei, die geladen werden soll.
     * @param array $data Die Daten, die an die Ansicht übergeben werden sollen.
     */
    protected function view($view, $data = []) {
        extract($data);
        include VIEW_PATH . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
    }

    /**
     * Erweitert die aktuelle Ansicht um ein Layout.
     *
     * Diese Methode wird verwendet, um eine Ansicht in ein Layout einzufügen.
     *
     * @param string $layout Das Layout, das erweitert werden soll.
     * @param array $data Die Daten, die an das Layout übergeben werden sollen.
     */
    protected function extend($layout, $data = []) {
        $layoutPath = LAYOUT_PATH . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $layout) . '.php';
        
        if (file_exists($layoutPath)) {
            extract($data);
            include $layoutPath;
        } else {
            echo "Layout not found: $layoutPath";
        }
    }

    /**
     * Erweitert den Inhalt einer Theme-Datei.
     *
     * Diese Methode wird verwendet, um den Inhalt einer Datei aus dem Theme-Verzeichnis zu laden und in die Ansicht einzufügen.
     *
     * @param string $theme Das Theme-Verzeichnis, in dem sich die Datei befindet.
     * @param string $filename Der Name der Datei, deren Inhalt erweitert werden soll.
     * @return string Der erweiterte Inhalt der Datei.
     */
    protected function extendThemeContent($theme, $filename) {
        $filePath = THEME_PATH . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . $filename . '.php';

        if (file_exists($filePath)) {
            ob_start();
            include $filePath;
            return ob_get_clean();
        } else {
            return "File not found: $filePath";
        }
    }
}
