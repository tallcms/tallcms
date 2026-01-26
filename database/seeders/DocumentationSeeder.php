<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\User;
use App\Services\FrontmatterParser;
use App\Services\MarkdownToHtml;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;

class DocumentationSeeder extends Seeder
{
    protected MarkdownToHtml $converter;

    protected FrontmatterParser $parser;

    protected int $authorId;

    /**
     * Files to exclude from seeding (by filename).
     */
    protected array $excludedFiles = ['README.md', 'STYLE_GUIDE.md'];

    /**
     * Category definitions with metadata.
     */
    protected array $categoryMap = [
        'getting-started' => [
            'name' => 'Getting Started',
            'description' => 'Quick guides to get you up and running',
            'order' => 1,
        ],
        'site-management' => [
            'name' => 'Site Management',
            'description' => 'Managing content, media, and settings',
            'order' => 2,
        ],
        'developers' => [
            'name' => 'For Developers',
            'description' => 'Themes, plugins, and extending TallCMS',
            'order' => 3,
        ],
        'reference' => [
            'name' => 'Reference',
            'description' => 'Detailed specifications and technical reference',
            'order' => 4,
        ],
    ];

    public function run(): void
    {
        $author = User::first();
        if (! $author) {
            $this->command->error('No users found. Please create a user first: php artisan make:user');

            return;
        }
        $this->authorId = $author->id;

        $this->parser = new FrontmatterParser;

        // Build slug map from all docs with frontmatter
        $slugMap = $this->buildSlugMap();
        $this->converter = new MarkdownToHtml($slugMap);

        // Clean up existing documentation
        $this->cleanupExistingDocs();

        // Create categories first
        $categories = $this->createCategories();

        // Process all documentation files
        $this->processDocFiles($categories);

        $this->command->info('Documentation seeded successfully!');
    }

    /**
     * Build a slug map from all docs with frontmatter.
     *
     * @return array<string, string>
     */
    protected function buildSlugMap(): array
    {
        $slugMap = [];
        $files = File::glob(base_path('docs/*.md'));

        foreach ($files as $path) {
            $filename = basename($path);
            if (in_array($filename, $this->excludedFiles)) {
                continue;
            }

            $raw = File::get($path);
            $parsed = $this->parser->parse($raw);

            if ($parsed['error'] || empty($parsed['frontmatter']['slug'])) {
                continue;
            }

            // Map both the filename and any legacy filenames to the canonical slug
            $slug = $parsed['frontmatter']['slug'];
            $slugMap[$filename] = $slug;

            // Also map uppercase version for backwards compatibility during transition
            $uppercaseFilename = strtoupper(pathinfo($filename, PATHINFO_FILENAME)).'.md';
            if ($uppercaseFilename !== $filename) {
                $slugMap[$uppercaseFilename] = $slug;
            }
        }

        return $slugMap;
    }

    /**
     * Remove existing documentation posts and categories.
     */
    protected function cleanupExistingDocs(): void
    {
        $categorySlugs = array_keys($this->categoryMap);

        foreach ($categorySlugs as $slug) {
            $category = CmsCategory::withSlug($slug)->first();
            if ($category) {
                $postIds = $category->posts()->pluck('tallcms_posts.id');
                if ($postIds->isNotEmpty()) {
                    CmsPost::whereIn('id', $postIds)->forceDelete();
                    $this->command->info("Deleted {$postIds->count()} posts from category: {$slug}");
                }
                $category->forceDelete();
            }
        }
    }

    /**
     * Create all documentation categories.
     *
     * @return array<string, CmsCategory>
     */
    protected function createCategories(): array
    {
        $categories = [];

        foreach ($this->categoryMap as $slug => $data) {
            $category = CmsCategory::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'],
            ]);

