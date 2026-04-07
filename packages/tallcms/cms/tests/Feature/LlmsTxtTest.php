<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Tests\TestCase;

class LlmsTxtTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['key', 'group']);
        });

        Schema::create('tallcms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('content')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_homepage')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tallcms_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_llms_txt_route_is_registered(): void
    {
        $router = $this->app['router'];

        $this->assertTrue(
            $router->has('tallcms.seo.llms-txt'),
            'llms.txt route should always be registered'
        );
    }

    public function test_llms_txt_returns_404_when_disabled(): void
    {
        SiteSetting::set('seo_llms_txt_enabled', false, 'boolean', 'seo');

        $response = $this->get('/llms.txt');

        $response->assertStatus(404);
    }

    public function test_llms_txt_returns_200_with_plain_text_when_enabled(): void
    {
        SiteSetting::set('seo_llms_txt_enabled', true, 'boolean', 'seo');
        SiteSetting::set('site_name', 'Test Site', 'text', 'general');

        $response = $this->get('/llms.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('# Test Site', $response->getContent());
    }

    public function test_llms_txt_includes_site_description(): void
    {
        SiteSetting::set('seo_llms_txt_enabled', true, 'boolean', 'seo');
        SiteSetting::set('site_name', 'My CMS', 'text', 'general');
        SiteSetting::set('site_description', 'A great website', 'text', 'general');

        $response = $this->get('/llms.txt');

        $response->assertStatus(200);
        $this->assertStringContainsString('> A great website', $response->getContent());
    }
}
