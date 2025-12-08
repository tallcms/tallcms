<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Services\MergeTagService;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = CmsPage::withSlug($slug)
            ->published()
            ->with(['categories', 'parent', 'children'])
            ->firstOrFail();
            
        // Render rich content with custom blocks
        $renderedContent = RichContentRenderer::make()
            ->content($page->content)
            ->render();
            
        // Process merge tags in the rendered content
        $renderedContent = MergeTagService::replaceTags($renderedContent, $page);
            
        return view('cms.page', [
            'page' => $page,
            'content' => $renderedContent,
        ]);
    }
    
    public function index(): View
    {
        $pages = CmsPage::published()
            ->whereNull('parent_id')
            ->with('categories')
            ->orderBy('sort_order')
            ->get();
            
        return view('cms.index', [
            'pages' => $pages,
        ]);
    }
}
