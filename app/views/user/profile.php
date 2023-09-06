<?php
/**
 * Name: profile
 * Beschreibung: Ansicht für das Benutzerprofil.
 *
 * Author: Markus
 * Datei: app/views/user/profile.php
 * Erstellt: Datum - Zeit
 *
 * Beschreibung: Diese Ansicht zeigt das Benutzerprofil an.
 * Ansicht: user/profile
 */

// Definiere den Titel der Seite
$title = "Benutzerprofil";

// Inkludiere das Header-Layout
include VIEW_PATH . '/layouts/header.php';
?>

<div class="container">
    <h1>Dein Benutzerprofil</h1>
    <p>Hier findest du Informationen zu deinem Benutzerkonto.</p>

    <table class="table">
        <tr>
            <th>Benutzername:</th>
            <td><?php echo $user['username']; ?></td>
        </tr>
        <tr>
            <th>E-Mail:</th>
            <td><?php echo $user['email']; ?></td>
        </tr>
        <tr>
            <th>Rolle:</th>
            <td><?php echo $user['role']; ?></td>
        </tr>
    </table>
</div>

<?php
// Inkludiere das Footer-Layout
include VIEW_PATH . '/layouts/footer.php';
?>