            $categories[$slug] = $category;
            $this->command->info("Created category: {$data['name']}");
        }

        return $categories;
    }

    /**
     * Process all documentation files with frontmatter.
     *
     * @param  array<string, CmsCategory>  $categories
     */
    protected function processDocFiles(array $categories): void
    {
        $files = File::glob(base_path('docs/*.md'));
        $docsByCategory = [];

        foreach ($files as $path) {
            $filename = basename($path);

            // Skip excluded files by name
            if (in_array($filename, $this->excludedFiles)) {
                $this->command->info("Skipping excluded file: {$filename}");

                continue;
            }

            $raw = File::get($path);
            $parsed = $this->parser->parse($raw);

            // Handle YAML parse errors
            if ($parsed['error']) {
                $this->command->warn("Skipping {$filename}: {$parsed['error']}");

                continue;
            }

            $frontmatter = $parsed['frontmatter'];
            $content = $parsed['content'];

            // Skip hidden docs
            if ($frontmatter['hidden'] ?? false) {
                $this->command->info("Skipping hidden doc: {$filename}");

                continue;
            }

            // Require frontmatter for all other docs
            if (empty($frontmatter['slug']) || empty($frontmatter['category'])) {
                $this->command->warn("Skipping {$filename}: missing required frontmatter (slug, category)");

                continue;
            }

            // Validate category exists
            $categorySlug = $frontmatter['category'];
            if (! isset($categories[$categorySlug])) {
                $this->command->warn("Skipping {$filename}: unknown category '{$categorySlug}'");

                continue;
            }

            // Group by category for ordered creation
            $docsByCategory[$categorySlug][] = [
                'filename' => $filename,
                'frontmatter' => $frontmatter,
                'content' => $content,
            ];
        }

        // Base timestamp for sequential ordering (1 day ago as starting point)
        $baseTimestamp = now()->subDay();
        $postIndex = 0;

        // Process categories in their defined order
        $sortedCategories = collect($this->categoryMap)
            ->sortBy('order')
            ->keys();

        foreach ($sortedCategories as $categorySlug) {
            if (! isset($docsByCategory[$categorySlug])) {
                continue;
            }

            $docs = $docsByCategory[$categorySlug];

            // Sort by order field within category
            usort($docs, fn ($a, $b) => ($a['frontmatter']['order'] ?? 99) <=> ($b['frontmatter']['order'] ?? 99));

            foreach ($docs as $doc) {
                // Each post gets a timestamp 1 minute after the previous
                $publishedAt = $baseTimestamp->copy()->addMinutes($postIndex);
                $this->createDocPost($doc, $categories[$categorySlug], $publishedAt);
                $postIndex++;
            }
        }
    }

    /**
     * Create a documentation post from parsed frontmatter and content.
     *
     * @param  array{filename: string, frontmatter: array<string, mixed>, content: string}  $doc
     * @param  \Carbon\Carbon  $publishedAt  Timestamp for ordering docs chronologically
     */
    protected function createDocPost(array $doc, CmsCategory $category, $publishedAt): void
    {
        $frontmatter = $doc['frontmatter'];
        $content = $doc['content'];

        $slug = $frontmatter['slug'];
        $title = $frontmatter['title'] ?? $this->converter->extractTitle($content, $doc['filename']);

        // Convert markdown to HTML
        $html = $this->converter->convert($content);
        $excerpt = $this->converter->generateMetaDescription($content);

        // Create post with sequential timestamp for proper ordering
        $post = CmsPost::create([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'meta_title' => "{$title} - TallCMS Documentation",
            'meta_description' => $excerpt,
            'status' => ContentStatus::Published->value,
            'published_at' => $publishedAt,
            'created_at' => $publishedAt,
            'updated_at' => $publishedAt,
            'author_id' => $this->authorId,
        ]);

        // Store HTML content directly
        $post->setTranslation('content', config('app.locale', 'en'), $html);
        $post->save();

        // Attach category
        $post->categories()->attach($category->id);

        $this->command->info("Created post: {$title} (slug: {$slug})");
    }
}
