<?php
declare(strict_types=1);

// Replace TODO placeholders in languages/en.php and languages/de.php
// Usage: php scripts/fill_i18n_placeholders.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

function load_lang(string $file): array
{
    if (!is_file($file)) return [];
    $arr = include $file;
    return is_array($arr) ? $arr : [];
}

function flatten(array $arr, string $prefix = ''): array
{
    $out = [];
    foreach ($arr as $k => $v) {
        $key = $prefix === '' ? $k : $prefix . '.' . $k;
        if (is_array($v)) {
            $out = array_merge($out, flatten($v, $key));
        } else {
            $out[$key] = $v;
        }
    }
    return $out;
}

function unflatten(array $flat): array
{
    $out = [];
    foreach ($flat as $k => $v) {
        $parts = explode('.', $k);
        $ref = &$out;
        foreach ($parts as $p) {
            if (!isset($ref[$p]) || !is_array($ref[$p])) $ref[$p] = [];
            $ref = &$ref[$p];
        }
        $ref = $v;
        unset($ref);
    }
    return $out;
}

$base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'languages';
$files = [
    $base . DIRECTORY_SEPARATOR . 'en.php',
    $base . DIRECTORY_SEPARATOR . 'de.php',
];

$changed = 0;
foreach ($files as $file) {
    $lang = basename($file, '.php');
    $data = load_lang($file);
    $flat = flatten($data);
    foreach ($flat as $k => $v) {
        if (!is_string($v)) continue;
        if (str_starts_with($v, 'TODO: translate:')) {
            $new = trim(substr($v, strlen('TODO: translate:')));
            $flat[$k] = $new;
            $changed++;
        } elseif (str_starts_with($v, 'TODO: übersetzen:')) {
            $new = trim(substr($v, strlen('TODO: übersetzen:')));
            $flat[$k] = $new;
            $changed++;
        }
    }

    ksort($flat);
    $nested = unflatten($flat);
    $export = var_export($nested, true);
    $content = "<?php\n\nreturn " . $export . ";\n";
    file_put_contents($file, $content);
    echo "Updated {$file} (keys: " . count($flat) . ")\n";
}

echo "Placeholders replaced: {$changed}\n";

exit(0);
