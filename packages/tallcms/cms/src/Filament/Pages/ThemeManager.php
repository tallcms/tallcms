<?php

namespace TallCms\Cms\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Models\Theme;
use TallCms\Cms\Services\MarketplaceCatalogService;
use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\ThemeManager as ThemeManagerService;
use TallCms\Cms\Services\ThemeValidator;

class ThemeManager extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static ?string $title = 'Theme Manager';

    protected string $view = 'tallcms::filament.pages.theme-manager';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-paint-brush';
    }

    public static function getNavigationLabel(): string
    {
        return 'Themes';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.navigation.groups.appearance', 'Appearance');
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public ?string $selectedTheme = null;

    public ?array $themeDetails = null;

    #[Url]
    public string $search = '';

    public int $themePage = 1;

    public int $themesPerPage = 12;

    public bool $filterDarkMode = false;

    public bool $filterThemeController = false;

    public bool $filterResponsive = false;

    public bool $filterAnimations = false;

    #[Url]
    public string $sort = 'active';

    public array $licenseStatuses = [];

    public function mount(): void
    {
        $this->refreshLicenseStatuses();
    }

    /**
     * Get the theme manager service
     */
    protected function getThemeManager(): ThemeManagerService
    {
        return app(ThemeManagerService::class);
    }

    /**
     * Get the theme validator service
     */
    protected function getValidator(): ThemeValidator
    {
        return app(ThemeValidator::class);
    }

    /**
     * Get multisite admin context by reading the session, with a role-based
     * fallback for users who haven't explicitly switched sites.
     *
     * Resolution order:
     *   1. Explicit session site selected via the Site Switcher.
     *   2. "__all_sites__" sentinel → null (caller treats as global).
     *   3. No session value AND user is not super_admin → their first owned
     *      site. Critical for site_owners on SaaS: they own exactly one
     *      (or a handful of) sites and would never touch the Site Switcher.
     *      Without this fallback, theme activation, preset changes, etc.
     *      all write to the GLOBAL config/theme.php instead of scoping to
     *      their own site.
     *   4. No session value and user is super_admin → null (caller treats
     *      as global — super_admins manage installation-wide defaults).
     *
     * @return ?stdClass with columns: id, name, domain, theme, etc.
     */
    protected function getMultisiteContext(): ?object
    {
        $sessionValue = session('multisite_admin_site_id');

        // Explicit "All Sites" selection
        if ($sessionValue === '__all_sites__') {
            return null;
        }

        try {
            if ($sessionValue && is_numeric($sessionValue)) {
                $site = DB::table('tallcms_sites')
                    ->where('id', $sessionValue)
                    ->where('is_active', true)
                    ->first();

                return $site ?: null;
            }

            // No session value — fall back based on role.
            $user = auth()->user();
            if (! $user) {
                return null;
            }

            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                // Super-admins manage global defaults; null = global.
                return null;
            }

            $site = DB::table('tallcms_sites')
                ->where('user_id', $user->getAuthIdentifier())
                ->where('is_active', true)
                ->orderBy('created_at')
                ->first();

            return $site ?: null;
        } catch (QueryException) {
            // Table doesn't exist (multisite plugin not installed)
            return null;
        }
    }

    public function getSubheading(): ?string
    {
        $context = $this->getMultisiteContext();
        if (! $context) {
            return null;
        }

        return "Managing theme for: {$context->name} ({$context->domain})";
    }

    /**
     * Get all available themes with metadata
     */
    #[Computed]
    public function themes(): Collection
    {
        return $this->getThemeManager()->getAvailableThemes()
            ->map(fn (Theme $theme) => [
                'slug' => $theme->slug,
                'name' => $theme->name,
                'description' => $theme->description,
                'version' => $theme->version,
                'author' => $theme->author,
                'screenshot' => $theme->getScreenshotUrl(),
                'isActive' => $theme->slug === $this->getActiveThemeSlug(),
                'supports' => $theme->supports,
                'parent' => $theme->parent,
                'isBuilt' => $theme->isBuilt(),
                'isPrebuilt' => $theme->isPrebuilt(),
                'meetsRequirements' => $theme->meetsRequirements(),
                'unmetRequirements' => $theme->getUnmetRequirements(),
                'daisyuiPreset' => $theme->getDaisyUIPreset(),
                'daisyuiColors' => $this->getPresetColors($theme),
                'hasDarkMode' => $theme->supports('dark_mode'),
                'hasThemeController' => $theme->supportsThemeController(),
                'hasResponsive' => $theme->supports('responsive'),
                'hasAnimations' => $theme->supports('animations'),
                'requiresLicense' => $theme->requiresLicense(),
                'licenseSlug' => $theme->getLicenseSlug(),
                'licenseStatus' => $theme->requiresLicense()
                    ? ($this->licenseStatuses[$theme->getLicenseSlug()] ?? null)
                    : null,
                'presets' => $theme->getDaisyUIPresets(),
                'tags' => $theme->getTags(),
                'purchaseUrl' => $theme->getPurchaseUrl(),
            ])
            ->values();
    }

    /**
     * Get themes filtered by search and feature toggles
     */
    #[Computed]
    public function filteredThemes(): Collection
    {
        $themes = $this->themes;

        if (! empty($this->search)) {
            $search = strtolower(trim($this->search));
            $themes = $themes->filter(fn ($t) => str_contains(strtolower($t['name']), $search)
                || str_contains(strtolower($t['description'] ?? ''), $search)
                || str_contains(strtolower($t['author']), $search)
                || str_contains(strtolower($t['daisyuiPreset'] ?? ''), $search)
                || collect($t['tags'] ?? [])->contains(fn ($tag) => str_contains(strtolower($tag), $search))
            );
        }

        if ($this->filterDarkMode) {
            $themes = $themes->where('hasDarkMode', true);
        }
        if ($this->filterThemeController) {
            $themes = $themes->where('hasThemeController', true);
        }
        if ($this->filterResponsive) {
            $themes = $themes->where('hasResponsive', true);
        }
        if ($this->filterAnimations) {
            $themes = $themes->where('hasAnimations', true);
        }

        $themes = $themes->sortBy(fn ($t) => match ($this->sort) {
            'name' => [0, $t['name']],
            'preset' => [$t['daisyuiPreset'], $t['isActive'] ? 0 : 1, $t['name']],
            default => [$t['isActive'] ? 0 : 1, $t['name']],
        });

        return $themes->values();
    }

    /**
     * Get paginated themes for the current page
     */
    #[Computed]
    public function paginatedThemes(): Collection
    {
        return $this->filteredThemes
            ->slice(($this->themePage - 1) * $this->themesPerPage, $this->themesPerPage)
            ->values();
    }

    /**
     * Get total number of theme pages
     */
    #[Computed]
    public function themePageCount(): int
    {
        return (int) ceil($this->filteredThemes->count() / $this->themesPerPage);
    }

    /**
     * Navigate to a theme page
     */
    public function goToThemePage(int $page): void
    {
        $this->themePage = max(1, min($page, $this->themePageCount));
    }

    /**
     * Reset theme pagination when search or filters change
     */
    public function updatedSearch(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterDarkMode(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterThemeController(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterResponsive(): void
    {
        $this->themePage = 1;
    }

    public function updatedFilterAnimations(): void
    {
        $this->themePage = 1;
    }

    /**
     * Get available themes from the remote marketplace catalog
     */
    #[Computed]
    public function availableMarketplaceThemes(): Collection
    {
        $catalog = app(MarketplaceCatalogService::class)->getThemes();

        // Use licenseSlug as the canonical identifier for installed themes.
        // Theme::getLicenseSlug() defaults to "tallcms/theme-{slug}".
        // Marketplace theme entries must use the same convention.
        $installedSlugs = $this->themes
            ->pluck('licenseSlug')
            ->filter()
            ->toArray();

        return collect($catalog)
            ->filter(fn ($theme) => ! in_array($theme['full_slug'], $installedSlugs))
            ->sortByDesc('featured')
            ->take(4)
            ->values();
    }

    /**
     * Get daisyUI preset colors for a theme
     */
    protected function getPresetColors(Theme $theme): array
    {
        $customColors = $theme->getDaisyUIColors();
        if (! empty($customColors)) {
            return $customColors;
        }

        $preset = $theme->getDaisyUIPreset();
        if ($preset) {
            return Theme::DAISYUI_PRESET_COLORS[$preset] ?? [];
        }

        return [];
    }

    /**
     * Clear cached theme computed properties
     */
    /**
     * Get the currently active theme data
     */
    #[Computed]
    public function activeTheme(): ?array
    {
        $theme = $this->themes->firstWhere('isActive', true);

        if ($theme) {
            // Resolve theme model directly from slug (not from global ThemeManager state)
            $activeThemeModel = Theme::find($this->getActiveThemeSlug());
            $fallback = $activeThemeModel?->getDaisyUIPreset() ?? 'light';
            $presets = $activeThemeModel?->getDaisyUIPresets() ?? [];

            // SiteSetting::get() resolves to the active site's override (or the
            // default site's override on standalone installs, post-4.0.8), and
            // falls back to the global value when no override exists. The older
            // branching on getMultisiteContext() predates the default-site
            // resolver fallback and silently skipped the override on standalone
            // installs — save went to the override table, read hit the globals
            // table, so the preset never persisted.
            $stored = SiteSetting::get('theme_default_preset');

            $theme['defaultPreset'] = ($stored && in_array($stored, $presets)) ? $stored : $fallback;
        }

        return $theme;
    }

    protected function clearThemeCache(): void
    {
        unset($this->themes);
        unset($this->filteredThemes);
        unset($this->activeTheme);
    }

    /**
     * Get the active theme slug (site-aware when multisite is active).
     * When a site is selected, returns the site's theme. Falls back to global.
     */
    public function getActiveThemeSlug(): string
    {
        $context = $this->getMultisiteContext();
        if ($context && $context->theme) {
            return $context->theme;
        }

        return $this->getThemeManager()->getActiveTheme()->slug;
    }

    /**
     * Check if rollback is available.
     * Rollback only applies to global theme changes (config/theme.php writes).
     * Per-site theme changes are direct column updates with no rollback state.
     */
    public function canRollback(): bool
    {
        if ($this->getMultisiteContext()) {
            return false;
        }

        return $this->getThemeManager()->canRollback();
    }

    /**
     * Get the rollback theme slug
     */
    public function getRollbackSlug(): ?string
    {
        return $this->getThemeManager()->getRollbackSlug();
    }

    /**
     * Activate a theme
     */
    public function activateTheme(string $slug): void
    {
        $theme = Theme::find($slug);

        if (! $theme) {
            Notification::make()
                ->title('Theme not found')
                ->body("The theme '{$slug}' could not be found.")
                ->danger()
                ->send();

            return;
        }

        // Run preflight validation
        $validation = $this->getValidator()->preflightCheck($theme);

        if (! $validation->isValid) {
            Notification::make()
                ->title('Theme activation failed')
                ->body(implode("\n", $validation->errors))
                ->danger()
                ->send();

            return;
        }

        // Show warnings if any
        if ($validation->hasWarnings()) {
            foreach ($validation->warnings as $warning) {
                Notification::make()
                    ->title('Warning')
                    ->body($warning)
                    ->warning()
                    ->send();
            }
        }

        $context = $this->getMultisiteContext();

        if ($context) {
            // Per-site: update the site's theme column
            DB::table('tallcms_sites')
                ->where('id', $context->id)
                ->update(['theme' => $slug, 'updated_at' => now()]);

            // Run essential activation steps (same as setActiveTheme minus config file write)
            $manager = $this->getThemeManager();
            $manager->publishThemeAssets($theme);

            // Clear compiled views to prevent stale cached templates
            $compiledViewPath = config('view.compiled');
            if ($compiledViewPath && File::isDirectory($compiledViewPath)) {
                foreach (File::glob($compiledViewPath.'/*.php') as $view) {
                    File::delete($view);
                }
            }

            // Clear preset for this site (SiteSetting::set() is site-aware)
            SiteSetting::set('theme_default_preset', '', 'text', 'theme');

            Notification::make()
                ->title('Site theme updated')
                ->body("'{$theme->name}' is now active for {$context->name}.")
                ->success()
                ->send();

            $this->clearThemeCache();
        } elseif ($this->getThemeManager()->activateWithRollback($slug)) {
            // Global: write to config/theme.php with rollback support
            SiteSetting::set('theme_default_preset', '', 'text', 'theme');

            Notification::make()
                ->title('Theme activated')
                ->body("'{$theme->name}' is now the default theme.")
                ->success()
                ->send();

            $this->clearThemeCache();
        } else {
            Notification::make()
                ->title('Activation failed')
                ->body('Failed to activate theme. Please check the logs.')
                ->danger()
                ->send();
        }
    }

    /**
     * Rollback to the previous theme
     */
    public function rollbackTheme(): void
    {
        $rollbackSlug = $this->getRollbackSlug();

        if (! $rollbackSlug) {
            Notification::make()
                ->title('No rollback available')
                ->body('There is no previous theme to rollback to.')
                ->warning()
                ->send();

            return;
        }

        if ($this->getThemeManager()->rollbackToPrevious()) {
            Notification::make()
                ->title('Rollback successful')
                ->body("Reverted to previous theme: {$rollbackSlug}")
                ->success()
                ->send();

            // Clear the computed property cache so themes list re-evaluates
            $this->clearThemeCache();
        } else {
            Notification::make()
                ->title('Rollback failed')
                ->body('Failed to rollback to previous theme.')
                ->danger()
                ->send();
        }
    }

    /**
     * Preview a theme in a new tab
     */
    public function previewTheme(string $slug): void
    {
        $theme = Theme::find($slug);

        if (! $theme) {
            Notification::make()
                ->title('Theme not found')
                ->danger()
                ->send();

            return;
        }

        // Validate theme can be previewed (same checks as middleware)
        if ($theme->isPrebuilt() && ! $theme->isBuilt()) {
            Notification::make()
                ->title('Theme not built')
                ->body("Theme '{$theme->name}' has not been built. Run 'npm run build' in the theme directory first.")
                ->danger()
                ->send();

            return;
        }

        if (! $theme->meetsRequirements()) {
            $unmet = $theme->getUnmetRequirements();
            Notification::make()
                ->title('Theme requirements not met')
                ->body(implode("\n", $unmet))
                ->danger()
                ->send();

            return;
        }

        // Generate preview URL
        $previewUrl = url('/').'?theme_preview='.$slug;

        // Dispatch event to open in new tab
        $this->dispatch('open-preview', url: $previewUrl);

        Notification::make()
            ->title('Preview opened')
            ->body("Preview of '{$theme->name}' opened in new tab.")
            ->info()
            ->send();
    }

    /**
     * Change the default daisyUI preset for the active theme
     */
    public function changeDefaultPreset(string $preset): void
    {
        // Resolve theme directly from site-aware slug, not from global ThemeManager
        $activeTheme = Theme::find($this->getActiveThemeSlug());

        if (! $activeTheme || ! $activeTheme->supportsThemeController()) {
            return;
        }

        $availablePresets = $activeTheme->getDaisyUIPresets();
        if (! in_array($preset, $availablePresets)) {
            Notification::make()
                ->title('Invalid preset')
                ->body("The preset '{$preset}' is not available for this theme.")
                ->danger()
                ->send();

            return;
        }

        SiteSetting::set('theme_default_preset', $preset, 'text', 'theme', 'Default daisyUI preset for the active theme');

        Notification::make()
            ->title('Default preset updated')
            ->body("Default preset changed to '".ucfirst($preset)."'.")
            ->success()
            ->send();

        $this->clearThemeCache();
    }

    /**
     * Show theme details in modal
     */
    public function showThemeDetails(string $slug): void
    {
        $theme = Theme::find($slug);

        if (! $theme) {
            return;
        }

        $this->selectedTheme = $slug;
        $activeSlug = $this->getActiveThemeSlug();

        $this->themeDetails = [
            'name' => $theme->name,
            'slug' => $theme->slug,
            'version' => $theme->version,
            'description' => $theme->description,
            'author' => $theme->author,
            'authorUrl' => $theme->getAuthorUrl(),
            'homepage' => $theme->getHomepage(),
            'license' => $theme->getLicense(),
            'parent' => $theme->parent,
            'supports' => $theme->supports,
            'tailwind' => $theme->tailwind,
            'daisyui' => [
                'preset' => $theme->getDaisyUIPreset(),
                'prefersDark' => $theme->getDaisyUIPrefersDark(),
                'presets' => $theme->getDaisyUIPresets(),
                'custom' => $theme->hasCustomDaisyUITheme(),
                'colors' => $theme->getDaisyUIColors(),
            ],
            'path' => $theme->path,
            'compatibility' => $theme->getCompatibility(),
            'isBuilt' => $theme->isBuilt(),
            'isPrebuilt' => $theme->isPrebuilt(),
            'isActive' => $activeSlug === $theme->slug,
            'meetsRequirements' => $theme->meetsRequirements(),
            'unmetRequirements' => $theme->getUnmetRequirements(),
            'screenshot' => $theme->getScreenshotUrl(),
            'gallery' => $theme->getGalleryScreenshots(),
            'requiresLicense' => $theme->requiresLicense(),
            'licenseSlug' => $theme->getLicenseSlug(),
            'licenseStatus' => $theme->requiresLicense()
                ? ($this->licenseStatuses[$theme->getLicenseSlug()] ?? null)
                : null,
            'tags' => $theme->getTags(),
            'hasDarkMode' => $theme->supports('dark_mode'),
            'hasThemeController' => $theme->supportsThemeController(),
            'hasResponsive' => $theme->supports('responsive'),
            'hasAnimations' => $theme->supports('animations'),
            'purchaseUrl' => $theme->getPurchaseUrl(),
        ];

        $this->dispatch('open-modal', id: 'theme-details-modal');
    }

    /**
     * Close theme details modal
     */
    public function closeThemeDetails(): void
    {
        $this->selectedTheme = null;
        $this->themeDetails = null;
    }

    /**
     * Refresh theme list
     */
    public function refreshThemes(): void
    {
        $this->getThemeManager()->refreshCache();

        Notification::make()
            ->title('Themes refreshed')
            ->body('Theme list has been refreshed.')
            ->success()
            ->send();

        // Clear the computed property cache so themes list re-evaluates
        $this->clearThemeCache();
    }

    /**
     * Delete theme action with Filament confirmation modal
     */
    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete Theme')
            ->modalDescription(fn (array $arguments) => "Are you sure you want to delete the theme '{$arguments['name']}'? This action cannot be undone.")
            ->modalSubmitActionLabel('Yes, Delete Theme')
            ->action(function (array $arguments) {
                $slug = $arguments['slug'];
                $theme = Theme::find($slug);

                if (! $theme) {
                    Notification::make()
                        ->title('Theme not found')
                        ->body("The theme '{$slug}' could not be found.")
                        ->danger()
                        ->send();

                    return;
                }

                // Use the service method to delete
                $result = $this->getThemeManager()->deleteTheme($slug);

                if (! $result['success']) {
                    Notification::make()
                        ->title('Delete failed')
                        ->body($result['error'])
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Theme deleted')
                    ->body("'{$theme->name}' has been removed.")
                    ->success()
                    ->send();

                // Close the details modal
                $this->closeThemeDetails();

                // Refresh the theme list
                $this->clearThemeCache();
            });
    }

    // =========================================================================
    // License Methods
    // =========================================================================

    /**
     * Refresh all license statuses for themes that require licenses
     */
    protected function refreshLicenseStatuses(): void
    {
        $licenseService = app(PluginLicenseService::class);
        $this->licenseStatuses = [];

        $themes = $this->getThemeManager()->getAvailableThemes();

        foreach ($themes as $theme) {
            if ($theme->requiresLicense()) {
                $slug = $theme->getLicenseSlug();
                $status = $licenseService->getStatus($slug);

                // Merge purchase_url from theme.json as fallback
                if (empty($status['purchase_url']) && $theme->getPurchaseUrl()) {
                    $status['purchase_url'] = $theme->getPurchaseUrl();
                }

                $this->licenseStatuses[$slug] = $status;
            }
        }
    }

    /**
     * Refresh a single theme's license status
     */
    public function refreshLicenseStatus(string $licenseSlug): void
    {
        $licenseService = app(PluginLicenseService::class);

        $licenseService->clearCache($licenseSlug);
        $licenseService->isValid($licenseSlug);

        $this->refreshLicenseState();

        Notification::make()
            ->title('Status Refreshed')
            ->body('License status has been refreshed from the server.')
            ->success()
            ->send();
    }

    /**
     * Refresh license data and clear theme caches
     */
    protected function refreshLicenseState(): void
    {
        $this->refreshLicenseStatuses();
        $this->clearThemeCache();

        // Refresh open details modal if showing a licensable theme
        if ($this->themeDetails && ($this->themeDetails['requiresLicense'] ?? false)) {
            $this->showThemeDetails($this->themeDetails['slug']);
        }
    }

    /**
     * Activate a theme license
     */
    public function activateLicenseAction(): Action
    {
        return Action::make('activateLicense')
            ->label('Activate License')
            ->icon('heroicon-o-key')
            ->color('primary')
            ->modalHeading(fn (array $arguments) => "Activate License — {$arguments['name']}")
            ->modalDescription('Enter your license key from your purchase email.')
            ->form([
                TextInput::make('license_key')
                    ->label('License Key')
                    ->placeholder('XXXX-XXXX-XXXX-XXXX')
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $result = app(PluginLicenseService::class)->activate(
                    $arguments['licenseSlug'],
                    $data['license_key']
                );

                if ($result['valid']) {
                    Notification::make()
                        ->title('License Activated')
                        ->body('The license has been successfully activated!')
                        ->success()
                        ->send();
                } else {
                    if ($result['status'] === 'not_supported') {
                        Notification::make()
                            ->title('Theme Not Supported')
                            ->body('This theme does not support license activation.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Activation Failed')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }

                $this->refreshLicenseState();
            });
    }

    /**
     * Deactivate a theme license
     */
    public function deactivateLicenseAction(): Action
    {
        return Action::make('deactivateLicense')
            ->label('Deactivate')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => "Deactivate License — {$arguments['name']}")
            ->modalDescription('Are you sure you want to deactivate this license? The theme may lose access to updates and premium features.')
            ->modalSubmitActionLabel('Yes, Deactivate')
            ->action(function (array $arguments) {
                $result = app(PluginLicenseService::class)->deactivate($arguments['licenseSlug']);

                if ($result['success']) {
                    Notification::make()
                        ->title('License Deactivated')
                        ->body('The license has been deactivated from this site.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Deactivation Notice')
                        ->body($result['message'])
                        ->warning()
                        ->send();
                }

                $this->refreshLicenseState();
            });
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('rollback')
                ->label('Rollback to Previous')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn () => $this->canRollback())
                ->requiresConfirmation()
                ->modalHeading('Rollback Theme')
                ->modalDescription(fn () => "Are you sure you want to rollback to the previous theme ({$this->getRollbackSlug()})?")
                ->modalSubmitActionLabel('Yes, Rollback')
                ->action(fn () => $this->rollbackTheme()),

            Action::make('refreshAllLicenses')
                ->label('Refresh Licenses')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->action(function () {
                    $licenseService = app(PluginLicenseService::class);
                    $licenseService->clearCache();

                    foreach ($this->licenseStatuses as $licenseSlug => $status) {
                        if ($status['has_license']) {
                            $licenseService->isValid($licenseSlug);
                        }
                    }

                    $this->refreshLicenseState();

                    Notification::make()
                        ->title('All Statuses Refreshed')
                        ->success()
                        ->send();
                })
                ->visible(fn () => collect($this->licenseStatuses)->contains('has_license', true)),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshThemes()),

            // Theme Upload action
            Action::make('upload')
                ->label('Upload Theme')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => config('theme.allow_uploads', false))
                ->form([
                    FileUpload::make('theme_zip')
                        ->label('Theme Package (ZIP)')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(50 * 1024) // 50MB
                        ->required()
                        ->disk('local')
                        ->directory('theme-uploads')
                        ->helperText('Upload a theme package (.zip file). Maximum size: 50MB.'),
                ])
                ->action(function (array $data) {
                    // Server-side guard: verify uploads are enabled (visible() is UI-only)
                    if (! config('theme.allow_uploads', false)) {
                        Notification::make()
                            ->title('Uploads disabled')
                            ->body('Theme uploads are not enabled in configuration.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $uploadedFile = $data['theme_zip'];
                    $zipPath = Storage::disk('local')->path($uploadedFile);
                    $extractedSlug = null;

                    try {
                        // Step 1: Validate ZIP file
                        $validation = $this->getValidator()->validateZip($zipPath);

                        if (! $validation->isValid) {
                            Notification::make()
                                ->title('Invalid theme package')
                                ->body(implode("\n", $validation->errors))
                                ->danger()
                                ->send();

                            return;
                        }

                        // Show warnings if any
                        foreach ($validation->warnings as $warning) {
                            Notification::make()
                                ->title('Warning')
                                ->body($warning)
                                ->warning()
                                ->send();
                        }

                        $slug = $validation->themeData['slug'];

                        // Step 2: Check if theme already exists
                        if (File::exists(base_path("themes/{$slug}"))) {
                            Notification::make()
                                ->title('Theme already exists')
                                ->body("A theme with slug '{$slug}' already exists. Please remove it first or upload a theme with a different slug.")
                                ->danger()
                                ->send();

                            return;
                        }

                        // Step 3: Extract theme
                        $extractResult = $this->getThemeManager()->extractTheme($zipPath, $slug);

                        if (! $extractResult['success']) {
                            Notification::make()
                                ->title('Extraction failed')
                                ->body($extractResult['error'])
                                ->danger()
                                ->send();

                            return;
                        }

                        // Track extracted slug for cleanup on failure
                        $extractedSlug = $slug;

                        // Step 4: Validate extracted directory
                        $dirValidation = $this->getValidator()->validateDirectory(base_path("themes/{$slug}"));

                        if (! $dirValidation->isValid) {
                            // Cleanup extracted files on validation failure
                            File::deleteDirectory(base_path("themes/{$slug}"));
                            $extractedSlug = null;

                            Notification::make()
                                ->title('Theme validation failed')
                                ->body(implode("\n", $dirValidation->errors))
                                ->danger()
                                ->send();

                            return;
                        }

                        // Step 5: Install theme (publish assets)
                        $installResult = $this->getThemeManager()->installTheme($slug);

                        if ($installResult === false) {
                            // Cleanup on install failure (both theme dir and any published assets)
                            File::deleteDirectory(base_path("themes/{$slug}"));
                            $publicPath = public_path("themes/{$slug}");
                            if (File::exists($publicPath) || is_link($publicPath)) {
                                if (is_link($publicPath)) {
                                    unlink($publicPath);
                                } else {
                                    File::deleteDirectory($publicPath);
                                }
                            }
                            $extractedSlug = null;

                            Notification::make()
                                ->title('Installation failed')
                                ->body('Failed to publish theme assets. Check logs for details.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Success - clear the slug so finally block doesn't cleanup
                        $extractedSlug = null;

                        // Step 6: Refresh theme list
                        $this->refreshThemes();

                        Notification::make()
                            ->title('Theme uploaded successfully')
                            ->body("Theme '{$validation->themeData['name']}' has been installed. You can now activate it.")
                            ->success()
                            ->send();

                    } catch (\Throwable $e) {
                        // Cleanup on unexpected error (both theme dir and any published assets)
                        if ($extractedSlug) {
                            if (File::exists(base_path("themes/{$extractedSlug}"))) {
                                File::deleteDirectory(base_path("themes/{$extractedSlug}"));
                            }
                            $publicPath = public_path("themes/{$extractedSlug}");
                            if (File::exists($publicPath) || is_link($publicPath)) {
                                if (is_link($publicPath)) {
                                    unlink($publicPath);
                                } else {
                                    File::deleteDirectory($publicPath);
                                }
                            }
                        }

                        // Log the error for debugging but fail gracefully for the user
                        Log::error('Theme upload failed', [
                            'error' => $e->getMessage(),
                            'slug' => $extractedSlug,
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->title('Upload failed')
                            ->body('An unexpected error occurred: '.$e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        // Always cleanup uploaded file
                        Storage::disk('local')->delete($uploadedFile);
                    }
                }),
        ];
    }
}
