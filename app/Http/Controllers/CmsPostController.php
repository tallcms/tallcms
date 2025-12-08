<?php

namespace App\Http\Controllers;

use App\Models\CmsCategory;
use App\Models\CmsPost;
use App\Services\MergeTagService;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsPostController extends Controller
{
    public function show(string $slug): View
    {
        $post = CmsPost::withSlug($slug)
            ->published()
            ->with(['categories', 'author'])
            ->firstOrFail();
            
        // Increment view count
        $post->increment('views');
            
        // Render rich content with custom blocks
        $renderedContent = RichContentRenderer::make()
            ->content($post->content)
            ->render();
            
        // Process merge tags in the rendered content
        $renderedContent = MergeTagService::replaceTags($renderedContent, $post);
            
        return view('cms.post', [
            'post' => $post,
            'content' => $renderedContent,
        ]);
    }
    
    public function index(): View
    {
        $posts = CmsPost::published()
            ->with(['categories', 'author'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);
            
        return view('cms.posts.index', [
            'posts' => $posts,
        ]);
    }
    
    public function category(string $slug): View
    {
        $category = CmsCategory::withSlug($slug)->firstOrFail();
        
        $posts = CmsPost::published()
            ->inCategory($slug)
            ->with(['categories', 'author'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);
            
        return view('cms.posts.category', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }
    
    public function featured(): View
    {
        $posts = CmsPost::published()
            ->featured()
            ->with(['categories', 'author'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);
            
        return view('cms.posts.featured', [
            'posts' => $posts,
        ]);
    }
}