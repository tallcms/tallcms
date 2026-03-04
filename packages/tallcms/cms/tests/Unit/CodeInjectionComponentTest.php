<?php

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Tests\TestCase;
use TallCms\Cms\View\Components\CodeInjection;

class CodeInjectionComponentTest extends TestCase
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
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_renders_head_code_from_site_setting(): void
    {
        SiteSetting::set('code_head', '<script>console.log("head")</script>', 'text', 'code-injection');
        Cache::flush();

        $component = new CodeInjection(zone: 'head');
        $rendered = $this->renderComponent($component);

        $this->assertStringContainsString('<script>console.log("head")</script>', $rendered);
    }

    public function test_renders_body_start_code_from_site_setting(): void
    {
        SiteSetting::set('code_body_start', '<!-- GTM noscript -->', 'text', 'code-injection');
        Cache::flush();

        $component = new CodeInjection(zone: 'body_start');
        $rendered = $this->renderComponent($component);

        $this->assertStringContainsString('<!-- GTM noscript -->', $rendered);
    }

    public function test_renders_body_end_code_from_site_setting(): void
    {
        SiteSetting::set('code_body_end', '<script src="chat.js"></script>', 'text', 'code-injection');
        Cache::flush();

        $component = new CodeInjection(zone: 'body_end');
        $rendered = $this->renderComponent($component);

        $this->assertStringContainsString('<script src="chat.js"></script>', $rendered);
    }

    public function test_renders_empty_when_setting_is_blank(): void
    {
        SiteSetting::set('code_head', '', 'text', 'code-injection');
        Cache::flush();

        $component = new CodeInjection(zone: 'head');

        $this->assertFalse($component->shouldRender());
    }

    public function test_renders_empty_when_setting_does_not_exist(): void
    {
        $component = new CodeInjection(zone: 'head');

        $this->assertFalse($component->shouldRender());
    }

    public function test_invalid_zone_renders_empty(): void
    {
        $component = new CodeInjection(zone: 'footer');

        $this->assertEquals('', $component->code);
        $this->assertFalse($component->shouldRender());
    }

    public function test_zone_to_key_mapping(): void
    {
        $mappings = [
            'head' => 'code_head',
            'body_start' => 'code_body_start',
            'body_end' => 'code_body_end',
        ];

        foreach ($mappings as $zone => $expectedKey) {
            $uniqueValue = "<!-- test-{$zone} -->";
            SiteSetting::set($expectedKey, $uniqueValue, 'text', 'code-injection');
            Cache::flush();

            $component = new CodeInjection(zone: $zone);
            $this->assertEquals($uniqueValue, $component->code, "Zone '{$zone}' should read from key '{$expectedKey}'");
        }
    }

    public function test_each_zone_reads_its_own_setting(): void
    {
        SiteSetting::set('code_head', 'HEAD_CODE', 'text', 'code-injection');
        SiteSetting::set('code_body_start', 'BODY_START_CODE', 'text', 'code-injection');
        SiteSetting::set('code_body_end', 'BODY_END_CODE', 'text', 'code-injection');
        Cache::flush();

        $head = new CodeInjection(zone: 'head');
        $bodyStart = new CodeInjection(zone: 'body_start');
        $bodyEnd = new CodeInjection(zone: 'body_end');

        $this->assertEquals('HEAD_CODE', $head->code);
        $this->assertEquals('BODY_START_CODE', $bodyStart->code);
        $this->assertEquals('BODY_END_CODE', $bodyEnd->code);
    }

    private function renderComponent(CodeInjection $component): string
    {
        return $component->render()->with($component->data())->render();
    }
}
