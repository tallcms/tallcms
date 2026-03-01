<?php

namespace TallCms\Cms\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use TallCms\Cms\Console\Commands\TallCmsInstall;
use TallCms\Cms\Tests\TestCase;

/**
 * Tests for TallCmsInstall::PLUGIN_REGISTRATION_PATTERN regex.
 *
 * Ensures the file-based fallback detection correctly identifies
 * TallCmsPlugin registration across all common provider styles.
 */
class PluginRegistrationRegexTest extends TestCase
{
    #[DataProvider('matchingSnippets')]
    public function test_matches_valid_registration(string $label, string $snippet): void
    {
        $this->assertMatchesRegularExpression(
            TallCmsInstall::PLUGIN_REGISTRATION_PATTERN,
            $snippet,
            "Failed to match: {$label}"
        );
    }

    #[DataProvider('nonMatchingSnippets')]
    public function test_rejects_non_registration(string $label, string $snippet): void
    {
        $this->assertDoesNotMatchRegularExpression(
            TallCmsInstall::PLUGIN_REGISTRATION_PATTERN,
            $snippet,
            "Should not match: {$label}"
        );
    }

    public static function matchingSnippets(): iterable
    {
        yield 'singular ->plugin()' => [
            'singular ->plugin()',
            '$panel->plugin(TallCmsPlugin::make())',
        ];

        yield 'singular with FQCN' => [
            'singular with FQCN',
            '$panel->plugin(\TallCms\Cms\TallCmsPlugin::make())',
        ];

        yield 'plural ->plugins() single entry' => [
            'plural ->plugins() single entry',
            '$panel->plugins([TallCmsPlugin::make()])',
        ];

        yield 'plural multiline' => [
            'plural multiline',
            <<<'PHP'
            $panel->plugins([
                TallCmsPlugin::make(),
            ])
            PHP,
        ];

        yield 'plural multiline with other plugins before' => [
            'plural multiline with other plugins before',
            <<<'PHP'
            $panel->plugins([
                FilamentShieldPlugin::make(),
                TallCmsPlugin::make(),
            ])
            PHP,
        ];

        yield 'plural multiline with other plugins after' => [
            'plural multiline with other plugins after',
            <<<'PHP'
            $panel->plugins([
                TallCmsPlugin::make(),
                SpatieLaravelTranslatablePlugin::make(),
            ])
            PHP,
        ];

        yield 'FQCN in plugins array' => [
            'FQCN in plugins array',
            <<<'PHP'
            $panel->plugins([
                \TallCms\Cms\TallCmsPlugin::make(),
            ])
            PHP,
        ];

        yield 'whitespace before opening paren' => [
            'whitespace before opening paren',
            '$panel->plugin( TallCmsPlugin::make() )',
        ];

        yield 'newline after ->plugin(' => [
            'newline after ->plugin(',
            <<<'PHP'
            $panel->plugin(
                TallCmsPlugin::make()
            )
            PHP,
        ];

        yield 'realistic full provider' => [
            'realistic full provider',
            <<<'PHP'
            <?php

            namespace App\Providers\Filament;

            use Filament\Panel;
            use Filament\PanelProvider;
            use TallCms\Cms\TallCmsPlugin;

            class AppPanelProvider extends PanelProvider
            {
                public function panel(Panel $panel): Panel
                {
                    return $panel
                        ->default()
                        ->id('app')
                        ->path('app')
                        ->plugins([
                            TallCmsPlugin::make(),
                        ]);
                }
            }
            PHP,
        ];
    }

    public static function nonMatchingSnippets(): iterable
    {
        yield 'no plugin at all' => [
            'no plugin at all',
            '$panel->id("admin")->path("admin")',
        ];

        yield 'different plugin' => [
            'different plugin',
            '$panel->plugin(FilamentShieldPlugin::make())',
        ];

        yield 'plugins array without TallCmsPlugin' => [
            'plugins array without TallCmsPlugin',
            <<<'PHP'
            $panel->plugins([
                FilamentShieldPlugin::make(),
                SpatieLaravelTranslatablePlugin::make(),
            ])
            PHP,
        ];

        yield 'class name substring' => [
            'class name substring',
            '$panel->plugin(NotTallCmsPlugin::make())',
        ];
    }
}
