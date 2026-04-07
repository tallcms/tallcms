<?php

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Tests\TestCase;

class HowToBlockSchemaTest extends TestCase
{
    private function renderAndExtractSchema(array $config): ?array
    {
        $html = \TallCms\Cms\Filament\Blocks\HowToBlock::toHtml($config, []);

        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            return json_decode($matches[1], true);
        }

        return null;
    }

    public function test_generates_howto_schema_with_steps(): void
    {
        $config = [
            'title' => 'How to Bake a Cake',
            'description' => 'A simple cake recipe',
            'show_schema' => true,
            'steps' => [
                ['step_name' => 'Preheat oven', 'step_text' => 'Set to 350F', 'step_image' => null, 'step_url' => null],
                ['step_name' => 'Mix ingredients', 'step_text' => 'Combine flour and sugar', 'step_image' => null, 'step_url' => null],
            ],
        ];

        $schema = $this->renderAndExtractSchema($config);

        $this->assertNotNull($schema);
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('HowTo', $schema['@type']);
        $this->assertEquals('How to Bake a Cake', $schema['name']);
        $this->assertEquals('A simple cake recipe', $schema['description']);
        $this->assertCount(2, $schema['step']);
        $this->assertEquals('HowToStep', $schema['step'][0]['@type']);
        $this->assertEquals(1, $schema['step'][0]['position']);
        $this->assertEquals('Preheat oven', $schema['step'][0]['name']);
        $this->assertEquals('Set to 350F', $schema['step'][0]['text']);
    }

    public function test_includes_total_time_when_set(): void
    {
        $config = [
            'title' => 'Quick Guide',
            'total_time' => 'PT30M',
            'show_schema' => true,
            'steps' => [
                ['step_name' => 'Step 1', 'step_text' => 'Do this', 'step_image' => null, 'step_url' => null],
                ['step_name' => 'Step 2', 'step_text' => 'Do that', 'step_image' => null, 'step_url' => null],
            ],
        ];

        $schema = $this->renderAndExtractSchema($config);

        $this->assertEquals('PT30M', $schema['totalTime']);
    }

    public function test_includes_estimated_cost_when_set(): void
    {
        $config = [
            'title' => 'DIY Project',
            'estimated_cost' => '50',
            'currency' => 'USD',
            'show_schema' => true,
            'steps' => [
                ['step_name' => 'Step 1', 'step_text' => 'Buy materials', 'step_image' => null, 'step_url' => null],
                ['step_name' => 'Step 2', 'step_text' => 'Assemble', 'step_image' => null, 'step_url' => null],
            ],
        ];

        $schema = $this->renderAndExtractSchema($config);

        $this->assertEquals('MonetaryAmount', $schema['estimatedCost']['@type']);
        $this->assertEquals('USD', $schema['estimatedCost']['currency']);
        $this->assertEquals('50', $schema['estimatedCost']['value']);
    }

    public function test_schema_omitted_when_show_schema_false(): void
    {
        $config = [
            'title' => 'No Schema',
            'show_schema' => false,
            'steps' => [
                ['step_name' => 'Step 1', 'step_text' => 'Do this', 'step_image' => null, 'step_url' => null],
                ['step_name' => 'Step 2', 'step_text' => 'Do that', 'step_image' => null, 'step_url' => null],
            ],
        ];

        $schema = $this->renderAndExtractSchema($config);

        $this->assertNull($schema);
    }

    public function test_step_url_included_when_set(): void
    {
        $config = [
            'title' => 'Guide',
            'show_schema' => true,
            'steps' => [
                ['step_name' => 'Visit site', 'step_text' => 'Go here', 'step_image' => null, 'step_url' => 'https://example.com'],
                ['step_name' => 'Done', 'step_text' => 'Finished', 'step_image' => null, 'step_url' => null],
            ],
        ];

        $schema = $this->renderAndExtractSchema($config);

        $this->assertEquals('https://example.com', $schema['step'][0]['url']);
        $this->assertArrayNotHasKey('url', $schema['step'][1]);
    }
}
