<?php
/**
 * Name: UserController
 * Beschreibung: Controller für Benutzerverwaltung im Chamy Framework.
 *
 * Author: Markus
 * Datei: app/controllers/UserController.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Dieser Controller handhabt die Benutzerverwaltung im Chamy Framework.
 * Klasse: UserController
 * Methoden: - register, - login, - logout, - loginAfterRegistration, - loginAfterSuccessfulLogin
 */

namespace App\Controllers;

use Core\Classes\Controller;
use Core\Classes\Database;
use Core\Classes\Session;
use Core\Manager\UserManager;

class UserController extends Controller {

    private $themeName; // Eigenschaft für den Theme-Namen
    private $db;

    /**
     * Konstruktor.
     * Initialisiert den Theme-Namen aus der Datenbank.
     */
    public function __construct() {
        $this->db = new Database();
        $theme = json_decode($this->db->getSetting('themes'), true); // Annahme: 'theme' ist der Name des Einstellungsschlüssels
        $this->themeName = $theme['backend'] ? $theme['backend'] : 'default'; // Standardwert 'default' verwenden, falls kein Theme in der Datenbank gefunden wird
    }

    /**
     * Zeigt das Registrierungsformular an oder verarbeitet die Registrierungsdaten.
     *
     * @return void
     */
    public function register() {
        $themename = $this->themeName;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Registrierungsdaten aus dem Formular
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            // Überprüfung, ob der Benutzername oder die E-Mail bereits existieren
            $userManager = new UserManager();
            if ($userManager->isUsernameTaken($username)) {
                $data['error'] = 'Der Benutzername ist bereits vergeben.';
                $this->view('admin.register', $data);
                return;
            }

            if ($userManager->isEmailTaken($email)) {
                $data['error'] = 'Die E-Mail-Adresse ist bereits vergeben.';
                $this->view('admin.register', $data);
                return;
            }

            // Benutzerregistrierung
            $userId = $userManager->registerUser($username, $email, $password);

            if ($userId) {
                // Anmeldung nach erfolgreicher Registrierung
                $this->loginAfterRegistration($userId);

                // Weiterleitung zur Startseite oder einer anderen Seite nach erfolgreicher Registrierung
                header('Location: /');
            } else {
                $data['error'] = 'Es ist ein Fehler bei der Registrierung aufgetreten.';
                $this->view('admin.register', $data);
            }
        } else {
            // Zeige das Registrierungsformular
            $this->view('admin.register', ['backendPath' => $this->db->getSetting('frontend_path')]);
        }
    }

    /**
     * Zeigt das Login-Formular an oder verarbeitet die Login-Daten.
     *
     * @return void
     */
    public function login() {
        $themename = $this->themeName;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Hier kommt die Logik zur Verarbeitung der Login-Daten
            // Zum Beispiel: Validierung der Daten, Überprüfung in der Datenbank usw.

            // Nach erfolgreicher Anmeldung kannst du den Benutzer einloggen
            $validatedUserId = 456; // Hier musst du die tatsächliche Benutzer-ID übergeben
            $this->loginAfterSuccessfulLogin($validatedUserId);

            // Weiterleitung zur Startseite oder einer anderen Seite nach erfolgreicher Anmeldung
            header('Location: /');
        } else {
            // Zeige das Login-Formular
            $this->view('admin.login');
        }
    }

    /**
     * Meldet den Benutzer nach erfolgreicher Registrierung an.
     *
     * @param int $userId Die ID des registrierten Benutzers.
     * @return void
     */
    private function loginAfterRegistration($userId) {
        // Führt die Anmeldung des Benutzers nach der Registrierung durch
        Session::login($userId);
    }

    /**
     * Meldet den Benutzer nach erfolgreicher Anmeldung an.
     *
     * @param int $userId Die ID des angemeldeten Benutzers.
     * @return void
     */
    private function loginAfterSuccessfulLogin($userId) {
        // Führt die Anmeldung des Benutzers nach erfolgreicher Anmeldung durch
        Session::login($userId);
    }

    /**
     * Meldet den Benutzer ab und leitet zur Startseite weiter.
     *
     * @return void
     */
    public function logout() {
        // Führt die Abmeldung des Benutzers durch
        Session::logout();

        // Weiterleitung zur Startseite oder einer anderen Seite nach Abmeldung
        header('Location: /');
    }
}
