<?php

namespace TallCms\Cms\Tests\Unit;

use ReflectionMethod;
use TallCms\Cms\Livewire\CmsPageRenderer;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for PostsBlock config extraction from page content.
 *
 * The CmsPageRenderer extracts PostsBlock configuration to pass display
 * settings (show_author, show_date, etc.) to the post detail view.
 * Content can be stored in either Tiptap JSON or HTML format.
 */
class PostsBlockConfigExtractionTest extends TestCase
{
    protected CmsPageRenderer $renderer;

    protected ReflectionMethod $extractFromHtml;

    protected ReflectionMethod $extractFromJson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new CmsPageRenderer;

        // Make protected methods accessible for testing
        $this->extractFromHtml = new ReflectionMethod(CmsPageRenderer::class, 'extractPostsBlockConfigFromHtml');
        $this->extractFromHtml->setAccessible(true);

        $this->extractFromJson = new ReflectionMethod(CmsPageRenderer::class, 'extractPostsBlockConfig');
        $this->extractFromJson->setAccessible(true);
    }

    // -------------------------------------------------------------------------
    // HTML Format: Standard attribute order
    // -------------------------------------------------------------------------

    public function test_extracts_config_from_standard_html_format(): void
    {
        $html = '<div data-type="customBlock" data-id="posts" data-config="{&quot;show_author&quot;:false,&quot;show_date&quot;:true}"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_author']);
        $this->assertTrue($config['show_date']);
    }

    // -------------------------------------------------------------------------
    // HTML Format: Different attribute ordering
    // -------------------------------------------------------------------------

    public function test_extracts_config_with_id_before_type(): void
    {
        $html = '<div data-id="posts" data-type="customBlock" data-config="{&quot;show_author&quot;:false}"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_author']);
    }

    public function test_extracts_config_with_config_first(): void
    {
        $html = '<div data-config="{&quot;show_author&quot;:true,&quot;layout&quot;:&quot;grid&quot;}" data-type="customBlock" data-id="posts"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertTrue($config['show_author']);
        $this->assertEquals('grid', $config['layout']);
    }

    public function test_extracts_config_with_config_before_id(): void
    {
        $html = '<div data-config="{&quot;show_excerpt&quot;:false}" data-id="posts" data-type="customBlock"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_excerpt']);
    }

    // -------------------------------------------------------------------------
    // HTML Format: Single vs double quotes
    // -------------------------------------------------------------------------

    public function test_extracts_config_with_single_quote_attributes(): void
    {
        // Single quotes on attributes with HTML-encoded JSON inside
        $html = "<div data-type='customBlock' data-id='posts' data-config='{&quot;show_author&quot;:false}'></div>";

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_author']);
    }

    public function test_extracts_config_with_mixed_quotes(): void
    {
        $html = '<div data-type="customBlock" data-id=\'posts\' data-config="{&quot;show_date&quot;:false}"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_date']);
    }

    // -------------------------------------------------------------------------
    // HTML Format: Entity encoding variations
    // -------------------------------------------------------------------------

    public function test_extracts_config_with_html_entities(): void
    {
        $html = '<div data-type="customBlock" data-id="posts" data-config="{&quot;show_author&quot;:false,&quot;empty_message&quot;:&quot;No posts yet!&quot;}"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_author']);
        $this->assertEquals('No posts yet!', $config['empty_message']);
    }

    public function test_handles_numeric_entity_encoding(): void
    {
        // Numeric HTML entities (&#34; for quotes)
        $html = '<div data-type="customBlock" data-id="posts" data-config="{&#34;show_author&#34;:true,&#34;columns&#34;:3}"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertTrue($config['show_author']);
        $this->assertEquals(3, $config['columns']);
    }

    // -------------------------------------------------------------------------
    // HTML Format: Complex/realistic config
    // -------------------------------------------------------------------------

    public function test_extracts_full_posts_block_config(): void
    {
        $html = '<div data-type="customBlock" data-config="{&quot;layout&quot;:&quot;grid&quot;,&quot;columns&quot;:3,&quot;per_page&quot;:9,&quot;show_date&quot;:false,&quot;block_uuid&quot;:&quot;52a2a230-8361-40bd-bba7-0d8a0770fc25&quot;,&quot;show_image&quot;:true,&quot;show_author&quot;:false,&quot;show_excerpt&quot;:true,&quot;empty_message&quot;:&quot;No blog posts yet. Check back soon!&quot;,&quot;show_read_more&quot;:true,&quot;show_categories&quot;:true,&quot;enable_pagination&quot;:true}" data-id="posts"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertEquals('grid', $config['layout']);
        $this->assertEquals(3, $config['columns']);
        $this->assertFalse($config['show_date']);
        $this->assertFalse($config['show_author']);
        $this->assertTrue($config['show_image']);
        $this->assertTrue($config['show_excerpt']);
        $this->assertTrue($config['enable_pagination']);
    }

    // -------------------------------------------------------------------------
    // HTML Format: Malformed/missing attributes (should return empty)
    // -------------------------------------------------------------------------

    public function test_returns_empty_for_missing_data_id(): void
    {
        $html = '<div data-type="customBlock" data-config="{&quot;show_author&quot;:false}"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function test_returns_empty_for_wrong_block_id(): void
    {
        $html = '<div data-type="customBlock" data-id="hero" data-config="{&quot;heading&quot;:&quot;Hello&quot;}"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function test_returns_empty_for_missing_data_config(): void
    {
        $html = '<div data-type="customBlock" data-id="posts"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function test_returns_empty_for_invalid_json_in_config(): void
    {
        $html = '<div data-type="customBlock" data-id="posts" data-config="not valid json"></div>';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function test_returns_empty_for_empty_html(): void
    {
        $config = $this->extractFromHtml->invoke($this->renderer, '');

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function test_returns_empty_for_plain_text(): void
    {
        $config = $this->extractFromHtml->invoke($this->renderer, 'Just some plain text content');

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    // -------------------------------------------------------------------------
    // HTML Format: Content with multiple blocks
    // -------------------------------------------------------------------------

    public function test_extracts_posts_config_from_content_with_multiple_blocks(): void
    {
        $html = '
            <div data-type="customBlock" data-id="hero" data-config="{&quot;heading&quot;:&quot;Welcome&quot;}"></div>
            <div data-type="customBlock" data-id="posts" data-config="{&quot;show_author&quot;:false,&quot;columns&quot;:2}"></div>
            <div data-type="customBlock" data-id="cta" data-config="{&quot;title&quot;:&quot;Contact Us&quot;}"></div>
        ';

        $config = $this->extractFromHtml->invoke($this->renderer, $html);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_author']);
        $this->assertEquals(2, $config['columns']);
    }

    // -------------------------------------------------------------------------
    // JSON Format (Tiptap): Basic extraction
    // -------------------------------------------------------------------------

    public function test_extracts_config_from_tiptap_json(): void
    {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => 'posts',
                        'config' => [
                            'show_author' => false,
                            'show_date' => true,
                            'layout' => 'grid',
                        ],
                    ],
                ],
            ],
        ];

        $config = $this->extractFromJson->invoke($this->renderer, $content);

        $this->assertIsArray($config);
        $this->assertFalse($config['show_author']);
        $this->assertTrue($config['show_date']);
        $this->assertEquals('grid', $config['layout']);
    }

    public function test_extracts_posts_config_from_nested_json_content(): void
    {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => 'hero',
                        'config' => ['heading' => 'Welcome'],
                    ],
                ],
                [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => 'posts',
                        'config' => ['show_author' => true, 'columns' => 4],
                    ],
                ],
            ],
        ];

        $config = $this->extractFromJson->invoke($this->renderer, $content);

        $this->assertIsArray($config);
        $this->assertTrue($config['show_author']);
        $this->assertEquals(4, $config['columns']);
    }

    public function test_returns_empty_for_json_without_posts_block(): void
    {
        $content = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'customBlock',
                    'attrs' => [
                        'id' => 'hero',
                        'config' => ['heading' => 'Welcome'],
                    ],
                ],
            ],
        ];

        $config = $this->extractFromJson->invoke($this->renderer, $content);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function test_returns_empty_for_empty_json_array(): void
    {
        $config = $this->extractFromJson->invoke($this->renderer, []);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }
}
