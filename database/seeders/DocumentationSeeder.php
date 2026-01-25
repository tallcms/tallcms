<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\User;
use App\Services\MarkdownToHtml;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;

class DocumentationSeeder extends Seeder
{
    protected MarkdownToHtml $converter;

    protected int $authorId;

    /**
     * Category mapping for documentation files
     */
    protected array $categoryMap = [
        'getting-started' => [
            'name' => 'Getting Started',
            'description' => 'Installation and basic setup guides',
            'files' => ['INSTALLATION.md', 'CMS_RICH_EDITOR.md'],
        ],
        'content-management' => [
            'name' => 'Content Management',
            'description' => 'Managing pages, posts, menus, and media',
            'files' => ['PAGE_SETTINGS.md', 'MENUS.md', 'SEO.md', 'PUBLISHING_WORKFLOW.md'],
        ],
        'customization' => [
            'name' => 'Customization',
            'description' => 'Themes, blocks, and styling',
            'files' => ['BLOCK_DEVELOPMENT.md', 'CUSTOM_BLOCK_STYLING.md', 'THEME_DEVELOPMENT.md'],
        ],
        'advanced' => [
            'name' => 'Advanced',
            'description' => 'Architecture, plugins, and internationalization',
            'files' => ['PLUGIN_DEVELOPMENT.md', 'SITE_SETTINGS.md', 'INTERNATIONALIZATION.md', 'DEVELOPER_ARCHITECTURE.md'],
        ],
    ];

    /**
     * Markdown file → URL slug mapping
     */
    protected array $slugMap = [
        'INSTALLATION.md' => 'installation',
        'BLOCK_DEVELOPMENT.md' => 'block-development',
        'CMS_RICH_EDITOR.md' => 'rich-editor',
        'PAGE_SETTINGS.md' => 'page-settings',
        'MENUS.md' => 'menus',
        'THEME_DEVELOPMENT.md' => 'theme-development',
        'PLUGIN_DEVELOPMENT.md' => 'plugin-development',
        'SITE_SETTINGS.md' => 'site-settings',
        'SEO.md' => 'seo',
        'PUBLISHING_WORKFLOW.md' => 'publishing-workflow',
        'INTERNATIONALIZATION.md' => 'internationalization',
        'CUSTOM_BLOCK_STYLING.md' => 'block-styling',
        'DEVELOPER_ARCHITECTURE.md' => 'architecture',
    ];

    public function run(): void
    {
        $author = User::first();
        if (! $author) {
            $this->command->error('No users found. Please create a user first: php artisan make:user');

            return;
        }
        $this->authorId = $author->id;

        $this->converter = new MarkdownToHtml($this->slugMap);

        // Clean up existing documentation
        $this->cleanupExistingDocs();

        // Create categories and posts
        foreach ($this->categoryMap as $categorySlug => $categoryData) {
            $category = $this->createCategory($categorySlug, $categoryData);

            foreach ($categoryData['files'] as $filename) {
                $this->createDocPost($filename, $category);
            }
        }

        $this->command->info('Documentation seeded successfully!');
    }

    /**
     * Remove existing documentation posts and categories
     */
    protected function cleanupExistingDocs(): void
    {
        // Delete posts in documentation categories
        $categorySlugs = array_keys($this->categoryMap);

        foreach ($categorySlugs as $slug) {
            $category = CmsCategory::withSlug($slug)->first();
            if ($category) {
                // Detach and delete posts
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
     * Create a documentation category
     */
    protected function createCategory(string $slug, array $data): CmsCategory
    {
        $category = CmsCategory::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'],
        ]);

        $this->command->info("Created category: {$data['name']}");

        return $category;
    }

    /**
     * Create a documentation post from a markdown file
     */
    protected function createDocPost(string $filename, CmsCategory $category): void
    {
        $path = base_path("docs/{$filename}");

        if (! File::exists($path)) {
            $this->command->warn("Skipping missing file: {$filename}");

            return;
        }

        $markdown = File::get($path);
        $slug = $this->slugMap[$filename] ?? $this->filenameToSlug($filename);

        // Extract title and generate HTML
        $title = $this->converter->extractTitle($markdown, $filename);
        $html = $this->converter->convert($markdown);
        $excerpt = $this->converter->generateMetaDescription($markdown);

        // Create post with raw HTML content
        $post = CmsPost::create([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'meta_title' => "{$title} - TallCMS Documentation",
            'meta_description' => $excerpt,
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
            'author_id' => $this->authorId,
        ]);

        // Store HTML content directly
        $post->setTranslation('content', config('app.locale', 'en'), $html);
        $post->save();

        // Attach category
        $post->categories()->attach($category->id);

        $this->command->info("Created post: {$title}");
    }

    /**
     * Convert filename to slug
     */
    protected function filenameToSlug(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        return strtolower(str_replace('_', '-', $name));
    }
}
