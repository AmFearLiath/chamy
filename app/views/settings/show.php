<?php
/**
 * Name: show
 * Beschreibung: Ansicht für die Anzeige der Einstellungen.
 *
 * Author: Markus
 * Datei: app/views/settings/show.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Ansicht zeigt die Anwendungseinstellungen an.
 * Ansicht: settings/show
 */

// Definiere den Titel der Seite
$title = "Anwendungseinstellungen";

// Inkludiere das Header-Layout
include VIEW_PATH . '/layouts/header.php';
?>

<div class="container">
    <h1>Anwendungseinstellungen</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Wert</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($settings as $setting): ?>
            <tr>
                <td><?php echo $setting['name']; ?></td>
                <td><?php echo $setting; ?></td>
            </tr>
            <?php endforeach ?>
