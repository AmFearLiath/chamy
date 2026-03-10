<?php
require_once __DIR__ . '/../vendor/autoload.php';

$kernel = \Chamy\Core\Bootstrap::init(dirname(__DIR__));
$db = $kernel->db();

try {
    $rows = $db->fetchAll('SELECT id, `key`, description, `group` FROM ' . $db->table('permissions') . ' ORDER BY id ASC');
    foreach ($rows as $r) {
        echo sprintf("%d\t%s\t%s\t%s\n", $r['id'], $r['key'], $r['description'], $r['group']);
    }
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
