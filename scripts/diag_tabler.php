<?php
$s = @file_get_contents('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css');
if ($s === false) {
    echo "fetch_failed\n";
    exit(1);
}
if (preg_match_all('/url\(([^)]+)\)/i', $s, $m)) {
    foreach ($m[1] as $u) {
        echo trim($u) . "\n";
    }
} else {
    echo "no_urls\n";
}

echo "--- content excerpt ---\n";
echo substr($s,0,800);
