<?php
require __DIR__ . '/../vendor/autoload.php';

use Chamy\Core\Managers\LanguageManager;

$base = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';
$langPath = $base . DIRECTORY_SEPARATOR . 'languages';

$lm = new LanguageManager($langPath, 'de', 'en');
$lm->boot();

$keys = [
    'admin.dashboard',
    'menu.title',
    'menu.description',
    'system.loading',
    'admin.save',
];

foreach ($keys as $k) {
    echo $k . ' => ' . $lm->t($k) . PHP_EOL;
}

// Dump if some expected admin keys exist
$sample = $lm->t('admin.actions');
echo "admin.actions => $sample\n";

// Check fallback
echo "fallback for nonexisting => " . $lm->t('this.key.does.not.exist') . PHP_EOL;
