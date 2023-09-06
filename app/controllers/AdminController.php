<?php
/**
 * Name: AdminController
 * Beschreibung: Dieser Controller verwaltet die Logik für den Adminbereich der Anwendung.
 *
 * Author: Markus
 * Datei: app/controllers/AdminController.php
 * Erstellt: 15:30 - 25.08.2023
 *
 * Klasse: AdminController
 * Methoden: index
 * Funktionen: -
 */

namespace App\Controllers;

use Core\Classes\Controller;
use Core\Manager\ThemeManager;

class AdminController extends Controller {
    /**
     * Zeigt die Admin-Startseite an.
     *
     * Diese Methode lädt die Startseite des Adminbereichs und zeigt sie an.
     *
     * @return void
     */
    public function index() {

        // Weitere Logik und Datenverarbeitung hier

        // Erstelle eine Instanz des ThemeManagers
        $themeManager = new ThemeManager();
        
        // Zeige die entsprechende Seite mit dem Theme Manager an
        $themeManager->loadView('admin', 'index');
    }
}
?>
