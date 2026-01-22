<?php

declare(strict_types=1);

namespace TallCms\Cms\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\SeoService;

class AuthorArchiveController extends Controller
{
    public function show(Request $request, string $authorSlug): View
    {
        // Get the configured user model
        $userModel = config('tallcms.plugin_mode.user_model', \App\Models\User::class);
        $author = null;

        // Try to find author by slug (if column exists)
        $userTable = (new $userModel)->getTable();
        if (Schema::hasColumn($userTable, 'slug')) {
            $author = $userModel::where('slug', $authorSlug)->first();
        }

        // Fallback: try user-{id} pattern
        if (! $author && Str::startsWith($authorSlug, 'user-')) {
            $id = Str::after($authorSlug, 'user-');
            if (is_numeric($id)) {
                $author = $userModel::find($id);
            }
        }

        // Final fallback: try finding by ID directly (for backwards compatibility)
        if (! $author && is_numeric($authorSlug)) {
            $author = $userModel::find($authorSlug);
        }

        // 404 if author not found
        abort_unless($author, 404);

        $perPage = config('tallcms.archive.per_page', 12);

        $posts = CmsPost::where('author_id', $author->getKey())
            ->published()
            ->with(['categories', 'author'])
            ->orderBy('published_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Get SEO metadata for author archive
        $metaTags = SeoService::getAuthorMetaTags($author);

        // Build breadcrumbs
        $prefix = config('tallcms.plugin_mode.routes_prefix', '');
        $prefix = $prefix ? "/{$prefix}" : '';

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url($prefix ?: '/')],
            ['name' => $author->name ?? 'Author', 'url' => request()->url()],
        ];

        return view('tallcms::archive.author', [
            'author' => $author,
            'posts' => $posts,
            // Layout data
            'title' => $metaTags['title'],
            'description' => $metaTags['description'],
            'featuredImage' => $metaTags['image'],
            'seoType' => 'profile',
            'seoArticle' => null,
            'seoTwitter' => null,
            'seoPost' => null,
            'seoPage' => null,
            'seoBreadcrumbs' => $breadcrumbs,
            'seoIncludeWebsite' => false,
            'seoProfile' => $metaTags['profile'] ?? null,
        ]);
    }
}
