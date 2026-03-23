<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Editor\EditorRenderer;
use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

final class FrontendController
{
    private Kernel $kernel;
    private EditorRenderer $renderer;

    public function __construct(Kernel $kernel)
    {
        $this->kernel   = $kernel;
        $this->renderer = new EditorRenderer($kernel);
    }

    private function baseData(string $route = ''): array
    {
        return [
            'site_name'     => $this->kernel->config()->get('APP_NAME', 'Chamy'),
            'app_locale'    => $this->kernel->config()->get('APP_LOCALE', 'de'),
            'current_route' => $route,
        ];
    }

    /**
     * If the entry data contains an editor tree, render it to HTML.
     */
    private function renderEditorContent(array $entryData): string
    {
        if (empty($entryData['editor_data'])) {
            return '';
        }
        $editorData = $entryData['editor_data'];
        if (is_string($editorData)) {
            $editorData = json_decode($editorData, true);
        }
        if (!is_array($editorData) || empty($editorData['root'])) {
            return '';
        }
        return $this->renderer->renderFrontendHtml($editorData);
    }

    /* ─── Home ─── */

    public function home(Request $request): Response
    {
        $data = $this->kernel->data();

        // Try to load a page with slug 'startseite' for hero/editor content
        $homePage    = $data->getContentBySlug('page', 'startseite');
        $homeData    = $homePage['_data'] ?? [];
        $editorHtml  = $this->renderEditorContent($homeData);

        $recentArticles = $data->getContentEntries('article', 'published', 6);
        $recentPages    = $data->getContentEntries('page', 'published', 6);

        // Load services for the services grid and references section
        $serviceEntries = $data->getContentEntries('service', 'published', 50);
        $services = [];
        foreach ($serviceEntries as $s) {
            $sd = $s['_data'] ?? (is_string($s['data'] ?? '') ? json_decode($s['data'], true) : ($s['data'] ?? []));
            $sd['id'] = $s['id'] ?? null;
            $services[] = $sd;
        }
        // Sort by sort_order
        usort($services, fn($a, $b) => ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99));

        // Load hero slides from snippet with slug 'hero-slides'
        $slidesSnippet = $data->getContentBySlug('snippet', 'hero-slides');
        $slides = [];
        if ($slidesSnippet) {
            $snippetData = $slidesSnippet['_data'] ?? [];
            $bodyJson = $snippetData['body'] ?? '';
            if (is_string($bodyJson)) {
                $decoded = json_decode($bodyJson, true);
                if (is_array($decoded)) {
                    $slides = $decoded;
                }
            } elseif (is_array($bodyJson)) {
                $slides = $bodyJson;
            }
        }

