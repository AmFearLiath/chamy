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
 * Datei: themes/frontend/theme1/index.php
 * Dateityp: Ansicht für die Startseite des Frontend-Themes
 * Erstellt: 15:30 - 25.08.2023
 * 
 * Beschreibung: Diese Ansicht stellt die Startseite des Frontend-Themes dar.
 * Ansicht: frontend/theme1/index
*/
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
</head>
<body>

<header>
    <h1><?php echo $title; ?></h1>
    <p>Willkommen, <?php echo @$user['username']; ?>!</p>
</header>

<nav>
    <!-- Hier können Navigationslinks eingefügt werden -->
</nav>

<main>
    <h2>Startseite</h2>
    <p>Dies ist die Startseite des Frontend-Themes '<?php echo $theme['Themename']; ?>'.</p>
</main>

<footer>
    <p>&copy; <?php echo date('Y'); ?> <?php echo $title; ?></p>
</footer>

</body>
</html>
