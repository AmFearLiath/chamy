<?php
/**
 * Name: ThemeManager
 * Beschreibung: Die Klasse ThemeManager verwaltet die Aktivierung von Themes im Frontend und Backend.
 *
 * Author: Markus
 * Datei: core/manager/ThemeManager.php
 * Erstellt: 2023-08-28 - 15:30 Uhr
 *
 * Diese Klasse bietet Funktionen zum Verwalten von Themes im Frontend und Backend.
 * Dabei werden Themes aktiviert, deaktiviert und deren Informationen abgerufen.
 * Die Themes werden aus den entsprechenden Ordnern ausgelesen und die Informationen aus theme.json-Dateien gelesen.
 */
namespace Core\Manager;

use Core\Classes\Controller;
use Core\Classes\Database;
use App\Models\Themes;
use App\Models\Route;

class ThemeManager extends Controller {
    private $database;
    private $themes;

    /**
     * Konstruktor.
     * Initialisiert eine Instanz der Database-Klasse.
     */
    public function __construct() {
        $this->database = new Database();
        $this->themes = new Themes();
    }

    /**
     * Gibt den Pfad zum View einer Ansicht im aktiven Theme zurück.
     *
     * @param string $viewName Der Name der Ansicht.
     * @return string Der Pfad zur View-Datei.
     */
    public function getViewPath($viewName) {
        $theme = $this->getActiveTheme();
        return APP_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . $viewName . '.php';
    }

    /**
     * Setzt das aktive Frontend-Theme.
     *
     * @param string $theme Das ausgewählte Frontend-Theme.
     * @return bool Gibt true zurück, wenn das Theme erfolgreich gesetzt wurde, ansonsten false.
     */
    public function setFrontendTheme($theme) {
        return $this->database->setFrontendTheme($theme); // Hier fehlte der Aufruf der setFrontendTheme-Methode
    }

    /**
     * Setzt das aktive Backend-Theme.
     *
     * @param string $theme Das ausgewählte Backend-Theme.
     * @return bool Gibt true zurück, wenn das Theme erfolgreich gesetzt wurde, ansonsten false.
     */
    public function setBackendTheme($theme) {
        return $this->database->setBackendTheme($theme); // Hier fehlte der Aufruf der setBackendTheme-Methode
    }

    /**
     * Gibt das aktuell ausgewählte Frontend-Theme zurück.
     *
     * @return string Das aktive Frontend-Theme.
     */
    public function getFrontendTheme() {
        $themes = json_decode($this->database->getSetting('themes'), true);
        return $themes['frontend'];
    }

    /**
     * Gibt das aktuell ausgewählte Backend-Theme zurück.
     *
     * @return string Das aktive Backend-Theme.
     */
    public function getBackendTheme() {
        $themes = json_decode($this->database->getSetting('themes'), true);
        return $themes['backend'];
    }

    /**
     * Gibt den Pfad zum Backend-Theme zurück.
     *
     * @return string Der Pfad zum Backend-Theme.
     */
    public static function getBackendPath() {
        $database = new Database();
        $settings = $database->getSetting('backend_path');
        return isset($settings) ? $settings : '/backend';
    }

    /**
     * Gibt den Pfad zum Frontend-Theme zurück.
     *
     * @return string Der Pfad zum Frontend-Theme.
     */
    public static function getFrontendPath() {
        $database = new Database();
        $settings = $database->getSetting('frontend_path');
        return isset($settings) ? $settings : '/';
    }

    /**
     * Gibt die Liste der verfügbaren Frontend-Themes zurück.
     *
     * @return array Ein Array mit den Theme-Informationen der verfügbaren Frontend-Themes.
     */
    public function getAvailableFrontendThemes() {
        $themesDirectory = 'themes/frontend';
        $themeFolders = array_diff(scandir($themesDirectory), ['.', '..']);
        $availableThemes = [];

        foreach ($themeFolders as $themeFolder) {
            $themeInfo = $this->getThemeInfo($themesDirectory . '/' . $themeFolder);
            if ($themeInfo) {
                $availableThemes[] = $themeInfo;
            }
        }

        return $availableThemes;
    }

