<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=chamy', 'root', '');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . implode(', ', $tables) . PHP_EOL;
foreach (['roles', 'permissions', 'role_permissions'] as $t) {
    echo $t . ': ';
    if (in_array($t, $tables)) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo $count . " rows\n";
        if ($count > 0) {
            $rows = $pdo->query("SELECT * FROM `$t` LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) echo "  " . json_encode($r) . "\n";
        }
    } else {
        echo "MISSING\n";
    }
}
