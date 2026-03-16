<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/vendor/autoload.php';
use Chamy\Core\Bootstrap;
$kernel = Bootstrap::init(dirname(__DIR__));
$themeManager = $kernel->themes();
$context = [
    'trash_stats' => ['total'=>1,'trashed'=>1,'restored'=>0,'purged'=>0,'categories'=>[],'types'=>[]],
    'trash_filters' => ['status'=>'trashed','category'=>'all','entity_type'=>'all','sort'=>'deleted_desc','q'=>''],
    'trash_items' => [
        [
            'id' => '1', 'deleted_at' => date('Y-m-d H:i:s'), 'category'=>'content','entity_type'=>'article','entity_key'=>'article-1','status'=>'trashed','payload'=>['name'=>'Test Artikel']
        ]
    ],
    'trash_actors' => [], 'can_manage_trash' => true,
];
try {
    $out = $themeManager->render('trash.twig', $context, 'admin');
    echo "Rendered length: " . strlen($out) . "\n";
    // optional: write to temp file
    file_put_contents(dirname(__DIR__) . '/storage/tmp/trash_render.html', $out);
    echo "Wrote storage/tmp/trash_render.html\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
