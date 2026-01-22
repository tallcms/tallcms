<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\SiteSetting;

class RssFeedController extends Controller
{
    /**
     * Main RSS feed for all posts.
     */
    public function index(): Response
    {
        // Check if RSS is enabled
        if (! SiteSetting::get('seo_rss_enabled', true)) {
            abort(404);
        }

        $limit = (int) SiteSetting::get('seo_rss_limit', 20);
        $includeFullContent = (bool) SiteSetting::get('seo_rss_full_content', false);

        $posts = CmsPost::published()
            ->with(['categories', 'author'])
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->buildFeed($posts, [
            'title' => SiteSetting::get('site_name', config('app.name')).' RSS Feed',
            'description' => SiteSetting::get('site_description', ''),
            'link' => url('/'),
            'feedLink' => $this->getFeedUrl(),
            'includeFullContent' => $includeFullContent,
        ]);
    }

    /**
     * Category-specific RSS feed.
     */
    public function category(string $slug): Response
    {
        // Check if RSS is enabled
        if (! SiteSetting::get('seo_rss_enabled', true)) {
            abort(404);
        }

        $category = CmsCategory::where('slug', $slug)->firstOrFail();

        $limit = (int) SiteSetting::get('seo_rss_limit', 20);
        $includeFullContent = (bool) SiteSetting::get('seo_rss_full_content', false);

        $posts = CmsPost::inCategory($slug)
            ->published()
            ->with(['categories', 'author'])
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();

        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        return $this->buildFeed($posts, [
            'title' => $category->name.' - '.SiteSetting::get('site_name', config('app.name')),
            'description' => $category->description ?? "Posts in {$category->name}",
            'link' => url($prefix.'/category/'.$slug),
            'feedLink' => $this->getFeedUrl().'/category/'.$slug,
            'includeFullContent' => $includeFullContent,
        ]);
    }

    /**
     * Build and return the RSS feed response.
     */
    protected function buildFeed($posts, array $options): Response
    {
        $content = view('tallcms::seo.rss', [
            'posts' => $posts,
            'feedTitle' => $options['title'],
            'feedDescription' => $options['description'],
            'feedLink' => $options['link'],
            'feedUrl' => $options['feedLink'],
            'includeFullContent' => $options['includeFullContent'],
            'lastBuildDate' => $posts->isNotEmpty() ? $posts->first()->published_at : now(),
        ])->render();

        return response($content, 200)
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Get the base feed URL.
     */
    protected function getFeedUrl(): string
    {
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        return url($prefix.'/feed');
    }
}
