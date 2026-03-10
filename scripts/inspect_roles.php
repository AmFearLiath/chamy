<?php

require_once __DIR__ . '/../vendor/autoload.php';

$kernel = \Chamy\Core\Bootstrap::init(dirname(__DIR__));
$db = $kernel->db();
try {
    $cols = $db->fetchAll('SHOW COLUMNS FROM ' . $db->table('roles'));
    print_r($cols);
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
