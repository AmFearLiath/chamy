<?php
$url = 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/fonts/tabler-icons.woff2?v3.40.0';
$ctx = stream_context_create(['http'=>['timeout'=>20,'follow_location'=>1],'ssl'=>['verify_peer'=>false]]);
$s = @file_get_contents($url, false, $ctx);
if ($s === false) {
    echo "fetch_failed\n";
    $info = error_get_last();
    var_export($info);
    exit(1);
}
echo "fetched: " . strlen($s) . " bytes\n";
