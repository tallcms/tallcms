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
    
    public function mount(string $slug)
    {
        $this->page = CmsPage::withSlug($slug)
            ->published()
            ->firstOrFail();
            
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