        return Response::html(
            $this->kernel->themes()->render('home.twig', array_merge($this->baseData('home'), [
                'recent_articles' => $recentArticles,
                'recent_pages'    => $recentPages,
                'home_entry'      => $homePage,
                'home_data'       => $homeData,
                'editor_content'  => $editorHtml,
                'services'        => $services,
                'slides'          => $slides,
            ]), 'frontend')
        );
    }

    /* ─── Services list (Leistungen) ─── */

    public function servicesList(Request $request): Response
    {
        $data    = $this->kernel->data();
        $entries = $data->getContentEntries('service', 'published', 50);

        return Response::html(
            $this->kernel->themes()->render('services.twig', array_merge($this->baseData('services'), [
                'entries' => $entries,
            ]), 'frontend')
        );
    }

    /* ─── Single service (Leistung) ─── */

    public function serviceShow(Request $request): Response
    {
        $slug  = $request->getRouteParam('slug');
        $entry = $this->kernel->data()->getContentBySlug('service', $slug);

        if (!$entry) {
            return Response::html(
                $this->kernel->themes()->render('errors/404.twig', array_merge($this->baseData(), []), 'frontend'),
                404
            );
        }

        $entryData = $entry['_data'] ?? [];

        // Decode JSON fields if they are strings
        foreach (['hero_bullets', 'target_items', 'scope_items', 'process_steps', 'faq_items'] as $jsonField) {
            if (isset($entryData[$jsonField]) && is_string($entryData[$jsonField])) {
                $decoded = json_decode($entryData[$jsonField], true);
                if (is_array($decoded)) {
                    $entryData[$jsonField] = $decoded;
                }
            }
        }

        return Response::html(
            $this->kernel->themes()->render('service.twig', array_merge($this->baseData('services'), [
                'entry'      => $entry,
                'entry_data' => $entryData,
            ]), 'frontend')
        );
    }

    /* ─── Contact (Kontakt) ─── */

    public function contact(Request $request): Response
    {
        $entry = $this->kernel->data()->getContentBySlug('page', 'kontakt');
        $entryData = $entry['_data'] ?? [];
        $session = $this->kernel->session();

        return Response::html(
            $this->kernel->themes()->render('contact.twig', array_merge($this->baseData('contact'), [
                'entry'      => $entry,
                'entry_data' => $entryData,
                'flash'      => $session->getAllFlash(),
                'old'        => $session->getFlash('old', []),
            ]), 'frontend')
        );
    }

    /* ─── Contact form submission ─── */

    public function contactSubmit(Request $request): Response
    {
        $session = $this->kernel->session();

        if (!$session->verifyCsrfToken($request->getPost('_csrf_token') ?? '')) {
            $session->flash('error', 'Ungültiges Sicherheitstoken.');
            return Response::redirect('/kontakt');
        }

        $name    = trim($request->getPost('name') ?? '');
        $email   = trim($request->getPost('email') ?? '');
        $message = trim($request->getPost('message') ?? '');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Bitte geben Sie Ihren Namen ein.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        }
        if ($message === '') {
            $errors[] = 'Bitte geben Sie eine Nachricht ein.';
        }

        if (!empty($errors)) {
            $session->flash('errors', $errors);
            $session->flash('old', ['name' => $name, 'email' => $email, 'message' => $message]);
            return Response::redirect('/kontakt');
        }

        $db     = $this->kernel->db();
        $prefix = $db->getPrefix();
        $stmt = $db->getPdo()->prepare("INSERT INTO {$prefix}contact_messages (name, email, message, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $message, date('Y-m-d H:i:s')]);

        $session->flash('success', 'Vielen Dank! Ihre Nachricht wurde gesendet.');
        return Response::redirect('/kontakt');
    }

    /* ─── References (Referenzen) ─── */

    public function references(Request $request): Response
    {
        $entry = $this->kernel->data()->getContentBySlug('page', 'referenzen');
        $entryData   = $entry ? ($entry['_data'] ?? []) : [];
        $editorHtml  = $entry ? $this->renderEditorContent($entryData) : '';

        return Response::html(
            $this->kernel->themes()->render('references.twig', array_merge($this->baseData('references'), [
                'entry'          => $entry,
                'entry_data'     => $entryData,
                'editor_content' => $editorHtml,
            ]), 'frontend')
        );
    }

    /* ─── Imprint (Impressum) ─── */

    public function imprint(Request $request): Response
    {
        $entry = $this->kernel->data()->getContentBySlug('page', 'impressum');
        $entryData   = $entry ? ($entry['_data'] ?? []) : [];
        $editorHtml  = $entry ? $this->renderEditorContent($entryData) : '';

        return Response::html(
            $this->kernel->themes()->render('page.twig', array_merge($this->baseData('imprint'), [
                'entry'          => $entry,
                'entry_data'     => array_merge($entryData, [
                    'title'   => $entryData['title'] ?? 'Impressum',
                    'badge'   => $entryData['badge'] ?? 'Rechtliches',
                    'excerpt' => $entryData['excerpt'] ?? 'Diese Seite ist ein Platzhalter und wird nach juristischer Prüfung befüllt.',
                ]),
                'editor_content' => $editorHtml,
            ]), 'frontend')
        );
    }

    /* ─── Privacy (Datenschutz) ─── */

    public function privacy(Request $request): Response
    {
        $entry = $this->kernel->data()->getContentBySlug('page', 'datenschutz');
        $entryData   = $entry ? ($entry['_data'] ?? []) : [];
        $editorHtml  = $entry ? $this->renderEditorContent($entryData) : '';

        return Response::html(
            $this->kernel->themes()->render('page.twig', array_merge($this->baseData('privacy'), [
                'entry'          => $entry,
                'entry_data'     => array_merge($entryData, [
                    'title'   => $entryData['title'] ?? 'Datenschutz',
                    'badge'   => $entryData['badge'] ?? 'Rechtliches',
                    'excerpt' => $entryData['excerpt'] ?? 'Diese Seite ist ein Platzhalter und wird nach juristischer Prüfung befüllt.',
                ]),
                'editor_content' => $editorHtml,
            ]), 'frontend')
        );
    }

    /* ─── Pages list ─── */

    public function pagesList(Request $request): Response
    {
        $page    = max(1, (int) $request->getQuery('page', '1'));
        $perPage = 20;
        $data    = $this->kernel->data();
        $total   = $data->countContent('page', 'published');
        $entries = $data->getContentEntries('page', 'published', $perPage, ($page - 1) * $perPage);

        return Response::html(
            $this->kernel->themes()->render('pages.twig', array_merge($this->baseData('pages'), [
                'entries'      => $entries,
                'current_page' => $page,
                'pages'        => max(1, (int) ceil($total / $perPage)),
            ]), 'frontend')
        );
    }

    /* ─── Single page ─── */

    public function pageShow(Request $request): Response
    {
        $slug  = $request->getRouteParam('slug');
        $entry = $this->kernel->data()->getContentBySlug('page', $slug);

        if (!$entry) {
            return Response::html(
                $this->kernel->themes()->render('base.twig', array_merge($this->baseData(), []), 'frontend'),
                404
            );
        }

        $entryData   = $entry['_data'] ?? [];
        $editorHtml  = $this->renderEditorContent($entryData);

        return Response::html(
            $this->kernel->themes()->render('page.twig', array_merge($this->baseData('pages'), [
                'entry'          => $entry,
                'entry_data'     => $entryData,
                'editor_content' => $editorHtml,
            ]), 'frontend')
        );
    }

    /* ─── Articles list ─── */

    public function articlesList(Request $request): Response
    {
        $page    = max(1, (int) $request->getQuery('page', '1'));
        $perPage = 20;
        $data    = $this->kernel->data();
        $total   = $data->countContent('article', 'published');
        $entries = $data->getContentEntries('article', 'published', $perPage, ($page - 1) * $perPage);

        return Response::html(
            $this->kernel->themes()->render('articles.twig', array_merge($this->baseData('articles'), [
                'entries'      => $entries,
                'current_page' => $page,
                'pages'        => max(1, (int) ceil($total / $perPage)),
            ]), 'frontend')
        );
    }

    /* ─── Single article ─── */

    public function articleShow(Request $request): Response
    {
        $slug  = $request->getRouteParam('slug');
        $entry = $this->kernel->data()->getContentBySlug('article', $slug);

        if (!$entry) {
            return Response::html(
                $this->kernel->themes()->render('base.twig', array_merge($this->baseData(), []), 'frontend'),
                404
            );
        }

        $entryData   = $entry['_data'] ?? [];
        $editorHtml  = $this->renderEditorContent($entryData);

        return Response::html(
            $this->kernel->themes()->render('article.twig', array_merge($this->baseData('articles'), [
                'entry'          => $entry,
                'entry_data'     => $entryData,
                'editor_content' => $editorHtml,
            ]), 'frontend')
        );
    }
}
