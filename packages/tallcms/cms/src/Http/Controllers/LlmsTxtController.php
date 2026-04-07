<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SeoService;

class LlmsTxtController extends Controller
{
    /**
     * Render llms.txt — a machine-readable content index for AI systems.
     *
     * Auto-generated from published content with admin-controlled
     * preamble, section toggles, and post limits.
     */
    public function __invoke(): Response
    {
        if (! SiteSetting::get('seo_llms_txt_enabled', false)) {
            abort(404);
        }

        $baseUrl = $this->getCanonicalBaseUrl();
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        $lines = $this->buildHeader();

        if (SiteSetting::get('seo_llms_txt_include_pages', true)) {
            $lines = array_merge($lines, $this->buildPagesSection($baseUrl, $prefix));
        }

        if (SiteSetting::get('seo_llms_txt_include_posts', true)) {
            $postLimit = (int) SiteSetting::get('seo_llms_txt_post_limit', 0);
            $lines = array_merge($lines, $this->buildPostsSections($postLimit));
        }

        $content = implode("\n", $lines);

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Get the canonical base URL.
     *
     * Uses config('app.url') as the source of truth. If the current request
     * is served over HTTPS but app.url is HTTP, upgrades to match — this
     * handles common deployments where app.url hasn't been updated for HTTPS.
     */
    protected function getCanonicalBaseUrl(): string
    {
        $baseUrl = rtrim(config('app.url'), '/');

        // Only upgrade to HTTPS if the current request is actually secure
        if (request()->isSecure() && str_starts_with($baseUrl, 'http://')) {
            $baseUrl = preg_replace('/^http:\/\//', 'https://', $baseUrl);
        }

        return $baseUrl;
    }

    /**
     * Build the header with site identity, description, and optional preamble.
     */
    protected function buildHeader(): array
    {
        $siteName = SiteSetting::get('site_name', config('app.name', 'TallCMS'));
        $siteDescription = SiteSetting::get('site_description');
        $preamble = SiteSetting::get('seo_llms_txt_preamble');

        $lines = ["# {$siteName}"];

        if ($siteDescription) {
            $lines[] = '';
            $lines[] = "> {$siteDescription}";
        }

        if ($preamble) {
            $lines[] = '';
            $lines[] = $preamble;
        }

        $lines[] = '';

        return $lines;
    }

    /**
     * Build the pages section, excluding the homepage.
     */
    protected function buildPagesSection(string $baseUrl, string $prefix): array
    {
        $pages = CmsPage::published()
            ->where('is_homepage', false)
            ->orderBy('sort_order')
            ->get()
            ->unique('slug');

        if ($pages->isEmpty()) {
            return [];
        }

        $lines = ['## Pages', ''];

        foreach ($pages as $page) {
            $pageUrl = $baseUrl.$prefix.'/'.$page->slug;
            $lines[] = "- [{$page->title}]({$pageUrl})";
        }

        $lines[] = '';

        return $lines;
    }

    /**
     * Build post sections grouped by category.
     *
     * Featured posts are listed first as cornerstone content.
     * Remaining posts are grouped by their primary category.
     * Categories with only one post are collected into an "Other" section.
     */
    protected function buildPostsSections(int $limit = 0): array
    {
        $query = CmsPost::published()
            ->with('categories')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            return [];
        }

        $lines = [];

        // Featured / cornerstone content first
        $featured = $posts->where('is_featured', true);
        if ($featured->isNotEmpty()) {
            $lines[] = '## Cornerstone Content';
            $lines[] = '';
            foreach ($featured as $post) {
                $lines[] = "- [{$post->title}]({$this->getPostUrl($post)})";
            }
            $lines[] = '';
        }

        // Group remaining posts by primary category
        $remaining = $posts->where('is_featured', false);
        $grouped = [];
        $uncategorized = [];

        foreach ($remaining as $post) {
            $category = $post->categories->first();
            if ($category) {
                $grouped[$category->name][] = $post;
            } else {
                $uncategorized[] = $post;
            }
        }

        // Sort category groups by post count (largest first)
        uasort($grouped, fn ($a, $b) => count($b) <=> count($a));

        // Render each category group (min 2 posts to get its own section)
        $other = $uncategorized;
        foreach ($grouped as $categoryName => $categoryPosts) {
            if (count($categoryPosts) < 2) {
                $other = array_merge($other, $categoryPosts);

                continue;
            }

            $lines[] = "## {$categoryName}";
            $lines[] = '';
            foreach ($categoryPosts as $post) {
                $lines[] = "- [{$post->title}]({$this->getPostUrl($post)})";
            }
            $lines[] = '';
        }

        if (! empty($other)) {
            $lines[] = '## Other';
            $lines[] = '';
            foreach ($other as $post) {
                $lines[] = "- [{$post->title}]({$this->getPostUrl($post)})";
            }
            $lines[] = '';
        }

        return $lines;
    }

    /**
     * Get the canonical URL for a post, matching the base URL scheme.
     */
    protected function getPostUrl(CmsPost $post): string
    {
        $url = SeoService::getPostUrl($post);

        // Match the scheme used by getCanonicalBaseUrl()
        if (request()->isSecure() && str_starts_with($url, 'http://')) {
            $url = preg_replace('/^http:\/\//', 'https://', $url);
        }

        return $url;
    }
}
