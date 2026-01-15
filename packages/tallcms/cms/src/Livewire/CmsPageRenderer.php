<?php

declare(strict_types=1);

namespace TallCms\Cms\Livewire;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Facades\View;
use Livewire\Component;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\CustomBlockDiscoveryService;
use TallCms\Cms\Services\MergeTagService;

class CmsPageRenderer extends Component
{
    public CmsPage $page;

    public ?CmsPost $post = null;

    public string $renderedContent;

    public string $parentSlug = '';

    public function mount(string $slug = '/')
    {
        // Handle root URL - find homepage or show welcome
        if ($slug === '/') {
            $homepage = CmsPage::where('is_homepage', true)
                ->published()
                ->first();

            if (! $homepage) {
                return $this->showWelcomePage();
            }

            $this->page = $homepage;
            $this->renderPageContent();

            return;
        }

        $cleanSlug = ltrim($slug, '/');

        // Handle /page/slug format (legacy support)
        if (str_starts_with($cleanSlug, 'page/')) {
            $cleanSlug = str_replace('page/', '', $cleanSlug);
        }

        // Check for nested slug (potential post URL like "blog/my-post")
        if (str_contains($cleanSlug, '/')) {
            $resolved = $this->resolveNestedSlug($cleanSlug);

            if ($resolved) {
                return; // Post or nested page was found and rendered
            }
        }

        // Try to find page by exact slug
        $page = CmsPage::withSlug($cleanSlug)
            ->published()
            ->first();

        if ($page) {
            $this->page = $page;
            $this->renderPageContent();

            return;
        }

        // Page not found - check if this is a post from a homepage with PostsBlock
        $homepage = CmsPage::where('is_homepage', true)
            ->published()
            ->first();

        if ($homepage && $this->pageHasPostsBlock($homepage)) {
            $post = CmsPost::withSlug($cleanSlug)
                ->with(['categories', 'author'])
                ->first();

            if ($post) {
                $canView = $post->isPublished() ||
                    (auth()->check() && request()->has('preview'));

                if ($canView) {
                    $this->page = $homepage;
                    $this->post = $post;
                    $this->parentSlug = '';
                    $this->renderedContent = 'POST_DETAIL';

                    return;
                }
            }
        }

        // Nothing found - 404
        abort(404);
    }

    /**
     * Resolve nested slugs to either a post within a page or a nested page
     */
    protected function resolveNestedSlug(string $slug): bool
    {
        // Split into parent and child segments
        $segments = explode('/', $slug);
        $childSlug = array_pop($segments);
        $parentSlug = implode('/', $segments);

        // Try to find parent page
        $parentPage = CmsPage::withSlug($parentSlug)
            ->published()
            ->first();

        if (! $parentPage) {
            return false;
        }

        // Check if parent page has a PostsBlock
        if ($this->pageHasPostsBlock($parentPage)) {
            // Try to find the post
            $post = CmsPost::withSlug($childSlug)
                ->with(['categories', 'author'])
                ->first();

            if ($post) {
                // Check publish status (allow drafts for authenticated users in preview)
                $canView = $post->isPublished() ||
                    (auth()->check() && request()->has('preview'));

                if ($canView) {
                    $this->page = $parentPage;
                    $this->post = $post;
                    $this->parentSlug = $parentSlug;
                    $this->renderedContent = 'POST_DETAIL';

                    return true;
                }
            }
        }

        // Not a post - try to find a page with the full nested slug
        $nestedPage = CmsPage::withSlug($slug)
            ->published()
            ->first();

        if ($nestedPage) {
            $this->page = $nestedPage;
            $this->renderPageContent();

            return true;
        }

        return false;
    }

    /**
     * Check if a page contains a PostsBlock
     * No caching - block detection needs to be accurate for routing
     */
    protected function pageHasPostsBlock(CmsPage $page): bool
    {
        if (empty($page->content)) {
            return false;
        }

        $content = $page->content;

        // Content is stored as JSON string (not cast to array)
        if (is_string($content)) {
            // Check for JSON format with customBlock type=posts
            if (str_contains($content, '"id":"posts"') || str_contains($content, "'id':'posts'")) {
                return true;
            }

            // Also check HTML format (legacy or rendered content)
            if (str_contains($content, 'data-id="posts"') || str_contains($content, "data-id='posts'")) {
                return true;
            }

            // Try to decode and check structured content
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $this->searchForPostsBlock($decoded);
            }
        }

        // Handle array format (shouldn't happen but be safe)
        if (is_array($content)) {
            return $this->searchForPostsBlock($content);
        }

        return false;
    }

    /**
     * Recursively search for posts block in content structure
     */
    protected function searchForPostsBlock(array $content): bool
    {
        foreach ($content as $key => $value) {
            // Direct check for block type
            if ($key === 'type' && $value === 'customBlock') {
                continue; // Check id in same array
            }
            if ($key === 'id' && $value === 'posts') {
                return true;
            }

            // Check nested arrays
            if (is_array($value)) {
                if ($this->searchForPostsBlock($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function showWelcomePage(): void
    {
        $this->page = new CmsPage([
            'title' => 'Welcome to TallCMS',
            'slug' => '/',
            'content' => '',
            'status' => 'published',
            'meta_title' => 'Welcome to TallCMS',
            'meta_description' => 'Get started with your new TallCMS installation',
        ]);

        $this->renderedContent = 'WELCOME_PAGE';
    }

    protected function renderPageContent(): void
    {
        // Share page slug with all views so blocks can generate correct URLs
        // For homepage, slug is empty string; for other pages, use the full slug
        $pageSlug = $this->page->slug === '/' ? '' : $this->page->slug;
        View::share('cmsPageSlug', $pageSlug);

        $renderedContent = RichContentRenderer::make($this->page->content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toUnsafeHtml();

        $this->renderedContent = MergeTagService::replaceTags($renderedContent, $this->page);
    }

    public function render()
    {
        // Determine metadata based on whether we're showing a post or page
        if ($this->post) {
            $title = $this->post->meta_title ?: $this->post->title;
            $description = $this->post->meta_description ?: $this->post->excerpt;
            $featuredImage = $this->post->featured_image;
        } else {
            $title = $this->page->meta_title ?: $this->page->title;
            $description = $this->page->meta_description;
            $featuredImage = $this->page->featured_image;
        }

        return view('tallcms::livewire.page')
            ->layout('tallcms::layouts.app', [
                'title' => $title,
                'description' => $description,
                'featuredImage' => $featuredImage,
            ]);
    }
}
