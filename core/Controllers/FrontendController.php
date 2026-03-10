<?php

declare(strict_types=1);

namespace Chamy\Core\Controllers;

use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Kernel;

final class FrontendController
{
    private Kernel $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    private function baseData(string $route = ''): array
    {
        return [
            'site_name'     => $this->kernel->config()->get('APP_NAME', 'Chamy'),
            'app_locale'    => $this->kernel->config()->get('APP_LOCALE', 'de'),
            'current_route' => $route,
        ];
    }

    /* ─── Home ─── */

    public function home(Request $request): Response
    {
        $data = $this->kernel->data();
        $recentArticles = $data->getContentEntries('article', 'published', 6);
        $recentPages    = $data->getContentEntries('page', 'published', 6);

        return Response::html(
            $this->kernel->themes()->render('home.twig', array_merge($this->baseData('home'), [
                'recent_articles' => $recentArticles,
                'recent_pages'    => $recentPages,
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

        return Response::html(
            $this->kernel->themes()->render('page.twig', array_merge($this->baseData('pages'), [
                'entry'      => $entry,
                'entry_data' => $entry['_data'] ?? [],
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

        return Response::html(
            $this->kernel->themes()->render('article.twig', array_merge($this->baseData('articles'), [
                'entry'      => $entry,
                'entry_data' => $entry['_data'] ?? [],
            ]), 'frontend')
        );
    }
}
