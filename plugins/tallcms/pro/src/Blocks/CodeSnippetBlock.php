<?php

namespace Tallcms\Pro\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Tallcms\Pro\Traits\RequiresLicense;

class CodeSnippetBlock extends RichContentCustomBlock
{
    use RequiresLicense;

    public static function getId(): string
    {
        return 'pro-code-snippet';
    }

    public static function getLabel(): string
    {
        return 'Code Snippet (Pro)';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Syntax-highlighted code blocks with copy button')
            ->modalHeading('Configure Code Snippet Block')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Header')
                    ->schema([
                        TextInput::make('heading')
                            ->label('Section Heading')
                            ->placeholder('Code Example'),

                        Textarea::make('subheading')
                            ->label('Subheading')
                            ->placeholder('Here\'s how to implement this feature')
                            ->rows(2),
                    ]),

                Section::make('Code')
                    ->schema([
                        Select::make('language')
                            ->label('Language')
                            ->options([
                                'javascript' => 'JavaScript',
                                'typescript' => 'TypeScript',
                                'php' => 'PHP',
                                'python' => 'Python',
                                'html' => 'HTML',
                                'css' => 'CSS',
                                'scss' => 'SCSS/Sass',
                                'json' => 'JSON',
                                'yaml' => 'YAML',
                                'bash' => 'Bash/Shell',
                                'sql' => 'SQL',
                                'markdown' => 'Markdown',
                                'jsx' => 'JSX (React)',
                                'tsx' => 'TSX (React)',
                                'vue' => 'Vue',
                                'go' => 'Go',
                                'rust' => 'Rust',
                                'java' => 'Java',
                                'csharp' => 'C#',
                                'cpp' => 'C++',
                                'ruby' => 'Ruby',
                                'swift' => 'Swift',
                                'kotlin' => 'Kotlin',
                                'docker' => 'Dockerfile',
                                'nginx' => 'Nginx',
                                'apache' => 'Apache',
                                'plaintext' => 'Plain Text',
                            ])
                            ->default('javascript')
                            ->searchable()
                            ->required(),

                        TextInput::make('filename')
                            ->label('Filename')
                            ->placeholder('app.js')
                            ->helperText('Optional filename to display'),

                        Textarea::make('code')
                            ->label('Code')
                            ->required()
                            ->rows(12)
                            ->placeholder('// Your code here'),
                    ]),

                Section::make('Options')
                    ->schema([
                        Toggle::make('show_line_numbers')
                            ->label('Show Line Numbers')
                            ->default(true),

                        Toggle::make('show_copy_button')
                            ->label('Show Copy Button')
                            ->default(true),

                        Toggle::make('wrap_lines')
                            ->label('Wrap Long Lines')
                            ->default(false),

                        Select::make('theme')
                            ->label('Theme')
                            ->options([
                                'okaidia' => 'Okaidia (Dark)',
                                'tomorrow' => 'Tomorrow (Dark)',
                                'twilight' => 'Twilight (Dark)',
                                'dark' => 'Dark',
                                'coy' => 'Coy (Light)',
                                'solarizedlight' => 'Solarized Light',
                            ])
                            ->default('okaidia'),

                        TextInput::make('highlight_lines')
                            ->label('Highlight Lines')
                            ->placeholder('1, 3-5, 8')
                            ->helperText('Comma-separated line numbers or ranges'),

                        Select::make('max_height')
                            ->label('Max Height')
                            ->options([
                                'none' => 'No Limit',
                                'sm' => 'Small (300px)',
                                'md' => 'Medium (400px)',
                                'lg' => 'Large (500px)',
                                'xl' => 'Extra Large (600px)',
                            ])
                            ->default('none'),

                        Placeholder::make('syntax_note')
                            ->label('')
                            ->content('Note: Syntax highlighting (colored keywords, strings, etc.) is only visible on the frontend. The admin preview shows theme colors and formatting.')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        $html = view('tallcms-pro::blocks.code-snippet', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'language' => $config['language'] ?? 'javascript',
            'filename' => $config['filename'] ?? '',
            'code' => $config['code'] ?? '',
            'show_line_numbers' => $config['show_line_numbers'] ?? true,
            'show_copy_button' => $config['show_copy_button'] ?? true,
            'wrap_lines' => $config['wrap_lines'] ?? false,
            'theme' => $config['theme'] ?? 'okaidia',
            'highlight_lines' => $config['highlight_lines'] ?? '',
            'max_height' => $config['max_height'] ?? 'none',
            'is_preview' => true,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }

    public static function toHtml(array $config, array $data): string
    {
        $html = view('tallcms-pro::blocks.code-snippet', [
            'heading' => $config['heading'] ?? '',
            'subheading' => $config['subheading'] ?? '',
            'language' => $config['language'] ?? 'javascript',
            'filename' => $config['filename'] ?? '',
            'code' => $config['code'] ?? '',
            'show_line_numbers' => $config['show_line_numbers'] ?? true,
            'show_copy_button' => $config['show_copy_button'] ?? true,
            'wrap_lines' => $config['wrap_lines'] ?? false,
            'theme' => $config['theme'] ?? 'okaidia',
            'highlight_lines' => $config['highlight_lines'] ?? '',
            'max_height' => $config['max_height'] ?? 'none',
            'is_preview' => false,
        ])->render();

        return static::wrapWithLicenseCheck($html);
    }
}
