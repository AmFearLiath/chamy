<?php
// scripts/terminal_panel.php
// CLI: statische Terminal-Anzeige mit festen Feldern und einer Live-Ausgabe-Zone
// Usage: php scripts/terminal_panel.php [logfile] [status.json]

date_default_timezone_set('Europe/Berlin');

$logFile = $argv[1] ?? __DIR__ . '/terminal.log';
$statusFile = $argv[2] ?? __DIR__ . '/terminal_status.json';
$refresh = 2; // seconds
$liveLines = 12; // number of lines reserved for live output

function clearScreen()
{
    // Clear screen and move cursor to home
    echo "\033[2J\033[H";
}

function tailFile(string $file, int $lines = 100): array
{
    if (!is_readable($file)) return [];
    $f = fopen($file, 'r');
    if (!$f) return [];
    $buffer = '';
    $pos = -1;
    $lineCount = 0;
    $chunk = '';
    $data = '';
    fseek($f, 0, SEEK_END);
    $filesize = ftell($f);
    while ($lineCount <= $lines && $pos < $filesize) {
        $pos++;
        $seek = max(0, $filesize - ($pos * 512));
        fseek($f, $seek);
        $chunk = fread($f, min(512, $filesize - $seek));
        $data = $chunk . $data;
        $lineCount = substr_count($data, "\n");
        if ($seek === 0) break;
    }
    fclose($f);
    $all = explode("\n", trim($data, "\n"));
    return array_slice($all, -$lines);
}

function readStatus(string $file): array
{
    $defaults = [
        'status' => 'OK',
        'lastAction' => '-',
        'activeUsers' => 0,
        'uptime' => '00:00:00'
    ];
    if (!is_readable($file)) return $defaults;
    $json = @file_get_contents($file);
    if (!$json) return $defaults;
    $data = json_decode($json, true);
    if (!is_array($data)) return $defaults;
    return array_merge($defaults, $data);
}

// Detect terminal width for basic layout
$termCols = (int)(`tput cols 2>/dev/null`) ?: (int)@getenv('COLUMNS') ?: 80;
$boxWidth = min(78, max(60, $termCols - 2));

// Main loop
while (true) {
    $status = readStatus($statusFile);
    $lines = tailFile($logFile, $liveLines);

    clearScreen();

    // Header box
    $line = str_repeat('═', $boxWidth);
    echo "╔{$line}╗\n";
    $title = " Chamy CMS — Terminal Status ";
    $pad = $boxWidth - mb_strlen($title);
    $left = (int)floor($pad / 2);
    $right = $pad - $left;
    echo "║" . str_repeat(' ', $left) . $title . str_repeat(' ', $right) . "║\n";
    echo "╚{$line}╝\n";

    // Fixed fields row
    printf("%-20s %-38s\n", "Status:", $status['status']);
    printf("%-20s %-38s\n", "Letzte Aktion:", $status['lastAction']);
    printf("%-20s %-38s\n", "Aktive Benutzer:", $status['activeUsers']);
    printf("%-20s %-38s\n", "Uptime:", $status['uptime']);

    echo str_repeat('-', $boxWidth) . "\n";

    // Live output area header (space reserved)
    echo "Live-Ausgabe (unten):\n";
    echo str_repeat(' ', 0) . "\n";

    // Print last N lines
    $count = 0;
    foreach ($lines as $ln) {
        echo $ln . "\n";
        $count++;
    }

    // Fill remaining lines so layout stays stable
    for ($i = $count; $i < $liveLines; $i++) echo "\n";

    echo "\n(Platz für Live-Terminal unten — drücke Ctrl+C zum Beenden)\n";

    // Wait before refresh
    sleep($refresh);
}
