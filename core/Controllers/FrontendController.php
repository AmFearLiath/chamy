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

        return Response::html(
            $this->kernel->themes()->render('home.twig', array_merge($this->baseData('home'), [
                'recent_articles' => $recentArticles,
                'recent_pages'    => $recentPages,
                'home_entry'      => $homePage,
                'home_data'       => $homeData,
                'editor_content'  => $editorHtml,
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
