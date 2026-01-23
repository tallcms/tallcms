<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use TallCms\Cms\Models\SiteSetting;

class RobotsController extends Controller
{
    /**
     * Render robots.txt from site settings.
     */
    public function index(): Response
    {
        // Get custom robots.txt content or use default
        $content = SiteSetting::get('seo_robots_txt', $this->getDefaultContent());

        // Optionally append sitemap URL
        $appendSitemap = SiteSetting::get('seo_robots_append_sitemap', true);
        if ($appendSitemap && SiteSetting::get('seo_sitemap_enabled', true)) {
            $prefix = config('tallcms.plugin_mode.routes_prefix', '');
            $prefix = $prefix ? "/{$prefix}" : '';
            $sitemapUrl = rtrim(config('app.url'), '/').$prefix.'/sitemap.xml';

            // Only append if not already present
            if (! str_contains($content, 'Sitemap:')) {
                $content = rtrim($content)."\n\nSitemap: {$sitemapUrl}";
            }
        }

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * Get default robots.txt content.
     */
    protected function getDefaultContent(): string
    {
        return <<<'ROBOTS'
User-agent: *
Allow: /
ROBOTS;
    }
}
