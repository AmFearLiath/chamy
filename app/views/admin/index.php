<?php
/**
 * Name: index
 * Beschreibung: Ansicht für die Admin-Startseite.
 *
 * Author: Markus
 * Datei: app/views/admin/index.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Ansicht stellt die Startseite des Adminbereichs der Anwendung dar.
 * Ansicht: admin/index
 */

use Core\Classes\Database;
use Core\Classes\Session;
use Core\Manager\ThemeManager;

include_once HELPER_PATH . '/helper.php';

$db = new Database;
$themeManager = new ThemeManager();
$title = $db->getSetting('sitetitle');
$theme = $themeManager->getActiveBackendThemeInfo();

// Erfasse wichtige Informationen über den aktuellen Benutzer, das Theme und den Inhalt
$user = Session::getUser();

// Passe das gewünschte Theme an
$themePath = $theme['Themepath'];
$backendPath = $db->getSetting('backend_path');
$viewPath = THEME_PATH . $backendPath.'/'.$themePath;
$data = array(
    'path' => $viewPath,
    'title' => $title,
    'theme' => $theme,
    'user' => $user
);

// Lade die Theme Ansicht und übergebe $data als Array
$themeManager->showView($viewPath . '/index.php', $data);
?>
