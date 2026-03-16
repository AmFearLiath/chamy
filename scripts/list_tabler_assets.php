<?php
$url = 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css';
$s = @file_get_contents($url);
if ($s === false) { echo "failed fetching css\n"; exit(1); }
if (!preg_match_all('/url\(([^)]+)\)/i', $s, $m)) { echo "no urls\n"; exit(0);} 
$base = $url;
foreach ($m[1] as $raw) {
    $clean = trim($raw, " \t\n\r\0\x0B\"'");
    if ($clean === '' || str_starts_with($clean,'data:')) continue;
    $resolved = resolve($base, $clean);
    echo "$clean -> $resolved\n";
    $ctx = stream_context_create(['http'=>['timeout'=>20,'follow_location'=>1],'ssl'=>['verify_peer'=>false]]);
    $b = @file_get_contents($resolved,false,$ctx);
    echo $b===false?"download_failed\n":"download_ok (".strlen($b).")\n";
}

function resolve($baseUrl, $value) {
    if (preg_match('#^https?://#i', $value)) return $value;
    $parts = parse_url($baseUrl);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return null;
    $scheme = $parts['scheme'];
    $host = $parts['host'];
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    if (str_starts_with($value, '/')) return $scheme.'://'.$host.$port.$value;
    $basePath = (string) ($parts['path'] ?? '/');
    $dir = rtrim(str_replace('\\','/', dirname($basePath)), '/');
    if ($dir === '') $dir = '/';
    $path = ($dir === '/' ? '' : $dir) . '/' . ltrim($value, '/');
    // normalize
    $segments = explode('/', $path);
    $resolvedSegments = [];
    foreach ($segments as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($resolvedSegments); continue; }
        $resolvedSegments[] = $seg;
    }
    $normalizedPath = '/' . implode('/', $resolvedSegments);
    return $scheme.'://'.$host.$port.$normalizedPath;
}
