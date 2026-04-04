<?php

namespace TallCms\Cms\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use TallCms\Cms\Models\SiteSetting;

class CodeInjection extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.code-injection';

    protected static ?string $navigationLabel = 'Embed Code';

    protected static ?string $title = 'Embed Code';

    public ?array $data = [];

    protected ?array $overriddenKeysCache = null;

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
        return config('tallcms.navigation.groups.configuration', 'Configuration');
    }

    public static function getNavigationSort(): ?int
    {
        return 42;
    }

    protected function getMultisiteContext(): ?object
    {
        $sessionValue = session('multisite_admin_site_id');

        if (! $sessionValue || $sessionValue === '__all_sites__') {
            return null;
        }

        try {
            return DB::table('tallcms_sites')
                ->where('id', $sessionValue)
                ->where('is_active', true)
                ->first();
        } catch (QueryException) {
            return null;
        }
    }

    protected function getOverriddenKeys(): array
    {
        if ($this->overriddenKeysCache !== null) {
            return $this->overriddenKeysCache;
        }

        $context = $this->getMultisiteContext();
        if (! $context) {
            return $this->overriddenKeysCache = [];
        }

        try {
            $this->overriddenKeysCache = DB::table('tallcms_site_setting_overrides')
                ->where('site_id', $context->id)
                ->whereIn('key', ['code_head', 'code_body_start', 'code_body_end'])
                ->pluck('key')
                ->toArray();
        } catch (QueryException) {
            $this->overriddenKeysCache = [];
        }

        return $this->overriddenKeysCache;
    }

    protected function clearOverrideCache(): void
    {
        $this->overriddenKeysCache = null;
    }

    public function getSubheading(): ?string
    {
        $context = $this->getMultisiteContext();
        if (! $context) {
            return null;
        }

        return "Editing embed code for: {$context->name} ({$context->domain})";
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

    protected function withMultisiteHint(Textarea $field, string $key): Textarea
    {
        $context = $this->getMultisiteContext();
        if (! $context) {
            return $field;
        }

        $overridden = in_array($key, $this->getOverriddenKeys());

        $field->hint($overridden ? 'Site override' : 'Inherited from global');
        $field->hintColor($overridden ? 'primary' : 'gray');
        $field->hintIcon($overridden ? 'heroicon-m-pencil-square' : 'heroicon-m-globe-alt');

        return $field;
    }

    protected function getFormSchema(): array
    {
        $isMultisite = $this->getMultisiteContext() !== null;

        return [
            Section::make('Warning')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->description($isMultisite
                    ? 'Embed code runs on every page of this site. Only paste code from sources you trust.'
                    : 'Embed code runs on every page for all visitors. Only paste code from sources you trust.')
                ->schema([]),

            Section::make('Head Code')
                ->description('Code embedded inside <head> before </head> (analytics, meta tags, CSS)')
                ->schema([
                    $this->withMultisiteHint(
                        Textarea::make('code_head')
                            ->label('Head Code')
                            ->helperText($this->getAuditHelperText('code_head'))
                            ->rows(8)
                            ->extraInputAttributes(['class' => 'font-mono text-sm'])
                            ->columnSpanFull(),
                        'code_head'
                    ),
                ]),

            Section::make('Body Start Code')
                ->description('Code embedded right after the <body> open tag (GTM noscript, early scripts)')
                ->schema([
                    $this->withMultisiteHint(
                        Textarea::make('code_body_start')
                            ->label('Body Start Code')
                            ->helperText($this->getAuditHelperText('code_body_start'))
                            ->rows(8)
                            ->extraInputAttributes(['class' => 'font-mono text-sm'])
                            ->columnSpanFull(),
                        'code_body_start'
                    ),
                ]),

            Section::make('Body End Code')
                ->description('Code embedded before </body> (tracking pixels, chat widgets, deferred JS)')
                ->schema([
                    $this->withMultisiteHint(
                        Textarea::make('code_body_end')
                            ->label('Body End Code')
                            ->helperText($this->getAuditHelperText('code_body_end'))
                            ->rows(8)
                            ->extraInputAttributes(['class' => 'font-mono text-sm'])
                            ->columnSpanFull(),
                        'code_body_end'
                    ),
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
        $isMultisite = $this->getMultisiteContext() !== null;
        $overriddenKeys = $isMultisite ? $this->getOverriddenKeys() : [];

        foreach (['code_head', 'code_body_start', 'code_body_end'] as $key) {
            $value = $data[$key] ?? '';

            if ($isMultisite) {
                // Only create override if value differs from global or override already exists
                $hasExistingOverride = in_array($key, $overriddenKeys);
                if (! $hasExistingOverride) {
                    $globalValue = SiteSetting::getGlobal($key);
                    if ((string) $value === (string) ($globalValue ?? '')) {
                        continue;
                    }
                }
            }

            SiteSetting::set($key, $value, 'text', 'code-injection');
            SiteSetting::set("{$key}_audit", [
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'at' => now()->toIso8601String(),
            ], 'json', 'code-injection');
        }

        SiteSetting::clearCache();
        $this->clearOverrideCache();

        $context = $this->getMultisiteContext();
        $message = $context
            ? "Embed code saved for {$context->name}."
            : 'Embed code settings saved successfully!';

        Notification::make()
            ->title($message)
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
