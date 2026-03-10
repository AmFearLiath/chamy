<?php

declare(strict_types=1);

use Chamy\Core\Database\Connection;
use Chamy\Core\Database\MigrationRunner;

$basePath = dirname(__DIR__);
$lockFile = $basePath . '/storage/install.lock';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$csrf = $_SESSION['install_csrf'] ?? bin2hex(random_bytes(16));
$_SESSION['install_csrf'] = $csrf;

$defaults = [
    'app_name' => 'Chamy',
    'app_env' => 'production',
    'app_debug' => 'false',
    'app_url' => 'http://localhost:8080',
    'app_locale' => 'de',
    'app_fallback_locale' => 'en',
    'db_driver' => 'mysql',
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_database' => 'chamy',
    'db_username' => 'root',
    'db_password' => '',
    'db_charset' => 'utf8mb4',
    'db_collation' => 'utf8mb4_unicode_ci',
    'db_prefix' => '',
    'session_lifetime' => '120',
    'admin_username' => 'admin',
    'admin_email' => 'admin@example.com',
    'admin_display_name' => 'Administrator',
    'admin_password' => '',
    'admin_password_confirm' => '',
];

$values = $defaults;
$errors = [];
$success = false;
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) {
        $values[$key] = trim((string) ($_POST[$key] ?? $values[$key]));
    }

    if (($_POST['csrf_token'] ?? '') !== $csrf) {
        $errors['csrf'] = 'Ungültiges Formular-Token. Bitte Seite neu laden.';
    }

    if (mb_strlen($values['app_name']) < 2 || mb_strlen($values['app_name']) > 80) {
        $errors['app_name'] = 'APP_NAME muss zwischen 2 und 80 Zeichen haben.';
    }

    if (!in_array($values['app_env'], ['production', 'development'], true)) {
        $errors['app_env'] = 'APP_ENV muss production oder development sein.';
    }

    if (!in_array($values['app_debug'], ['true', 'false'], true)) {
        $errors['app_debug'] = 'APP_DEBUG muss true oder false sein.';
    }

    if (filter_var($values['app_url'], FILTER_VALIDATE_URL) === false) {
        $errors['app_url'] = 'APP_URL muss eine gültige URL sein (z. B. http://localhost:8080).';
    }

    $port = (int) $values['db_port'];
    if ($port < 1 || $port > 65535) {
        $errors['db_port'] = 'DB_PORT muss zwischen 1 und 65535 liegen.';
    }

    if ($values['db_host'] === '' || mb_strlen($values['db_host']) > 255) {
        $errors['db_host'] = 'DB_HOST ist erforderlich (max. 255 Zeichen).';
    }

    if ($values['db_database'] === '' || mb_strlen($values['db_database']) > 128) {
        $errors['db_database'] = 'DB_DATABASE ist erforderlich (max. 128 Zeichen).';
    }

    if ($values['db_username'] === '' || mb_strlen($values['db_username']) > 128) {
        $errors['db_username'] = 'DB_USERNAME ist erforderlich (max. 128 Zeichen).';
    }

    // DB prefix is optional. If provided, validate characters and length.
    if ($values['db_prefix'] !== '' && !preg_match('/^[a-zA-Z0-9_]{1,20}$/', $values['db_prefix'])) {
        $errors['db_prefix'] = 'DB_PREFIX darf nur a-z, A-Z, 0-9 und _ enthalten (1-20 Zeichen), oder leer sein.';
    }

    $sessionLifetime = (int) $values['session_lifetime'];
    if ($sessionLifetime < 5 || $sessionLifetime > 1440) {
        $errors['session_lifetime'] = 'SESSION_LIFETIME muss zwischen 5 und 1440 Minuten liegen.';
    }

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,32}$/', $values['admin_username'])) {
        $errors['admin_username'] = 'Admin-Benutzername: 3-32 Zeichen, erlaubt sind a-z, A-Z, 0-9, ., _, -.';
    }

    if (filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['admin_email'] = 'Admin-E-Mail ist ungültig.';
    }

    if (mb_strlen($values['admin_display_name']) < 2 || mb_strlen($values['admin_display_name']) > 120) {
        $errors['admin_display_name'] = 'Admin-Anzeigename muss zwischen 2 und 120 Zeichen haben.';
    }

    if (mb_strlen($values['admin_password']) < 8 || mb_strlen($values['admin_password']) > 72) {
        $errors['admin_password'] = 'Admin-Passwort muss zwischen 8 und 72 Zeichen haben.';
    }

    if ($values['admin_password'] !== $values['admin_password_confirm']) {
        $errors['admin_password_confirm'] = 'Passwort und Bestätigung stimmen nicht überein.';
    }

    if ($errors === []) {
        try {
            $db = new Connection(
                driver: $values['db_driver'],
                host: $values['db_host'],
                port: (int) $values['db_port'],
                database: $values['db_database'],
                username: $values['db_username'],
                password: $values['db_password'],
                charset: $values['db_charset'],
                collation: $values['db_collation'],
                prefix: $values['db_prefix']
            );

            $db->getPdo(); // Connection test

            $runner = new MigrationRunner($db);
            $runner->run($basePath . '/system/migrations');

            $prefix = $db->getPrefix();
            $pdo = $db->getPdo();

            $username = $values['admin_username'];
            $email = $values['admin_email'];
            $displayName = $values['admin_display_name'];
            $passwordHash = password_hash($values['admin_password'], PASSWORD_BCRYPT);
            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

            $stmt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $userId = (int) ($stmt->fetchColumn() ?: 0);

            if ($userId > 0) {
                $update = $pdo->prepare("UPDATE {$prefix}users SET email = :email, password_hash = :password_hash, display_name = :display_name, role = 'admin', is_active = 1, locale = :locale, updated_at = NOW() WHERE id = :id");
                $update->execute([
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'display_name' => $displayName,
                    'locale' => $values['app_locale'],
                    'id' => $userId,
                ]);
            } else {
                $insert = $pdo->prepare("INSERT INTO {$prefix}users (uuid, username, email, password_hash, display_name, role, locale, is_active, created_at, updated_at) VALUES (:uuid, :username, :email, :password_hash, :display_name, 'admin', :locale, 1, NOW(), NOW())");
                $insert->execute([
                    'uuid' => $uuid,
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'display_name' => $displayName,
                    'locale' => $values['app_locale'],
                ]);
                $userId = (int) $pdo->lastInsertId();
            }

            $roleIdStmt = $pdo->query("SELECT id FROM {$prefix}roles WHERE `key` = 'admin' LIMIT 1");
            $adminRoleId = (int) ($roleIdStmt->fetchColumn() ?: 0);
            if ($adminRoleId > 0) {
                $link = $pdo->prepare("INSERT IGNORE INTO {$prefix}user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                $link->execute(['user_id' => $userId, 'role_id' => $adminRoleId]);
            }

            $appKey = 'base64:' . base64_encode(random_bytes(32));

            $env = [];
            $env[] = 'APP_NAME=' . envQuote($values['app_name']);
            $env[] = 'APP_ENV=' . $values['app_env'];
            $env[] = 'APP_DEBUG=' . $values['app_debug'];
            $env[] = 'APP_URL=' . $values['app_url'];
            $env[] = 'APP_KEY=' . $appKey;
            $env[] = 'APP_LOCALE=' . $values['app_locale'];
            $env[] = 'APP_FALLBACK_LOCALE=' . $values['app_fallback_locale'];
            $env[] = 'DB_DRIVER=' . $values['db_driver'];
            $env[] = 'DB_HOST=' . $values['db_host'];
            $env[] = 'DB_PORT=' . $values['db_port'];
            $env[] = 'DB_DATABASE=' . $values['db_database'];
            $env[] = 'DB_USERNAME=' . $values['db_username'];
            $env[] = 'DB_PASSWORD=' . envQuote($values['db_password']);
            $env[] = 'DB_CHARSET=' . $values['db_charset'];
            $env[] = 'DB_COLLATION=' . $values['db_collation'];
            $env[] = 'DB_PREFIX=' . $values['db_prefix'];
            $env[] = 'SESSION_DRIVER=database';
            $env[] = 'SESSION_LIFETIME=' . $values['session_lifetime'];
            $env[] = 'SESSION_NAME=chamy_session';
            $env[] = 'CACHE_DRIVER=file';
            $env[] = 'CACHE_PREFIX=chamy_cache_';
            $env[] = 'LOG_LEVEL=error';
            $env[] = 'LOG_CHANNEL=file';
            $env[] = 'API_VERSION=v1';
            $env[] = 'API_RATE_LIMIT=60';
            $env[] = 'API_RATE_WINDOW=60';
            $env[] = 'ADMIN_THEME=default';
            $env[] = 'FRONTEND_THEME=default';
            $env[] = 'MARKETPLACE_URL=';
            $env[] = 'MARKETPLACE_ENABLED=false';
            $env[] = 'CSRF_ENABLED=true';
            $env[] = 'CORS_ALLOWED_ORIGINS=';
            $env[] = 'DATA_SOURCE=live';

            $envPath = $basePath . '/.env';
            if (is_file($envPath)) {
                @copy($envPath, $basePath . '/.env.bak.' . date('YmdHis'));
            }
            file_put_contents($envPath, implode(PHP_EOL, $env) . PHP_EOL);

            if (!is_dir($basePath . '/storage')) {
                @mkdir($basePath . '/storage', 0755, true);
            }
            file_put_contents($lockFile, json_encode([
                'installed_at' => date('c'),
                'app_url' => $values['app_url'],
                'app_env' => $values['app_env'],
                'db_database' => $values['db_database'],
                'db_host' => $values['db_host'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $success = true;
            $successMessage = 'Installation abgeschlossen. Sie können sich jetzt im Admin-Bereich anmelden.';
        } catch (Throwable $e) {
            $errors['install'] = 'Installation fehlgeschlagen: ' . $e->getMessage();
        }
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function envQuote(string $value): string
{
    if ($value === '') {
        return '';
    }
    if (preg_match('/\s|[#"\"]/u', $value)) {
        return '"' . addcslashes($value, "\\\"") . '"';
    }
    return $value;
}
?><!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chamy Installer</title>
    <style>
        :root {
            --bg: #0e1116;
            --surface: #151a21;
            --surface-2: #1b222b;
            --text: #e7edf5;
            --muted: #9fb0c4;
            --accent: #4da3ff;
            --danger: #ff6b6b;
            --ok: #22c55e;
            --border: #2a3340;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: radial-gradient(circle at 0% 0%, #162536, var(--bg) 35%); color: var(--text); }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <h1>Chamy Installationsmanager</h1>
        <p class="muted">Geführte Installation mit Pflichtfeldern, Validierung, Beispielen, Min/Max-Werten und automatischer Grundkonfiguration.</p>
    </div>

    <?php if ($success): ?>
        <div class="card">
            <div class="ok"><?= h($successMessage) ?></div>
            <p class="help">Nächster Schritt: <a href="/admin/login" style="color:#86c5ff;">Zum Admin-Login</a></p>
        </div>
    <?php else: ?>

    <?php
        // Render the reusable InstallWizard component here
        require_once $basePath . '/core/Components/InstallWizard.php';
        \Chamy\Core\Components\InstallWizard::render($values, $errors, $csrf);
    ?>

    <?php endif; ?>
</div>
</body>
</html>
