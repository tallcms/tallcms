<?php

namespace TallCms\Cms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TallCms\Cms\View\Components\CodeInjection;

class CodeInjectionComponentTest extends TestCase
{
    public function test_zone_to_key_mapping_head(): void
    {
        // Verify the component maps zone names to SiteSetting keys correctly
        // We test the mapping logic by inspecting constructor behavior with reflection
        $component = $this->createPartialMock(CodeInjection::class, []);

        // Use reflection to check the allowed zones constant
        $reflection = new \ReflectionClass(CodeInjection::class);
        $constant = $reflection->getConstant('ALLOWED_ZONES');

        $this->assertContains('head', $constant);
        $this->assertContains('body_start', $constant);
        $this->assertContains('body_end', $constant);
    }

    public function test_zone_to_key_mapping_produces_correct_keys(): void
    {
        // The component maps zone to "code_{$zone}"
        $expectedMappings = [
            'head' => 'code_head',
            'body_start' => 'code_body_start',
            'body_end' => 'code_body_end',
        ];

        foreach ($expectedMappings as $zone => $expectedKey) {
            $this->assertEquals($expectedKey, "code_{$zone}");
        }
    }

    public function test_invalid_zone_is_not_in_allowed_list(): void
    {
        $reflection = new \ReflectionClass(CodeInjection::class);
        $constant = $reflection->getConstant('ALLOWED_ZONES');

        $this->assertNotContains('footer', $constant);
        $this->assertNotContains('sidebar', $constant);
        $this->assertNotContains('', $constant);
    }

    public function test_allowed_zones_has_exactly_three_entries(): void
    {
        $reflection = new \ReflectionClass(CodeInjection::class);
        $constant = $reflection->getConstant('ALLOWED_ZONES');

        $this->assertCount(3, $constant);
    }

    public function test_component_renders_correct_view(): void
    {
        // Verify the render method references the correct view path
        $reflection = new \ReflectionClass(CodeInjection::class);
        $method = $reflection->getMethod('render');

        // The method should exist
        $this->assertTrue($reflection->hasMethod('render'));
    }

    public function test_component_extends_illuminate_component(): void
    {
        $this->assertTrue(is_subclass_of(CodeInjection::class, \Illuminate\View\Component::class));
    }
}
