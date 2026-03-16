<?php
declare(strict_types=1);

use Chamy\Core\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';

$kernel = Bootstrap::init(dirname(__DIR__));
$assetLib = $kernel->assetLibrary();

$sets = $assetLib->listIconSets();
if (!is_array($sets) || $sets === []) {
    echo "No icon sets registered.\n";
    exit(0);
}

foreach ($sets as $set) {
    $id = (string) ($set['id'] ?? '');
    $name = (string) ($set['name'] ?? '');
    $source = (string) ($set['source_url'] ?? '');
    if ($id === '' || $source === '') {
        echo "Skipping incomplete set: $id ($name)\n";
        continue;
    }
    echo "Reinstalling: $id ($name) from $source\n";
    $res = $assetLib->installIconSetFromUrl(['name' => $name, 'source_url' => $source, 'id' => $id]);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
}

echo "Done.\n";
