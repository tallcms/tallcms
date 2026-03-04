<?php

namespace TallCms\Cms\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use TallCms\Cms\Models\SiteSetting;

class CodeInjection extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.code-injection';

    protected static ?string $navigationLabel = 'Code Injection';

    protected static ?string $title = 'Code Injection';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('Manage:CodeInjection') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-code-bracket';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return (config('tallcms.filament.navigation_sort') ?? 99) + 2;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('Manage:CodeInjection'), 403);

        $this->form->fill([
            'code_head' => SiteSetting::get('code_head', ''),
            'code_body_start' => SiteSetting::get('code_body_start', ''),
            'code_body_end' => SiteSetting::get('code_body_end', ''),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Warning')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->description('Injected code runs on every page for all visitors. Malicious or broken scripts can break your site. Only paste code from sources you trust.')
                ->schema([]),

            Section::make('Head Code')
                ->description('Code injected inside <head> before </head> (analytics, meta tags, CSS)')
                ->schema([
                    Textarea::make('code_head')
                        ->label('Head Code')
                        ->helperText($this->getAuditHelperText('code_head'))
                        ->rows(8)
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->columnSpanFull(),
                ]),

            Section::make('Body Start Code')
                ->description('Code injected right after the <body> open tag (GTM noscript, early scripts)')
                ->schema([
                    Textarea::make('code_body_start')
                        ->label('Body Start Code')
                        ->helperText($this->getAuditHelperText('code_body_start'))
                        ->rows(8)
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->columnSpanFull(),
                ]),

            Section::make('Body End Code')
                ->description('Code injected before </body> (tracking pixels, chat widgets, deferred JS)')
                ->schema([
                    Textarea::make('code_body_end')
                        ->label('Body End Code')
                        ->helperText($this->getAuditHelperText('code_body_end'))
                        ->rows(8)
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('Manage:CodeInjection'), 403);

        $data = $this->form->getState();

        foreach (['code_head', 'code_body_start', 'code_body_end'] as $key) {
            SiteSetting::set($key, $data[$key] ?? '', 'text', 'code-injection');
            SiteSetting::set("{$key}_audit", [
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'at' => now()->toIso8601String(),
            ], 'json', 'code-injection');
        }

        SiteSetting::clearCache();

        Notification::make()
            ->title('Code injection settings saved successfully!')
            ->success()
            ->send();
    }

    protected function getAuditHelperText(string $key): ?string
    {
        $audit = SiteSetting::get("{$key}_audit");

        if (! $audit || ! is_array($audit)) {
            return null;
        }

        $name = $audit['name'] ?? 'Unknown';
        $at = $audit['at'] ?? null;

        if ($at) {
            try {
                $date = \Carbon\Carbon::parse($at)->format('M j, Y g:i A');

                return "Last modified by {$name} on {$date}";
            } catch (\Throwable) {
                // Fall through
            }
        }

        return "Last modified by {$name}";
    }
}
