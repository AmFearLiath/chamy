<?php
/**
     _______           _______  _______          
    (  ____ \|\     /|(  ___  )(       )|\     /|
    | (    \/| )   ( || (   ) || () () |( \   / )
    | |      | (___) || (___) || || || | \ (_) / 
    | |      |  ___  ||  ___  || |(_)| |  \   /  
    | |      | (   ) || (   ) || |   | |   ) (   
    | (____/\| )   ( || )   ( || )   ( |   | |   
    (_______/|/     \||/     \||/     \|   \_/  

 Ein leichtgewichtiges, modulares PHP-Framework zur einfachen Erstellung von Webanwendungen. 
 Bietet Themes für Backend und Frontend, Hooks, Module und eine Benutzerverwaltung.
 *
 * Author: Liath <amfearliath@googlemail.com>
 * License: All Rights Reserved
 * 
 * Datei: app/controllers/HomeController.php
 * Dateityp: Controller für die Startseite
 * Erstellt: 15:30 - 25.08.2023
 * 
 * Beschreibung: Dieser Controller verwaltet die Logik für die Startseite der Anwendung.
 * Klasse: HomeController
 * Methoden: index
 * Funktionen: - 
*/

namespace App\Controllers;

use Core\Classes\Controller;
use Core\Classes\Session;
use Core\Manager\ThemeManager;
use App\Models\Route;

class HomeController extends Controller {
    /**
     * Displays the homepage.
     *
     * This method retrieves route information from the database and displays it on the homepage.
     *
     * @return void
     */
    public function index() {

        // Further logic and data processing here 

        // Create an instance of the ThemeManager
        $themeManager = new ThemeManager();
        
        // Display the appropriate page using the theme manager
        $themeManager->loadView('home', 'index');
    }
}
