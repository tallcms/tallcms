<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;
use Tests\TestCase;

class DocumentationSeederTest extends TestCase
{
    use RefreshDatabase;

    protected string $docsPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user required by the seeder
        User::factory()->create();

        $this->docsPath = base_path('docs');
    }

    public function test_seeds_docs_with_valid_frontmatter(): void
    {
        // Skip if docs directory doesn't exist or has no files
        if (! File::isDirectory($this->docsPath)) {
            $this->markTestSkipped('docs directory does not exist');
        }

        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->assertSuccessful();

        // Check categories were created (use withSlug scope for translatable slug)
        $this->assertTrue(CmsCategory::withSlug('getting-started')->exists());
        $this->assertTrue(CmsCategory::withSlug('site-management')->exists());
        $this->assertTrue(CmsCategory::withSlug('developers')->exists());
        $this->assertTrue(CmsCategory::withSlug('reference')->exists());
    }

    public function test_excludes_readme_and_style_guide(): void
    {
        if (! File::isDirectory($this->docsPath)) {
            $this->markTestSkipped('docs directory does not exist');
        }

        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->assertSuccessful();

        // README.md should not be seeded
        $this->assertDatabaseMissing('tallcms_posts', ['slug' => 'readme']);

        // STYLE_GUIDE.md should not be seeded (if it exists)
        $this->assertDatabaseMissing('tallcms_posts', ['slug' => 'style-guide']);
    }

    public function test_skips_hidden_docs(): void
    {
        if (! File::isDirectory($this->docsPath)) {
            $this->markTestSkipped('docs directory does not exist');
        }

        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->assertSuccessful();

        // testing-checklist should have hidden: true and not be seeded
        $this->assertDatabaseMissing('tallcms_posts', ['slug' => 'testing-checklist']);
    }

    public function test_creates_posts_with_correct_slugs(): void
    {
        if (! File::isDirectory($this->docsPath)) {
            $this->markTestSkipped('docs directory does not exist');
        }

        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->assertSuccessful();

        // Check some expected slugs based on the plan
        // These will exist once the docs are migrated with frontmatter
        $expectedSlugs = ['installation'];

        foreach ($expectedSlugs as $slug) {
            // Only check if the corresponding doc file exists
            $files = File::glob($this->docsPath.'/*.md');
            $hasValidDoc = false;

            foreach ($files as $file) {
                $content = File::get($file);
                if (str_contains($content, "slug: {$slug}")) {
                    $hasValidDoc = true;
                    break;
                }
            }

            if ($hasValidDoc) {
                $this->assertDatabaseHas('tallcms_posts', ['slug' => $slug]);
            }
        }
    }

    public function test_cleans_up_existing_docs_before_seeding(): void
    {
        if (! File::isDirectory($this->docsPath)) {
            $this->markTestSkipped('docs directory does not exist');
        }

        // Seed once
        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->assertSuccessful();

        $firstCount = CmsPost::count();

        // Seed again - should clean up and recreate
        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->assertSuccessful();

        $secondCount = CmsPost::count();

        // Should have same count (not doubled)
        $this->assertEquals($firstCount, $secondCount);
    }

    public function test_attaches_posts_to_correct_categories(): void
    {
        if (! File::isDirectory($this->docsPath)) {
            $this->markTestSkipped('docs directory does not exist');
        }

        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->assertSuccessful();

        // Find a post and verify it has a category
        $post = CmsPost::first();
        if ($post) {
            $this->assertTrue($post->categories()->exists());
        }
    }

    public function test_requires_user_to_exist(): void
    {
        // Delete all users
        User::query()->delete();

        $this->artisan('db:seed', ['--class' => 'DocumentationSeeder'])
            ->expectsOutput('No users found. Please create a user first: php artisan make:user');

        // No categories should be created
        $this->assertDatabaseMissing('tallcms_categories', ['slug' => 'getting-started']);
    }
}