    /**
     * Gibt die Liste der verfügbaren Backend-Themes zurück.
     *
     * @return array Ein Array mit den Namen der verfügbaren Backend-Themes.
     */
    public function getAvailableBackendThemes() {
        $themesDirectory = 'themes/backend';
        $themeFolders = array_diff(scandir($themesDirectory), ['.', '..']);
        $availableThemes = [];

        foreach ($themeFolders as $themeFolder) {
            $themeInfo = $this->getThemeInfo($themesDirectory . '/' . $themeFolder);
            if ($themeInfo) {
                $availableThemes[] = $themeInfo;
            }
        }

        return $availableThemes;
    }

    /**
     * Liest die Theme-Informationen aus der theme.json-Datei im angegebenen Ordner.
     *
     * @param string $themeFolder Der Pfad zum Theme-Ordner.
     * @return array|null|string Ein Array mit den Theme-Informationen, 'theme.json is not compatible' oder null, wenn die theme.json nicht vorhanden oder ungültig ist.
     */
    private function getThemeInfo($themeFolder) {
        $themeInfoFile = $themeFolder . '/theme.json';
        
        if (file_exists($themeInfoFile)) {
            // Lese den Inhalt der theme.json-Datei und dekodiere ihn als assoziatives Array
            $themeInfo = json_decode(file_get_contents($themeInfoFile), true);
            
            // Liste der erforderlichen Schlüssel
            $requiredKeys = ['Themename', 'Themepath', 'Themeversion', 'ChamyVersion'];
            
            // Initialisiere ein leeres Array für potenzielle Fehler
            $themeError = array();

            // Überprüfe jeden erforderlichen Schlüssel
            foreach ($requiredKeys as $key) {
                if (!isset($themeInfo[$key])) {
                    // Füge fehlenden Schlüssel zur Fehlerliste hinzu
                    $themeError[] = $key;
                }
            }

            // Wenn keine Fehler aufgetreten sind
            if (empty($themeError)) {
                return $themeInfo; // Gebe die Theme-Informationen zurück
            } else {
                return 'theme.json is not compatible'; // Gebe Fehlermeldung zurück
            }
        }
        
        return null; // Gebe null zurück, wenn theme.json nicht vorhanden ist
    }

    /**
     * Ruft die Einstellungen für das aktive Theme aus der Datenbank ab, abhängig vom Kontext (Backend/Frontend).
     *
     * @return array|null Ein assoziatives Array mit den Einstellungen des aktiven Themes oder null, wenn das Theme nicht gefunden wurde.
     */
    public function getThemeSettings() {
        // Bestimme den Kontext (Backend/Frontend) des Aufrufs
        $isBackend = $this->isBackendRequest();

        // Hole das aktive Theme basierend auf dem Kontext
        $activeTheme = $isBackend ? $this->getBackendTheme() : $this->getFrontendTheme();

        // Stelle eine Verbindung zur Datenbank her
        $db = new Database();

        // Abfrage zum Abrufen der Einstellungen für das aktive Theme
        $query = "SELECT * FROM settings WHERE name = :theme";
        $params = ['theme' => $activeTheme];
        $settings = $db->fetch($query, $params);

        return $settings;
    }


    /**
     * Gibt die Informationen zum aktiven Backend-Theme zurück.
     *
     * @return array|null Ein Array mit den Informationen des aktiven Backend-Themes oder null, wenn das Theme nicht gefunden wird.
     */
    public function getActiveBackendThemeInfo() {
        $backendTheme = $this->getBackendTheme();
        $info = $this->getThemeInfo("themes/backend/$backendTheme");
        return $info;
    }

    /**
     * Gibt die Informationen zum aktiven Frontend-Theme zurück.
     *
     * @return array|null Ein Array mit den Informationen des aktiven Frontend-Themes oder null, wenn das Theme nicht gefunden wird.
     */
    public function getActiveFrontendThemeInfo() {
        $frontendTheme = $this->getFrontendTheme();
        $info = $this->getThemeInfo("themes/frontend/$frontendTheme");
        return $info;
    }

