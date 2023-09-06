<?php

// Funktion, um HTML-Content von einer URL abzurufen
function fetchURL($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

// Basis-URL deiner Webseite
$baseURL = "https://www.lotto.de/lotto-6aus49/lottozahlen"; // Beispiel: "https://www.deinewebsite.com"

// Array von Jahren und Tagen
$years = array(2023, 2022); // Füge hier die Jahre hinzu, die du abrufen möchtest

foreach ($years as $year) {
    // URL für das Jahr
    $yearURL = $baseURL . "/lotto-jahr-" . $year; // Beispiel: "https://www.deinewebsite.com/lotto-jahr-2023"
    
    // HTML-Content des Jahres abrufen
    $yearContent = fetchURL($yearURL);
    
    // Parsing des HTML-Inhalts, um die Tage der Ziehungen zu finden
    preg_match_all('/<option value="(\d{4}-\d{2}-\d{2})">/', $yearContent, $matches);
    $drawDates = $matches[1]; // Array der Ziehungstage im Format "YYYY-MM-DD"
    
    foreach ($drawDates as $drawDate) {
        // URL für die Ziehung
        $drawURL = $baseURL . "/lotto-ziehung-" . $drawDate; // Beispiel: "https://www.deinewebsite.com/lotto-ziehung-2023-08-26"
        
        // HTML-Content der Ziehung abrufen
        $drawContent = fetchURL($drawURL);
        
        // Parsing des HTML-Inhalts, um Lottozahlen, Superzahl, Spiel 77 und Super 6 zu finden
        preg_match('/<div class="DrawNumbersCollection__container">(.+?)<\/div>/', $drawContent, $numbersMatch);
        $drawNumbers = strip_tags($numbersMatch[1]); // Lottozahlen, Superzahl usw. ohne HTML-Tags
        
        preg_match('/<div class="WinningNumbersAdditionalGame">(.+?)<\/div>/', $drawContent, $additionalGamesMatch);
        $additionalGames = strip_tags($additionalGamesMatch[1]); // Spiel 77 und Super 6 ohne HTML-Tags
        
        // Ausgabe der Daten für diese Ziehung
        echo "Ziehung am $drawDate:<br>";
        echo "Lottozahlen: $drawNumbers<br>";
        echo "Zusatzspiele: $additionalGames<br>";
        echo "<br>";
    }
}

?>
