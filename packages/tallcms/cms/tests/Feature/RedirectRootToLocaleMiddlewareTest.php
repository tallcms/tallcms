<?php

namespace TallCms\Cms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Tests\TestCase;

class RedirectRootToLocaleMiddlewareTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain');
            $table->string('theme')->nullable();
            $table->string('locale', 10)->nullable();
            $table->uuid('uuid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
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
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tallcms_site_setting_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('text');
            $table->timestamps();
            $table->unique(['site_id', 'key']);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('tallcms.i18n.enabled', true);
        Config::set('tallcms.i18n.url_strategy', 'prefix');
        Config::set('tallcms.i18n.hide_default_locale', false);
        Config::set('tallcms.i18n.default_locale', 'en');
        Config::set('tallcms.i18n.redirect_root_to_locale', false);
        Config::set('tallcms.i18n.locales', [
            'en' => ['label' => 'English', 'native' => 'English', 'rtl' => false],
            'de' => ['label' => 'German', 'native' => 'Deutsch', 'rtl' => false],
        ]);

        SiteSetting::setGlobal('i18n_enabled', true, 'boolean', 'i18n');
        SiteSetting::setGlobal('default_locale', 'en', 'text', 'i18n');
        SiteSetting::setGlobal('hide_default_locale', false, 'boolean', 'i18n');
        SiteSetting::setGlobal('redirect_root_to_locale', true, 'boolean', 'i18n');
        SiteSetting::clearCache();

        Site::query()->create([
            'name' => 'Default',
            'domain' => 'localhost',
            'locale' => 'en',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_root_redirects_to_default_locale_prefix_when_enabled(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/en');
        $response->assertStatus(301);
    }

    public function test_root_redirect_preserves_query_string(): void
    {
        $response = $this->get('/?utm_source=test');

        $response->assertRedirect('/en?utm_source=test');
        $response->assertStatus(301);
    }

    public function test_root_does_not_redirect_when_toggle_disabled(): void
    {
        SiteSetting::setGlobal('redirect_root_to_locale', false, 'boolean', 'i18n');
        SiteSetting::clearCache();

        $response = $this->get('/');

        $this->assertNotEquals(301, $response->getStatusCode());
        $this->assertNotEquals(302, $response->getStatusCode());
    }

    public function test_root_does_not_redirect_when_hide_default_locale_is_on(): void
    {
        SiteSetting::setGlobal('hide_default_locale', true, 'boolean', 'i18n');
        SiteSetting::clearCache();

        $response = $this->get('/');

        $this->assertNotEquals(301, $response->getStatusCode());
    }

    public function test_admin_paths_are_not_redirected(): void
    {
        $response = $this->get('/admin');

        $this->assertNotEquals(301, $response->getStatusCode());
    }
}