    /**
     * Gibt das aktive Theme (Frontend oder Backend) zurück.
     *
     * @return string Der Name des aktiven Themes.
     */
    public function getActiveTheme() {
        // Prüfe, ob das aktive Theme ein Frontend- oder Backend-Theme ist
        $frontendTheme = $this->getFrontendTheme();
        $backendTheme = $this->getBackendTheme();
        
        // Gib das entsprechende aktive Theme zurück
        return $this->isFrontendRequest() ? $frontendTheme : $backendTheme;
    }

    /**
     * Prüft, ob die aktuelle Anfrage für das Frontend erfolgt.
     *
     * @return bool True, wenn die Anfrage für das Frontend ist, sonst false.
     */
    public function isFrontendRequest() {
        // Hole den aktuell aufgerufenen Pfad der URL
        $currentPath = $_SERVER['REQUEST_URI'];

        // Vergleiche den aktuellen Pfad mit dem Frontend-Pfad aus den Einstellungen
        $frontendPath = self::getFrontendPath();

        // Prüfe, ob der aktuelle Pfad mit dem Frontend-Pfad beginnt
        return strpos($currentPath, $frontendPath) === 0;
    }

    /**
     * Prüft, ob die aktuelle Anfrage für das Backend erfolgt.
     *
     * @return bool True, wenn die Anfrage für das Backend ist, sonst false.
     */
    public function isBackendRequest() {
        // Hole den aktuellen Pfad der URL
        $currentPath = $_SERVER['REQUEST_URI'];

        // Hole den Backend-Pfad aus den Einstellungen
        $backendPath = self::getBackendPath();

        // Prüfe, ob der aktuelle Pfad mit dem Backend-Pfad beginnt
        return strpos($currentPath, $backendPath) === 0;
    }

    /**
     * Zeigt eine bestimmte Seite basierend auf der angegebenen Route, Ansicht und Daten an.
     *
     * Diese Methode zeigt eine bestimmte Seite basierend auf der angegebenen Route an und übergibt Informationen an die Ansicht.
     *
     * @param string $route Die Zielroute (z. B. 'admin/home').
     * @param string $viewName Der Name der anzuzeigenden Ansicht.
     * @param array $data Zusätzliche Informationen, die an die Ansicht übergeben werden sollen.
     * @return void
     */
    public function loadView($route, $viewName, $data = array()) {

        // Erstelle eine Instanz des Route-Modells und erhalte die Routen
        $routeModel = new Route();
        $routes = $routeModel->getAllRoutes();

        // Füge zusätzliche Daten mit gemeinsamen Daten zusammen
        $mergedData = array_merge([
            'routes' => $routes
        ], $data);

        // Gebe Daten an die Ansicht weiter
        $this->view($route . '/' . $viewName, $mergedData); // Hier wird der $route-Parameter verwendet
    }

    /**
     * Zeigt eine bestimmte Seite basierend auf der angegebenen Route, Ansicht und Daten an.
     *
     * Diese Methode lädt eine Ansicht und zeigt sie basierend auf der angegebenen Route an und übergibt Informationen an die Ansicht.
     *
     * @param string $viewPath Der Pfad zur Ansichtsdatei.
     * @param array $data Zusätzliche Informationen, die an die Ansicht übergeben werden sollen.
     * @return void
     */
    public function showView($viewPath, $data) {
        extract($data);
        include $viewPath;
    }

    /**
     * Fügt eine Schriftart zum aktuellen Theme hinzu.
     *
     * Diese Methode fügt eine Schriftart zur Liste der im aktuellen Theme geladenen Schriftarten hinzu.
     *
     * @param string $fontName Der Name der Schriftart (ohne Dateiendung).
     * @param string $fontExtension Die Dateiendung der Schriftart (z.B. "woff2").
     * @param string $fontPath Das Verzeichnis im Theme, in dem die Schriftart liegt.
     * @return bool Gibt true zurück, wenn die Schriftart erfolgreich hinzugefügt wurde, sonst false.
     */
    public function addFont($fontName, $fontExtension, $fontPath = 'fonts') {
        $theme = $this->getActiveFrontendThemeInfo();
        $fontUrl = THEME_PATH . '/' . $fontPath . '/' . $fontName . '.' . $fontExtension;
        $css = "@font-face {
            font-family: '$fontName';
            src: url('$fontUrl') format('$fontExtension');
        }";
        $this->themes->addInlineCSS($css);

