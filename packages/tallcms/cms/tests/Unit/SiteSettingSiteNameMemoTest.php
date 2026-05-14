<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Tests\TestCase;

class SiteSettingSiteNameMemoTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('theme')->nullable();
            $table->string('locale')->nullable();
            $table->string('uuid')->unique()->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tallcms_site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::setDefaultDriver('array');
        Cache::flush();
        SiteSetting::forgetMemoizedDefaultSiteId();
    }

    protected function tearDown(): void
    {
        SiteSetting::forgetMemoizedDefaultSiteId();

        parent::tearDown();
    }

    public function test_setting_site_name_clears_request_and_static_site_name_memos(): void
    {
        $siteId = $this->createDefaultSite('Old Site');

        $this->assertSame('Old Site', SiteSetting::get('site_name'));

        SiteSetting::set('site_name', 'New Site');

        $this->assertSame('New Site', SiteSetting::get('site_name'));
        $this->assertSame('New Site', $this->siteName($siteId));
    }

    public function test_missing_global_setting_is_cached_without_requerying(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            if (str_contains($query->sql, 'tallcms_site_settings')) {
                $queries[] = $query->sql;
            }
        });

        $this->assertSame('fallback', SiteSetting::getGlobal('missing_setting', 'fallback'));

        SiteSetting::forgetMemoizedDefaultSiteId();

        $this->assertSame('fallback', SiteSetting::getGlobal('missing_setting', 'fallback'));
        $this->assertCount(1, $queries);
    }

    private function createDefaultSite(string $name): int
    {
        return (int) DB::table('tallcms_sites')->insertGetId([
            'name' => $name,
            'domain' => 'example.test',
            'uuid' => 'site-uuid',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function siteName(int $siteId): ?string
    {
        return DB::table('tallcms_sites')
            ->where('id', $siteId)
            ->value('name');
    }
}
