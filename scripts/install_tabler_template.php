<?php
use Chamy\Core\Bootstrap;
require_once __DIR__ . '/../vendor/autoload.php';
$kernel = Bootstrap::init(dirname(__DIR__));
$assetLib = $kernel->assetLibrary();
$res = $assetLib->installIconSetFromTemplate([
    'name' => 'Tabler Icons Webfont',
    'template_id' => 'jsdelivr-npm',
    'package' => '@tabler/icons-webfont',
    'version' => 'latest',
    'id' => 'tabler-icons-webfont',
]);
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
