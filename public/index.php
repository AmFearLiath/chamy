<?php

declare(strict_types=1);

/**
 * Chamy CMS – Front Controller
 *
 * All HTTP requests are routed through this file via .htaccess.
 */

// Prevent direct access to dotfiles and sensitive paths
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
if (preg_match('#(^|/)\.#', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

$installLock = dirname(__DIR__) . '/storage/install.lock';
$installerRoute = ($uri === '/install' || str_starts_with($uri, '/install/'));

// Force setup until installation is completed
if (!file_exists($installLock) && !$installerRoute) {
    header('Location: /install');
    exit;
}

if ($installerRoute) {
    if (file_exists($installLock)) {
        header('Location: /admin/login');
        exit;
    }
    require __DIR__ . '/install.php';
    return;
}

// Serve static theme/module assets for PHP built-in server
if (PHP_SAPI === 'cli-server') {
    $staticFile = dirname(__DIR__) . $uri;
    if ($uri !== '/' && is_file($staticFile)) {
        $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'eot'  => 'application/vnd.ms-fontobject',
            'json' => 'application/json',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
            readfile($staticFile);
            return;
        }
    }
}

// Load Composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    http_response_code(500);
    exit('Composer autoloader not found. Run: composer install');
}
require $autoloader;

// Bootstrap Chamy
\Chamy\Core\Bootstrap::init(dirname(__DIR__));

// Capture Request, dispatch via Kernel
$request  = \Chamy\Core\Http\Request::capture();
$kernel   = \Chamy\Core\Kernel::getInstance();
$response = $kernel->handle($request);

// Send Response
$response->send();
