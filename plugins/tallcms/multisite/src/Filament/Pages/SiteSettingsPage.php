<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Cache;
use TallCms\Cms\Models\SiteSetting;
use Tallcms\Multisite\Models\SiteSettingOverride;
use Tallcms\Multisite\Services\CurrentSiteResolver;

class SiteSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'tallcms-multisite::filament.pages.site-settings';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings Overrides';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Multisite';
    }

    public ?array $data = [];

    /**
     * Settings that can be overridden per site.
     * Format: key => [type, group]
     */
    protected function getOverridableSettings(): array
    {
        return [
            'site_name' => ['text', 'general'],
            'site_tagline' => ['text', 'general'],
            'site_description' => ['text', 'general'],
            'contact_email' => ['text', 'contact'],
            'company_name' => ['text', 'contact'],
            'company_address' => ['text', 'contact'],
            'contact_phone' => ['text', 'contact'],
            'maintenance_mode' => ['boolean', 'maintenance'],
            'maintenance_message' => ['text', 'maintenance'],
            'show_powered_by' => ['boolean', 'branding'],
        ];
    }

    public function mount(): void
    {
        $resolver = app(CurrentSiteResolver::class);
        $siteId = $resolver->id();

        if (! $siteId) {
            return;
        }

        // Load current overrides
        $overrides = SiteSettingOverride::where('site_id', $siteId)
            ->pluck('value', 'key')
            ->toArray();

        $data = [];
        foreach ($this->getOverridableSettings() as $key => [$type, $group]) {
            if (array_key_exists($key, $overrides)) {
                $data[$key] = $type === 'boolean'
                    ? filter_var($overrides[$key], FILTER_VALIDATE_BOOLEAN)
                    : $overrides[$key];
            } else {
                $data[$key] = null; // null = use global default
            }
        }

        $this->form->fill($data);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General')
                ->description('Leave empty to use the global setting.')
                ->schema([
                    TextInput::make('site_name')
                        ->placeholder(fn () => SiteSetting::get('site_name', 'Site Name'))
                        ->nullable(),
                    TextInput::make('site_tagline')
                        ->placeholder(fn () => SiteSetting::get('site_tagline', ''))
                        ->nullable(),
                    Textarea::make('site_description')
                        ->placeholder(fn () => SiteSetting::get('site_description', ''))
                        ->nullable()
                        ->rows(3),
                ])
                ->columns(2),

            Section::make('Contact')
                ->description('Leave empty to use the global setting.')
                ->schema([
                    TextInput::make('contact_email')
                        ->email()
                        ->placeholder(fn () => SiteSetting::get('contact_email', ''))
                        ->nullable(),
                    TextInput::make('company_name')
                        ->placeholder(fn () => SiteSetting::get('company_name', ''))
                        ->nullable(),
                    TextInput::make('company_address')
                        ->placeholder(fn () => SiteSetting::get('company_address', ''))
                        ->nullable(),
                    TextInput::make('contact_phone')
                        ->placeholder(fn () => SiteSetting::get('contact_phone', ''))
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Maintenance')
                ->schema([
                    Toggle::make('maintenance_mode')
                        ->label('Maintenance Mode')
                        ->helperText('Override maintenance mode for this site'),
                    Textarea::make('maintenance_message')
                        ->placeholder(fn () => SiteSetting::get('maintenance_message', ''))
                        ->nullable()
                        ->rows(3),
                ])
                ->columns(1),

            Section::make('Branding')
                ->schema([
                    Toggle::make('show_powered_by')
                        ->label('Show "Powered by TallCMS"')
                        ->helperText('Override the global branding setting for this site'),
                ])
                ->columns(1),
        ];
    }

    public function save(): void
    {
        $resolver = app(CurrentSiteResolver::class);
        $siteId = $resolver->id();

        if (! $siteId) {
            Notification::make()
                ->title('No site selected')
                ->body('Please select a site from the site switcher first.')
                ->warning()
                ->send();

            return;
        }

        $data = $this->form->getState();
        $settings = $this->getOverridableSettings();

        foreach ($settings as $key => [$type, $group]) {
            $value = $data[$key] ?? null;

            // Clear cache for this site+key
            Cache::forget("site_setting_{$siteId}_{$key}");

            if ($value === null || $value === '') {
                // Remove override — falls back to global
                SiteSettingOverride::where('site_id', $siteId)
                    ->where('key', $key)
                    ->delete();

                continue;
            }

            $processedValue = match ($type) {
                'boolean' => $value ? '1' : '0',
                'json' => json_encode($value),
                default => (string) $value,
            };

            SiteSettingOverride::updateOrCreate(
                ['site_id' => $siteId, 'key' => $key],
                ['value' => $processedValue, 'type' => $type]
            );
        }

        Notification::make()
            ->title('Site settings saved')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        // Only accessible when a specific site is selected (not "All Sites")
        $resolver = app(CurrentSiteResolver::class);

        return $resolver->isResolved() && $resolver->id() !== null;
    }
}
