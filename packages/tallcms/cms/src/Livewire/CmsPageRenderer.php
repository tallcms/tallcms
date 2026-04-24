<?php

declare(strict_types=1);

namespace TallCms\Cms\Livewire;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Component;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\LocaleRegistry;
use TallCms\Cms\Services\MergeTagService;
use TallCms\Cms\Services\SeoService;
use TallCms\Cms\Services\TemplateRegistry;

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

        // Multi-segment slugs (anything with "/") need nested resolution. The
        // resolver branches internally on the hierarchical_urls flag: when off
        // it preserves the pre-PR behavior (post-under-page-with-PostsBlock,
        // and falling back to a literal "parent/child" page slug); when on it
        // also walks the parent_id chain to resolve hierarchical page paths.
        if (str_contains($cleanSlug, '/')) {
            if ($this->resolveNestedSlug($cleanSlug)) {
                return;
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
     * Resolve a multi-segment slug.
     *
     * Branches on the `tallcms.pages.hierarchical_urls` flag:
     *   - off (default): pre-PR behavior — single-level "parent_page/post_slug"
     *     where the parent has a PostsBlock, with a fallback to looking up the
     *     full "parent/child" string as a single page slug.
     *   - on: walks the parent_id chain segment by segment so child pages
     *     resolve at their full ancestor path, with the same post-under-page
     *     fallback at the leaf.
     *
     * Splitting on the flag is intentional: the legacy post-under-page case
     * was load-bearing for existing installs and must keep working when the
     * new feature is opt-out (default). See PR #59 review for context.
     */
    protected function resolveNestedSlug(string $slug): bool
    {
        if (! config('tallcms.pages.hierarchical_urls', false)) {
            return $this->resolveLegacyNestedSlug($slug);
        }

        return $this->walkHierarchicalSegments($slug);
    }

    /**
     * Pre-PR-59 nested-slug resolution, preserved for the flag-off path.
     *
     * 1. Treat /parent_page/post_slug as a post under a page that has a
     *    PostsBlock (the most common existing usage).
     * 2. Fall back to looking up the full "parent/child" string as a single
     *    page slug — covers installs that historically stored slashes in
     *    the slug column directly.
     */
    protected function resolveLegacyNestedSlug(string $slug): bool
    {
        $segments = explode('/', $slug);
        $childSlug = array_pop($segments);
        $parentSlug = implode('/', $segments);

        $parentPage = tallcms_i18n_enabled()
            ? CmsPage::withLocalizedSlug($parentSlug)->published()->first()
            : CmsPage::withSlug($parentSlug)->published()->first();

        if ($parentPage && $this->pageHasPostsBlock($parentPage)) {
            $post = tallcms_i18n_enabled()
                ? CmsPost::withLocalizedSlug($childSlug)->with(['categories', 'author'])->first()
                : CmsPost::withSlug($childSlug)->with(['categories', 'author'])->first();

            if ($post) {
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

        // Fallback: page whose stored slug literally contains "/".
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
     * Walk multi-segment paths via the parent_id chain — the hierarchical_urls
     * code path.
     *
     * Each segment is looked up as a published page scoped to the parent found
     * in the previous step. Every intermediate segment must be published — a
     * draft or pending parent makes its entire subtree unreachable via the
     * hierarchical URL. This is intentional: an unpublished parent acts as an
     * invisible node and its children are inaccessible until the parent is
     * also published.
     */
    protected function walkHierarchicalSegments(string $slug): bool
    {
        $segments = explode('/', $slug);
        $parentId = null;

        foreach ($segments as $i => $segment) {
            $isLast = ($i === count($segments) - 1);

            $query = tallcms_i18n_enabled()
                ? CmsPage::withLocalizedSlug($segment)
                : CmsPage::withSlug($segment);

            // published() is intentionally required for every segment — see
            // method docblock above for the rationale.
            $query->published();

            if ($parentId !== null) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id');
            }

            $currentPage = $query->first();

            if (! $currentPage) {
                // Segment didn't match a page — if this is the last segment and we found
                // a valid parent page with a PostsBlock, treat it as a post URL.
                if ($isLast && $parentId !== null) {
                    $parentPage = CmsPage::find($parentId);

                    if ($parentPage && $this->pageHasPostsBlock($parentPage)) {
                        $post = tallcms_i18n_enabled()
                            ? CmsPost::withLocalizedSlug($segment)->with(['categories', 'author'])->first()
                            : CmsPost::withSlug($segment)->with(['categories', 'author'])->first();

                        if ($post) {
                            $canView = $post->isPublished() ||
                                (auth()->check() && request()->has('preview'));

                            if ($canView) {
                                $parentSlug = implode('/', array_slice($segments, 0, $i));
                                $this->page = $parentPage;
                                $this->post = $post;
                                $this->parentSlug = $parentSlug;
                                $this->postsBlockConfig = $this->getPostsBlockConfig($parentPage);
                                $this->renderedContent = 'POST_DETAIL';

                                return true;
                            }
                        }
                    }
                }

                return false;
            }

            if ($isLast) {
                $this->page = $currentPage;
                $this->renderPageContent();

                return true;
            }

            // Intermediate segment matched — continue deeper with this page as the new parent
            $parentId = $currentPage->id;
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
     * Delegates to CmsPage model which handles both JSON and HTML formats.
     */
    protected function getPostsBlockConfig(CmsPage $page): array
    {
        return $page->getPostsBlockConfig();
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
        // Share page slug with all views so blocks can generate correct URLs.
        // For homepage, slug is empty string; for other pages, use the full
        // hierarchical path so blocks (posts, links) build /parent/child URLs
        // when tallcms.pages.hierarchical_urls is on. Falls back to the leaf
        // slug when off — getFullSlug() handles both modes internally.
        $pageSlug = $this->page->slug === '/' ? '' : $this->page->getFullSlug();
        View::share('cmsPageSlug', $pageSlug);

        // Share page content width with blocks so they can inherit it
        View::share('cmsPageContentWidth', $this->page->content_width ?? 'standard');

        $renderedContent = $this->page->renderRichContentUnsafe('content');

        // Add heading IDs for TOC support
        $renderedContent = $this->addHeadingIds($renderedContent);

        $this->renderedContent = MergeTagService::replaceTags($renderedContent, $this->page);
    }

    /**
     * Render content for a single page section (SPA mode)
     */
    protected function renderSinglePageContent(CmsPage $page): string
    {
        // Temporarily set cmsPageSlug for this section (for posts block URLs).
        // getFullSlug() honors tallcms.pages.hierarchical_urls so SPA-mode
        // section blocks build URLs consistent with non-SPA rendering.
        $previousSlug = View::shared('cmsPageSlug');
        View::share('cmsPageSlug', $page->getFullSlug());

        // Temporarily set content width for this page's blocks
        $previousWidth = View::shared('cmsPageContentWidth');
        View::share('cmsPageContentWidth', $page->content_width ?? 'standard');

        $rendered = $page->renderRichContentUnsafe('content');

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

            $showBreadcrumbs = $this->page->shouldShowBreadcrumbs();
            if ($showBreadcrumbs) {
                $breadcrumbItems = $seoBreadcrumbs;
            } else {
                $seoBreadcrumbs = null;
                $breadcrumbItems = [];
            }
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
