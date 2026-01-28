<?php

namespace TallCms\Cms\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSetServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use TallCms\Cms\TallCmsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            NestedSetServiceProvider::class,
            SanctumServiceProvider::class,
            TallCmsServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // Create users table before package migrations run (required for foreign keys)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Create personal_access_tokens table for Sanctum
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use SQLite in-memory for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up basic app config
        $app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');

        // Configure Sanctum auth guard
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'users',
        ]);

        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \TallCms\Cms\Tests\Fixtures\User::class,
        ]);

        // Configure for plugin mode by default in tests
        $app['config']->set('tallcms.mode', 'plugin');
        $app['config']->set('tallcms.plugin_mode.preview_routes_enabled', true);
        $app['config']->set('tallcms.plugin_mode.api_routes_enabled', true);

        // Use test User model
        $app['config']->set('tallcms.plugin_mode.user_model', \TallCms\Cms\Tests\Fixtures\User::class);

        // Enable API routes (must be set before service provider boots)
        $app['config']->set('tallcms.api.enabled', true);
        $app['config']->set('tallcms.api.prefix', 'api/v1/tallcms');
        $app['config']->set('tallcms.api.rate_limit', 60);
        $app['config']->set('tallcms.api.auth_rate_limit', 5);
    }

    /**
     * Configure the test to run in standalone mode
     */
    protected function configureStandaloneMode($app): void
    {
        $app['config']->set('tallcms.mode', 'standalone');
    }

    /**
     * Configure the test to run in plugin mode
     */
    protected function configurePluginMode($app): void
    {
        $app['config']->set('tallcms.mode', 'plugin');
    }
}
