<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\SeoService;

class CategoryArchiveController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $category = CmsCategory::where('slug', $slug)->firstOrFail();

        $perPage = config('tallcms.archive.per_page', 12);

        $posts = CmsPost::inCategory($slug)
            ->published()
            ->with(['categories', 'author'])
            ->orderBy('published_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Get SEO metadata for category archive
        $metaTags = SeoService::getCategoryMetaTags($category);

        // Build breadcrumbs
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url($prefix ?: '/')],
            ['name' => $category->name, 'url' => request()->url()],
        ];

        return view('tallcms::archive.category', [
            'category' => $category,
            'posts' => $posts,
            // Layout data
            'title' => $metaTags['title'],
            'description' => $metaTags['description'],
            'featuredImage' => $metaTags['image'],
            'seoType' => 'website',
            'seoArticle' => null,
            'seoTwitter' => null,
            'seoPost' => null,
            'seoPage' => null,
            'seoBreadcrumbs' => $breadcrumbs,
            'seoIncludeWebsite' => false,
        ]);
    }
}
