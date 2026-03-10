<?php

use Chamy\Core\Database\Connection;

return [
    'up' => function (Connection $db): void {
        $prefix = $db->getPrefix();

        // Create default admin user (password: admin — must be changed on first login)
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $passwordHash = password_hash('admin', PASSWORD_BCRYPT, ['cost' => 12]);

        $db->getPdo()->exec("
            INSERT INTO {$prefix}users (uuid, username, email, password_hash, display_name, role, locale)
            VALUES (
                '{$uuid}',
                'admin',
                'admin@chamy.local',
                '{$passwordHash}',
                'Administrator',
                'admin',
                'de'
            )
        ");
    },

    'down' => function (Connection $db): void {
        $prefix = $db->getPrefix();
        $db->getPdo()->exec("DELETE FROM {$prefix}users WHERE username = 'admin'");
    },
];
