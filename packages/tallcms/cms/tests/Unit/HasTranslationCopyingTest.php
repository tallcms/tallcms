<?php

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Filament\Concerns\HasTranslationCopying;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for the HasTranslationCopying trait.
 */
class HasTranslationCopyingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Enable i18n for tests
        $this->app['config']->set('tallcms.i18n.enabled', true);
        $this->app['config']->set('tallcms.i18n.locales', [
            'en' => ['label' => 'English', 'native' => 'English', 'rtl' => false],
            'zh_CN' => ['label' => 'Chinese', 'native' => '简体中文', 'rtl' => false],
        ]);
        $this->app['config']->set('tallcms.i18n.default_locale', 'en');
    }

    /**
     * Create a test class that uses the trait for testing protected methods.
     */
    protected function createTestClass(): object
    {
        return new class {
            use HasTranslationCopying;

            // Expose protected method for testing
            public function testIsLocaleDataEmpty(array $data, array $translatableAttributes): bool
            {
                return $this->isLocaleDataEmpty($data, $translatableAttributes);
            }
        };
    }

    public function test_empty_array_is_detected_as_empty(): void
    {
        $testClass = $this->createTestClass();

        $result = $testClass->testIsLocaleDataEmpty([], ['title', 'content']);

        $this->assertTrue($result);
    }

    public function test_array_with_null_values_is_detected_as_empty(): void
    {
        $testClass = $this->createTestClass();

        $data = [
            'title' => null,
            'content' => null,
        ];

        $result = $testClass->testIsLocaleDataEmpty($data, ['title', 'content']);

        $this->assertTrue($result);
    }

    public function test_array_with_empty_strings_is_detected_as_empty(): void
    {
        $testClass = $this->createTestClass();

        $data = [
            'title' => '',
            'content' => '',
        ];

        $result = $testClass->testIsLocaleDataEmpty($data, ['title', 'content']);

        $this->assertTrue($result);
    }

    public function test_array_with_empty_arrays_is_detected_as_empty(): void
    {
        $testClass = $this->createTestClass();

        $data = [
            'title' => '',
            'content' => [], // Block content as empty array
        ];

        $result = $testClass->testIsLocaleDataEmpty($data, ['title', 'content']);

        $this->assertTrue($result);
    }

    public function test_array_with_content_is_detected_as_not_empty(): void
    {
        $testClass = $this->createTestClass();

        $data = [
            'title' => 'Hello World',
            'content' => '',
        ];

        $result = $testClass->testIsLocaleDataEmpty($data, ['title', 'content']);

        $this->assertFalse($result);
    }

    public function test_array_with_block_content_is_detected_as_not_empty(): void
    {
        $testClass = $this->createTestClass();

        $data = [
            'title' => '',
            'content' => [
                ['type' => 'hero', 'data' => ['title' => 'Welcome']],
            ],
        ];

        $result = $testClass->testIsLocaleDataEmpty($data, ['title', 'content']);

        $this->assertFalse($result);
    }

    public function test_only_checks_translatable_attributes(): void
    {
        $testClass = $this->createTestClass();

        // Has non-translatable data but translatable fields are empty
        $data = [
            'title' => '',
            'content' => '',
            'status' => 'published', // Non-translatable, should be ignored
        ];

        $result = $testClass->testIsLocaleDataEmpty($data, ['title', 'content']);

        $this->assertTrue($result);
    }

    public function test_mixed_content_with_some_filled_is_not_empty(): void
    {
        $testClass = $this->createTestClass();

        $data = [
            'title' => 'About Us',
            'slug' => '',
            'content' => null,
            'meta_title' => '',
            'meta_description' => '',
        ];

        $result = $testClass->testIsLocaleDataEmpty($data, ['title', 'slug', 'content', 'meta_title', 'meta_description']);

        $this->assertFalse($result);
    }
}
