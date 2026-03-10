<?php
/**
 * Kontaktformular-Modul – Einstiegspunkt
 *
 * Wird automatisch vom ModuleManager geladen wenn das Modul aktiv ist.
 */

use Chamy\Core\Kernel;

$kernel = Kernel::getInstance();
$router = $kernel->getRouter();
$hooks  = $kernel->hooks();

// ─── Frontend-Route ────────────────────────
$router->get('/contact', function () use ($kernel) {
    $theme   = $kernel->themes();
    $session = $kernel->session();

    return $theme->render('modules/contact_form/form.twig', [
        'csrf_token' => $session->getCsrfToken(),
        'flash'      => $session->getAllFlash(),
    ], 'frontend');
});

$router->post('/contact', function () use ($kernel) {
    $request = \Chamy\Core\Http\Request::capture();
    $session = $kernel->session();

    if (!$session->verifyCsrfToken($request->getPost('_token') ?? '')) {
        $session->flash('error', 'Ungültiges Sicherheitstoken.');
        return \Chamy\Core\Http\Response::redirect('/contact');
    }

    $name    = trim($request->getPost('name') ?? '');
    $email   = trim($request->getPost('email') ?? '');
    $message = trim($request->getPost('message') ?? '');

    $errors = [];
    if ($name === '')    $errors[] = 'Bitte geben Sie Ihren Namen ein.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    if ($message === '') $errors[] = 'Bitte geben Sie eine Nachricht ein.';

    if (!empty($errors)) {
        $session->flash('errors', $errors);
        $session->flash('old', ['name' => $name, 'email' => $email, 'message' => $message]);
        return \Chamy\Core\Http\Response::redirect('/contact');
    }

    // In DB speichern
    $db     = $kernel->db();
    $prefix = $db->getPrefix();
    $db->insert("{$prefix}contact_messages", [
        'name'       => $name,
        'email'      => $email,
        'message'    => $message,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    $session->flash('success', 'Vielen Dank! Ihre Nachricht wurde gesendet.');
    return \Chamy\Core\Http\Response::redirect('/contact');
});

// ─── Admin-Route: Nachrichten ansehen ──────
$router->get('/admin/contact-messages', function () use ($kernel) {
    $session = $kernel->session();
    if (!$session->get('user_id')) {
        return \Chamy\Core\Http\Response::redirect('/admin/login');
    }

    $db       = $kernel->db();
    $prefix   = $db->getPrefix();
    $messages = $db->fetchAll(
        "SELECT * FROM {$prefix}contact_messages ORDER BY created_at DESC LIMIT 50"
    );

    $theme = $kernel->themes();
    return $theme->render('modules/contact_form/admin_list.twig', [
        'messages' => $messages,
    ], 'admin');
});
