<?php
require_once __DIR__ . '/../vendor/autoload.php';
\Chamy\Core\Bootstrap::init(dirname(__DIR__));
$kernel = \Chamy\Core\Kernel::getInstance();
$db = $kernel->db();
$prefix = $db->getPrefix();
$users = $db->fetchAll("SELECT id, username, email, password_hash, role, created_at FROM {$prefix}users ORDER BY id");
foreach ($users as $u) {
    echo "ID: {$u['id']} | username: {$u['username']} | email: {$u['email']} | role: {$u['role']} | created_at: {$u['created_at']}\n";
    $ph = $u['password_hash'] ?? '';
    echo "  password_hash: " . ($ph === null ? 'NULL' : $ph) . "\n";
    echo "  seems_bcrypt: " . (strpos($ph, '$2y$') === 0 || strpos($ph, '$2a$') === 0 ? 'yes' : 'no') . "\n";
}
