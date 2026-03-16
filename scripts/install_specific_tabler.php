<?php
use Chamy\Core\Bootstrap;
require_once __DIR__ . '/../vendor/autoload.php';
$kernel = Bootstrap::init(dirname(__DIR__));
$assetLib = $kernel->assetLibrary();
$res = $assetLib->installIconSetFromUrl([
    'name' => 'Tabler Icons Webfont',
    'source_url' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css',
    'id' => 'tabler-icons-webfont',
]);
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
