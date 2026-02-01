<?php

declare(strict_types=1);

namespace TallCms\Cms\Livewire;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Facades\View;
use Livewire\Component;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\CustomBlockDiscoveryService;
use TallCms\Cms\Services\LocaleRegistry;
use TallCms\Cms\Services\MergeTagService;
use TallCms\Cms\Services\SeoService;
use TallCms\Cms\Services\TemplateRegistry;
use Illuminate\Support\Str;

class CmsPageRenderer extends Component
{
    public CmsPage $page;

    public ?CmsPost $post = null;

    public string $renderedContent;

    public string $parentSlug = '';

    public array $allPages = [];

    public array $postsBlockConfig = [];

    public function mount(string $slug = '/', ?string $locale = null)
    {
        // Set locale if i18n enabled and locale provided
        if (tallcms_i18n_enabled() && $locale) {
            $registry = app(LocaleRegistry::class);
            if ($registry->isValidLocale($locale)) {
                app()->setLocale($locale);
            }
        }

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

            // SPA Mode: Load all pages as sections on homepage
            $siteType = SiteSetting::get('site_type', 'multi-page');
            if ($siteType === 'single-page') {
                $this->allPages = $this->loadSpaPages();
            }

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

        // Try to find page by slug (use localized lookup when i18n enabled)
        $page = tallcms_i18n_enabled()
            ? CmsPage::withLocalizedSlug($cleanSlug)->published()->first()
            : CmsPage::withSlug($cleanSlug)->published()->first();

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
            // Use localized lookup for posts when i18n enabled
            $post = tallcms_i18n_enabled()
                ? CmsPost::withLocalizedSlug($cleanSlug)->with(['categories', 'author'])->first()
                : CmsPost::withSlug($cleanSlug)->with(['categories', 'author'])->first();

            if ($post) {
                $canView = $post->isPublished() ||
                    (auth()->check() && request()->has('preview'));

                if ($canView) {
                    $this->page = $homepage;
                    $this->post = $post;
                    $this->parentSlug = '';
                    $this->postsBlockConfig = $this->getPostsBlockConfig($homepage);
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

        // Try to find parent page (use localized lookup when i18n enabled)
        $parentPage = tallcms_i18n_enabled()
            ? CmsPage::withLocalizedSlug($parentSlug)->published()->first()
            : CmsPage::withSlug($parentSlug)->published()->first();

        if (! $parentPage) {
            return false;
        }

        // Check if parent page has a PostsBlock
        if ($this->pageHasPostsBlock($parentPage)) {
            // Try to find the post (use localized lookup when i18n enabled)
            $post = tallcms_i18n_enabled()
                ? CmsPost::withLocalizedSlug($childSlug)->with(['categories', 'author'])->first()
                : CmsPost::withSlug($childSlug)->with(['categories', 'author'])->first();

            if ($post) {
                // Check publish status (allow drafts for authenticated users in preview)
                $canView = $post->isPublished() ||
                    (auth()->check() && request()->has('preview'));

                if ($canView) {
                    $this->page = $parentPage;
                    $this->post = $post;
                    $this->parentSlug = $parentSlug;
                    $this->postsBlockConfig = $this->getPostsBlockConfig($parentPage);
                    $this->renderedContent = 'POST_DETAIL';

                    return true;
                }
            }
        }

        // Not a post - try to find a page with the full nested slug
        $nestedPage = tallcms_i18n_enabled()
            ? CmsPage::withLocalizedSlug($slug)->published()->first()
            : CmsPage::withSlug($slug)->published()->first();

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

    /**
     * Extract PostsBlock config from page content.
     * Used to pass display settings to the post detail view.
     * Handles both Tiptap JSON format and HTML format with data attributes.
     */
    protected function getPostsBlockConfig(CmsPage $page): array
    {
        if (empty($page->content)) {
            return [];
        }

        $content = $page->content;

        // Handle string content (could be JSON or HTML)
        if (is_string($content)) {
            // Try JSON format first (Tiptap)
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $this->extractPostsBlockConfig($decoded);
            }

            // Fall back to HTML format with data attributes
            return $this->extractPostsBlockConfigFromHtml($content);
        }

        // Already an array (decoded JSON)
        if (is_array($content)) {
            return $this->extractPostsBlockConfig($content);
        }

        return [];
    }

    /**
     * Recursively extract posts block config from Tiptap JSON content structure.
     */
    protected function extractPostsBlockConfig(array $content): array
    {
        // Check if this is a customBlock with id=posts
        if (isset($content['type']) && $content['type'] === 'customBlock' &&
            isset($content['attrs']['id']) && $content['attrs']['id'] === 'posts') {
            return $content['attrs']['config'] ?? [];
        }

        // Search nested arrays
        foreach ($content as $value) {
            if (is_array($value)) {
                $config = $this->extractPostsBlockConfig($value);
                if (! empty($config)) {
                    return $config;
                }
            }
        }

        return [];
    }

    /**
     * Extract posts block config from HTML content with data attributes.
     */
    protected function extractPostsBlockConfigFromHtml(string $html): array
    {
        // Look for data-type="customBlock" data-id="posts" data-config="..."
        if (preg_match('/data-type=["\']customBlock["\'][^>]*data-id=["\']posts["\'][^>]*data-config=["\']([^"\']+)["\']/', $html, $matches) ||
            preg_match('/data-id=["\']posts["\'][^>]*data-type=["\']customBlock["\'][^>]*data-config=["\']([^"\']+)["\']/', $html, $matches) ||
            preg_match('/data-config=["\']([^"\']+)["\'][^>]*data-type=["\']customBlock["\'][^>]*data-id=["\']posts["\']/', $html, $matches) ||
            preg_match('/data-config=["\']([^"\']+)["\'][^>]*data-id=["\']posts["\']/', $html, $matches)) {
            $configJson = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $config = json_decode($configJson, true);

            return is_array($config) ? $config : [];
        }

        return [];
    }

    /**
     * Check if the page content starts with a hero block.
     * Used to apply smart contrast for breadcrumbs.
     */
    protected function pageStartsWithHero(CmsPage $page): bool
    {
        if (empty($page->content)) {
            return false;
        }

        $content = $page->content;

        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                return false;
            }
            $content = $decoded;
        }

        // Tiptap structure: { type: 'doc', content: [ ... ] }
        if (isset($content['type']) && $content['type'] === 'doc' && isset($content['content'])) {
            foreach ($content['content'] as $block) {
                if (isset($block['type']) && $block['type'] === 'customBlock') {
                    return isset($block['attrs']['id']) && $block['attrs']['id'] === 'hero';
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

    /**
     * Add IDs to headings that don't have them (for TOC anchor links).
     * Handles headings with inline markup (e.g., <h2><span>Title</span></h2>).
     */
    protected function addHeadingIds(string $html): string
    {
        // Track all used IDs (both existing and generated) to avoid collisions
        $usedIds = [];

        return preg_replace_callback(
            '/<(h[2-4])([^>]*)>(.*?)<\/\1>/is',
            function ($matches) use (&$usedIds) {
                $tag = $matches[1];
                $attrs = $matches[2];
                $content = $matches[3];

                // Skip if already has ID, but track it
                if (preg_match('/\bid\s*=/i', $attrs)) {
                    if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
                        $usedIds[$idMatch[1]] = true;
                    }

                    return $matches[0];
                }

                // Extract text content (strip tags for slug generation)
                $text = strip_tags($content);
                $baseId = Str::slug($text);

                // Handle empty slugs
                if (empty($baseId)) {
                    $baseId = 'heading-'.substr(md5($content), 0, 8);
                }

                // Find an unused ID by checking both base and suffixed candidates
                $id = $baseId;
                $counter = 1;
                while (isset($usedIds[$id])) {
                    $counter++;
                    $id = $baseId.'-'.$counter;
                }
                $usedIds[$id] = true;

                return "<{$tag} id=\"{$id}\"{$attrs}>{$content}</{$tag}>";
            },
            $html
        );
    }

    protected function renderPageContent(): void
    {
        // Share page slug with all views so blocks can generate correct URLs
        // For homepage, slug is empty string; for other pages, use the full slug
        $pageSlug = $this->page->slug === '/' ? '' : $this->page->slug;
        View::share('cmsPageSlug', $pageSlug);

        // Share page content width with blocks so they can inherit it
        View::share('cmsPageContentWidth', $this->page->content_width ?? 'standard');

        $renderedContent = RichContentRenderer::make($this->page->content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toUnsafeHtml();

        // Add heading IDs for TOC support
        $renderedContent = $this->addHeadingIds($renderedContent);

        $this->renderedContent = MergeTagService::replaceTags($renderedContent, $this->page);
    }

    /**
     * Render content for a single page section (SPA mode)
     */
    protected function renderSinglePageContent(CmsPage $page): string
    {
        // Temporarily set cmsPageSlug for this section (for posts block URLs)
        $previousSlug = View::shared('cmsPageSlug');
        View::share('cmsPageSlug', $page->slug);

        // Temporarily set content width for this page's blocks
        $previousWidth = View::shared('cmsPageContentWidth');
        View::share('cmsPageContentWidth', $page->content_width ?? 'standard');

        $rendered = RichContentRenderer::make($page->content)
            ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
            ->toUnsafeHtml();

        // Add heading IDs for SPA mode TOC support
        $rendered = $this->addHeadingIds($rendered);

        $result = MergeTagService::replaceTags($rendered, $page);

        // Restore previous context to avoid bleeding into subsequent views
        View::share('cmsPageSlug', $previousSlug);
        View::share('cmsPageContentWidth', $previousWidth);

        return $result;
    }

    /**
     * Load pages for SPA mode with proper hierarchical ordering.
     * Parents are sorted by sort_order, children grouped directly after their parent.
     */
    protected function loadSpaPages(): array
    {
        // Get all non-homepage published pages
        $pages = CmsPage::where('is_homepage', false)
            ->published()
            ->orderBy('sort_order')
            ->get();

        // Build hierarchical order: top-level pages first, then each parent's children
        $ordered = collect();
        $processed = collect();

        // Process top-level pages (no parent) first
        $topLevel = $pages->whereNull('parent_id')->sortBy('sort_order');
        foreach ($topLevel as $parent) {
            $ordered->push($parent);
            $processed->push($parent->id);

            // Add this parent's children directly after
            $children = $pages->where('parent_id', $parent->id)->sortBy('sort_order');
            foreach ($children as $child) {
                $ordered->push($child);
                $processed->push($child->id);
            }
        }

        // Add any remaining pages (orphans or deeper nesting not yet processed)
        $remaining = $pages->whereNotIn('id', $processed->toArray());
        $ordered = $ordered->concat($remaining);

        return $ordered->map(function ($page) {
            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'anchor' => tallcms_slug_to_anchor($page->slug, $page->id),
                'title' => $page->title,
                'content_width' => $page->content_width ?? 'standard',
                'content' => $this->renderSinglePageContent($page),
            ];
        })->toArray();
    }

    public function render()
    {
        // Determine metadata and SEO context based on content type
        if ($this->post) {
            // Post/Article SEO
            $metaTags = SeoService::getPostMetaTags($this->post);
            $title = $metaTags['title'];
            $description = $metaTags['description'];
            $featuredImage = $this->post->featured_image;
            $seoType = 'article';
            $seoArticle = $metaTags['article'];
            $seoTwitter = $metaTags['twitter'];
            $seoPost = $this->post;
            $seoPage = null;
            $seoIncludeWebsite = false;

            // Build breadcrumbs for post (with absolute localized URLs for JSON-LD)
            $seoBreadcrumbs = [
                ['name' => __('Home'), 'url' => url(tallcms_localized_url('/'))],
            ];

            // Add parent page breadcrumb if post is under a page
            if ($this->parentSlug) {
                $seoBreadcrumbs[] = [
                    'name' => $this->page->title,
                    'url' => url(tallcms_localized_url($this->parentSlug)),
                ];
            }

            // Add post title as final breadcrumb (canonical URL)
            $postUrl = $this->parentSlug
                ? $this->parentSlug.'/'.$this->post->slug
                : $this->post->slug;
            $seoBreadcrumbs[] = [
                'name' => $this->post->title,
                'url' => url(tallcms_localized_url($postUrl)),
            ];

            $showBreadcrumbs = true; // Posts always show breadcrumbs
            $breadcrumbItems = $seoBreadcrumbs;
        } else {
            // Page SEO
            $metaTags = SeoService::getMetaTags($this->page);
            $title = $metaTags['title'];
            $description = $metaTags['description'];
            $featuredImage = $this->page->featured_image;
            $seoType = 'website';
            $seoArticle = null;
            $seoTwitter = null;
            $seoPost = null;
            $seoPage = $this->page;
            $seoIncludeWebsite = $this->page->is_homepage;

            // Build breadcrumbs for page
            if ($this->page->is_homepage) {
                $seoBreadcrumbs = null;
                $showBreadcrumbs = false;
                $breadcrumbItems = [];
            } else {
                $showBreadcrumbs = $this->page->shouldShowBreadcrumbs();
                if ($showBreadcrumbs) {
                    $breadcrumbItems = $this->page->getBreadcrumbTrail();
                    $seoBreadcrumbs = $breadcrumbItems; // Also used for JSON-LD
                } else {
                    // Toggle OFF suppresses both visual AND JSON-LD breadcrumbs
                    $seoBreadcrumbs = null;
                    $breadcrumbItems = [];
                }
            }
        }

        // Detect if breadcrumbs will appear over a hero block
        $breadcrumbsOverHero = $showBreadcrumbs && $this->pageStartsWithHero($this->page);

        // Resolve template and widgets for pages (not posts)
        $templateRegistry = app(TemplateRegistry::class);
        $template = $this->page->template ?: 'default';
        $templateView = $templateRegistry->resolveTemplateView($template);
        $templateConfig = $templateRegistry->getTemplateConfig($template);

        // Sidebar widgets: empty array OR null = use template defaults
        $sidebarWidgets = [];
        if ($templateConfig['has_sidebar'] ?? false) {
            $pageWidgets = $this->page->sidebar_widgets;
            $sidebarWidgets = (! empty($pageWidgets))
                ? $pageWidgets
                : ($templateConfig['default_widgets'] ?? []);
        }

        // Minimal chrome flag for landing pages
        $minimalChrome = $templateConfig['minimal_chrome'] ?? false;

        return view('tallcms::livewire.page', [
            'templateView' => $templateView,
            'templateConfig' => $templateConfig,
            'sidebarWidgets' => $sidebarWidgets,
        ])->layout('tallcms::layouts.app', [
            'title' => $title,
            'description' => $description,
            'featuredImage' => $featuredImage,
            'seoType' => $seoType,
            'seoArticle' => $seoArticle,
            'seoTwitter' => $seoTwitter,
            'seoPost' => $seoPost,
            'seoPage' => $seoPage,
            'seoBreadcrumbs' => $seoBreadcrumbs,
            'seoIncludeWebsite' => $seoIncludeWebsite,
            'showBreadcrumbs' => $showBreadcrumbs,
            'breadcrumbItems' => $breadcrumbItems,
            'breadcrumbsOverHero' => $breadcrumbsOverHero,
            'minimalChrome' => $minimalChrome,
        ]);
    }
}
