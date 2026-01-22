<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\SiteSetting;

class SitemapService
{
    /**
     * Number of URLs per sitemap chunk.
     */
    protected const CHUNK_SIZE = 1000;

    /**
     * Cache TTL in seconds (1 hour default).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get the URL prefix for routes.
     */
    protected static function getPrefix(): string
    {
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');

        return $prefix ? "/{$prefix}" : '';
    }

    /**
     * Check if sitemap generation is enabled.
     */
    public static function isEnabled(): bool
    {
        return (bool) SiteSetting::get('seo_sitemap_enabled', true);
    }

    /**
     * Get the sitemap index (or single sitemap if small enough).
     */
    public static function getIndex(): array
    {
        return Cache::remember('sitemap:index', self::CACHE_TTL, function () {
            $sitemaps = [];
            $prefix = self::getPrefix();
            $baseUrl = rtrim(config('app.url'), '/');

            // Always include pages sitemap
            $sitemaps[] = [
                'loc' => $baseUrl.$prefix.'/sitemap-pages.xml',
                'lastmod' => CmsPage::published()->max('updated_at'),
            ];

            // Posts - chunked if needed
            $postCount = CmsPost::published()->count();
            $postChunks = max(1, ceil($postCount / self::CHUNK_SIZE));

            for ($i = 1; $i <= $postChunks; $i++) {
                $sitemaps[] = [
                    'loc' => $baseUrl.$prefix."/sitemap-posts-{$i}.xml",
                    'lastmod' => CmsPost::published()->max('updated_at'),
                ];
            }

            // Categories (only those with published posts)
            $categoriesWithPosts = CmsCategory::whereHas('posts', function ($q) {
                $q->published();
            })->count();

            if ($categoriesWithPosts > 0) {
                $sitemaps[] = [
                    'loc' => $baseUrl.$prefix.'/sitemap-categories.xml',
                    'lastmod' => CmsCategory::whereHas('posts', function ($q) {
                        $q->published();
                    })->max('updated_at'),
                ];
            }

            // Authors (only those with published posts)
            $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);
            $userInstance = new $userModel;
            $userTable = $userInstance->getTable();
            $userKey = $userInstance->getKeyName();
            $userConnection = $userInstance->getConnectionName();
            $postsTable = (new CmsPost)->getTable();

            // Use the user model's connection (may differ from default)
            $db = $userConnection ? DB::connection($userConnection) : DB::connection();

            $authorsWithPosts = $db->table($userTable)
                ->whereExists(function ($q) use ($postsTable, $userTable, $userKey) {
                    $q->select(DB::raw(1))
                        ->from($postsTable)
                        ->whereColumn('author_id', "{$userTable}.{$userKey}")
                        ->where('status', 'published')
                        // Match published() scope: NULL or past published_at
                        ->where(function ($q2) {
                            $q2->whereNull('published_at')
                                ->orWhere('published_at', '<=', now());
                        });
                })
                ->count();

            if ($authorsWithPosts > 0) {
                $sitemaps[] = [
                    'loc' => $baseUrl.$prefix.'/sitemap-authors.xml',
                    'lastmod' => now(),
                ];
            }

            return $sitemaps;
        });
    }

    /**
     * Get URLs for all published pages.
     */
    public static function getPages(): Collection
    {
        return Cache::remember('sitemap:pages', self::CACHE_TTL, function () {
            $prefix = self::getPrefix();
            $baseUrl = rtrim(config('app.url'), '/');

            return CmsPage::published()
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($page) use ($baseUrl, $prefix) {
                    $slug = $page->is_homepage ? '' : '/'.$page->slug;

                    return [
                        'loc' => $baseUrl.$prefix.$slug,
                        'lastmod' => $page->updated_at?->toIso8601String(),
                        'changefreq' => $page->is_homepage ? 'daily' : 'weekly',
                        'priority' => $page->is_homepage ? '1.0' : '0.8',
                    ];
                });
        });
    }

    /**
     * Get URLs for published posts (chunked).
     */
    public static function getPosts(int $page = 1): Collection
    {
        $cacheKey = "sitemap:posts:{$page}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($page) {
            $offset = ($page - 1) * self::CHUNK_SIZE;

            return CmsPost::published()
                ->orderBy('published_at', 'desc')
                ->skip($offset)
                ->take(self::CHUNK_SIZE)
                ->get()
                ->map(function ($post) {
                    return [
                        'loc' => SeoService::getPostUrl($post),
                        'lastmod' => $post->updated_at?->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => $post->is_featured ? '0.9' : '0.7',
                    ];
                });
        });
    }

    /**
     * Get URLs for categories with published posts.
     */
    public static function getCategories(): Collection
    {
        return Cache::remember('sitemap:categories', self::CACHE_TTL, function () {
            $prefix = self::getPrefix();
            $baseUrl = rtrim(config('app.url'), '/');

            return CmsCategory::whereHas('posts', function ($q) {
                $q->published();
            })
                ->orderBy('name')
                ->get()
                ->map(function ($category) use ($baseUrl, $prefix) {
                    return [
                        'loc' => $baseUrl.$prefix.'/category/'.$category->slug,
                        'lastmod' => $category->updated_at?->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ];
                });
        });
    }

    /**
     * Get URLs for authors with published posts.
     */
    public static function getAuthors(): Collection
    {
        return Cache::remember('sitemap:authors', self::CACHE_TTL, function () {
            $prefix = self::getPrefix();
            $baseUrl = rtrim(config('app.url'), '/');
            $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);

            return $userModel::whereHas('posts', function ($q) {
                $q->published();
            })
                ->get()
                ->map(function ($author) use ($baseUrl, $prefix) {
                    $slug = $author->slug ?? 'user-'.$author->getKey();

                    return [
                        'loc' => $baseUrl.$prefix.'/author/'.$slug,
                        'lastmod' => $author->updated_at?->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.5',
                    ];
                });
        });
    }

    /**
     * Clear all sitemap caches.
     */
    public static function clearCache(): void
    {
        Cache::forget('sitemap:index');
        Cache::forget('sitemap:pages');
        Cache::forget('sitemap:categories');
        Cache::forget('sitemap:authors');

        // Clear chunked post caches
        $postCount = CmsPost::published()->count();
        $postChunks = max(1, ceil($postCount / self::CHUNK_SIZE));

        for ($i = 1; $i <= $postChunks; $i++) {
            Cache::forget("sitemap:posts:{$i}");
        }
    }
}
