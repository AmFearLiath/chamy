<?php
/**
 * Name: index
 * Beschreibung: Ansicht für die Startseite.
 *
 * Author: Markus
 * Datei: app/views/home/index.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Ansicht stellt die Startseite der Anwendung dar.
 * Ansicht: home/index
 */

use Core\Classes\Database;
use Core\Classes\Session;
use Core\Manager\ThemeManager;

include_once HELPER_PATH . '/helper.php';
$db = new Database;
$themeManager = new ThemeManager;

$title = $db->getSetting('sitetitle');
$theme = $themeManager->getActiveFrontendThemeInfo();

// Gather essential information about the current user, theme, and content
$user = Session::getUser();

// An dieser Stelle soll das gewünschte Theme aufgerufen werden
$themePath = $theme['Themepath'];
$viewPath = $themePath . '/index.php';
$data = array(
    'title' => $title,
    'theme' => $theme,
    'user' => $user
);

// Lade die Theme Ansicht und übergebe $data als Array
$themeManager->showView($viewPath, $data);

?>