        // Datenbank-Operation: Schriftart-Einstellungen in die Datenbank einfügen
        $themeId = $theme['id'];
        $fonts = $theme['fonts'] ?? [];
        $fonts[] = array(
            'name' => $fontName,
            'extension' => $fontExtension,
            'path' => $fontPath
        );
        $updatedFonts = json_encode($fonts);
        $query = "UPDATE themes SET fonts = :fonts WHERE id = :themeId";
        $params = ['fonts' => $updatedFonts, 'themeId' => $themeId];
        return $this->database->query($query, $params) !== false;
    }

    /**
     * Aktualisiert eine Schriftart im aktuellen Theme.
     *
     * Diese Methode aktualisiert die Einstellungen einer Schriftart im aktuellen Theme.
     *
     * @param string $fontName Der Name der Schriftart (ohne Dateiendung).
     * @param string $fontExtension Die neue Dateiendung der Schriftart (z.B. "woff2").
     * @param string $fontPath Das neue Verzeichnis im Theme, in dem die Schriftart liegt.
     * @return bool Gibt true zurück, wenn die Schriftart erfolgreich aktualisiert wurde, sonst false.
     */
    public function updateFont($fontName, $fontExtension, $fontPath = 'fonts') {
        $theme = $this->getActiveFrontendThemeInfo();
        // Datenbank-Operation: Schriftart-Einstellungen in der Datenbank aktualisieren
        $themeId = $theme['id'];
        $fonts = $theme['fonts'] ?? [];
        foreach ($fonts as &$font) {
            if ($font['name'] === $fontName) {
                $font['extension'] = $fontExtension;
                $font['path'] = $fontPath;
                break;
            }
        }
        $updatedFonts = json_encode($fonts);
        $query = "UPDATE themes SET fonts = :fonts WHERE id = :themeId";
        $params = ['fonts' => $updatedFonts, 'themeId' => $themeId];
        return $this->database->query($query, $params) !== false;
    }

    /**
     * Entfernt eine Schriftart aus dem aktuellen Theme.
     *
     * Diese Methode entfernt eine Schriftart aus der Liste der im aktuellen Theme geladenen Schriftarten.
     *
     * @param string $fontName Der Name der zu entfernenden Schriftart (ohne Dateiendung).
     * @return bool Gibt true zurück, wenn die Schriftart erfolgreich entfernt wurde, sonst false.
     */
    public function removeFont($fontName) {
        $theme = $this->getActiveFrontendThemeInfo();
        // Datenbank-Operation: Schriftart aus der Datenbank entfernen
        $themeId = $theme['id'];
        $fonts = $theme['fonts'] ?? [];
        $updatedFonts = array_filter($fonts, function ($font) use ($fontName) {
            return $font['name'] !== $fontName;
        });
        $updatedFonts = array_values($updatedFonts);
        $updatedFonts = json_encode($updatedFonts);
        $query = "UPDATE themes SET fonts = :fonts WHERE id = :themeId";
        $params = ['fonts' => $updatedFonts, 'themeId' => $themeId];
        return $this->database->query($query, $params) !== false;
    }

    /**
     * Gibt alle im aktuellen Theme geladenen Schriftarten zurück.
     *
     * Diese Methode gibt eine Liste der im aktuellen Theme geladenen Schriftarten zurück.
     *
     * @return array Ein Array von Schriftarten.
     */
    public function getAllFonts() {
        $theme = $this->getActiveFrontendThemeInfo();
        return $theme['fonts'] ?? [];
    }

    public function loadCSS($file, $path) {
        return $this->themes->loadCSS($file, $path);
    }

    public function loadJS($file, $path) {
        return $this->themes->loadJS($file, $path);
    }

    public function loadIMG($file, $path) {
        return $this->themes->loadIMG($file, $path);
    }
}
?>
