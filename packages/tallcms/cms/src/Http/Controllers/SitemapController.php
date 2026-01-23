<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TallCms\Cms\Services\SitemapService;

class SitemapController extends Controller
{
    /**
     * Sitemap index.
     */
    public function index(): Response
    {
        if (! SitemapService::isEnabled()) {
            abort(404);
        }

        $sitemaps = SitemapService::getIndex();

        $content = view('tallcms::seo.sitemap-index', [
            'sitemaps' => $sitemaps,
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Pages sitemap.
     */
    public function pages(): Response
    {
        if (! SitemapService::isEnabled()) {
            abort(404);
        }

        $urls = SitemapService::getPages();

        return $this->renderSitemap($urls);
    }

    /**
     * Posts sitemap (chunked).
     */
    public function posts(int $page): Response
    {
        if (! SitemapService::isEnabled()) {
            abort(404);
        }

        $urls = SitemapService::getPosts($page);

        if ($urls->isEmpty()) {
            abort(404);
        }

        return $this->renderSitemap($urls);
    }

    /**
     * Categories sitemap.
     */
    public function categories(): Response
    {
        if (! SitemapService::isEnabled()) {
            abort(404);
        }

        $urls = SitemapService::getCategories();

        if ($urls->isEmpty()) {
            abort(404);
        }

        return $this->renderSitemap($urls);
    }

    /**
     * Authors sitemap.
     */
    public function authors(): Response
    {
        if (! SitemapService::isEnabled()) {
            abort(404);
        }

        $urls = SitemapService::getAuthors();

        if ($urls->isEmpty()) {
            abort(404);
        }

        return $this->renderSitemap($urls);
    }

    /**
     * Render a sitemap from URLs.
     */
    protected function renderSitemap($urls): Response
    {
        $content = view('tallcms::seo.sitemap', [
            'urls' => $urls,
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
