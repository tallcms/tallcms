<?php

namespace App\Livewire;

use App\Models\CmsPage;
use App\Services\CustomBlockDiscoveryService;
use App\Services\MergeTagService;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Livewire\Component;

class CmsPageRenderer extends Component
{
    public CmsPage $page;
    public string $renderedContent;
    
    public function mount(string $slug = '/')
    {
        // Handle root URL - find homepage
        if ($slug === '/') {
            $this->page = CmsPage::where('is_homepage', true)
                ->published()
                ->firstOrFail();
        } else {
            // Try to find page by slug, also handle /page/slug format
            $cleanSlug = ltrim($slug, '/');
            if (str_starts_with($cleanSlug, 'page/')) {
                $cleanSlug = str_replace('page/', '', $cleanSlug);
            }
            
            $this->page = CmsPage::withSlug($cleanSlug)
                ->published()
                ->firstOrFail();
        }
            
        $this->renderPageContent();
    }
    
    protected function renderPageContent(): void
    {
        // Render rich content with auto-discovered custom blocks
        $renderedContent = RichContentRenderer::make($this->page->content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toHtml();
            
        // Process merge tags in the rendered content
        $this->renderedContent = MergeTagService::replaceTags($renderedContent, $this->page);
    }
    
    public function render()
    {
        return view('livewire.page')
            ->layout('layouts.app', [
                'title' => $this->page->meta_title ?: $this->page->title,
                'description' => $this->page->meta_description,
                'featuredImage' => $this->page->featured_image,
            ]);
    }
}
