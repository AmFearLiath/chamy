<?php
$url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=INVALID&fields=kind';
echo "Testing file_get_contents...\n";
$opts = stream_context_create(['http' => ['timeout' => 4]]);
$res = @file_get_contents($url, false, $opts);
if ($res === false) {
    echo "file_get_contents: ERR\n";
} else {
    echo "file_get_contents: OK\n";
}

echo "Testing cURL...\n";
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $cres = curl_exec($ch);
    if ($cres === false) {
        echo 'curl error: ' . curl_error($ch) . "\n";
    } else {
        echo "curl: OK\n";
    }
    curl_close($ch);
} else {
    echo "curl not available\n";
}

?>