<?php

namespace App\Filament\Pages;

use App\Models\Theme;
use App\Services\ThemeManager as ThemeManagerService;
use App\Services\ThemeValidator;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ThemeManager extends Page implements HasForms
{
    use InteractsWithForms, HasPageShield;

    protected static ?string $title = 'Theme Manager';
    protected string $view = 'filament.pages.theme-manager';

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
        return 'Appearance';
    }

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public ?string $selectedTheme = null;
    public ?array $themeDetails = null;

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
            ])
            ->sortByDesc('isActive')
            ->values();
    }

    /**
     * Get the active theme slug
     */
    public function getActiveThemeSlug(): string
    {
        return $this->getThemeManager()->getActiveTheme()->slug;
    }

    /**
     * Check if rollback is available
     */
    public function canRollback(): bool
    {
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

        if (!$theme) {
            Notification::make()
                ->title('Theme not found')
                ->body("The theme '{$slug}' could not be found.")
                ->danger()
                ->send();
            return;
        }

        // Run preflight validation
        $validation = $this->getValidator()->preflightCheck($theme);

        if (!$validation->isValid) {
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

        // Activate theme with rollback support
        if ($this->getThemeManager()->activateWithRollback($slug)) {
            Notification::make()
                ->title('Theme activated')
                ->body("'{$theme->name}' is now active.")
                ->success()
                ->send();

            // Clear the computed property cache so themes list re-evaluates
            unset($this->themes);
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

        if (!$rollbackSlug) {
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
            unset($this->themes);
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

        if (!$theme) {
            Notification::make()
                ->title('Theme not found')
                ->danger()
                ->send();
            return;
        }

        // Validate theme can be previewed (same checks as middleware)
        if ($theme->isPrebuilt() && !$theme->isBuilt()) {
            Notification::make()
                ->title('Theme not built')
                ->body("Theme '{$theme->name}' has not been built. Run 'npm run build' in the theme directory first.")
                ->danger()
                ->send();
            return;
        }

        if (!$theme->meetsRequirements()) {
            $unmet = $theme->getUnmetRequirements();
            Notification::make()
                ->title('Theme requirements not met')
                ->body(implode("\n", $unmet))
                ->danger()
                ->send();
            return;
        }

        // Generate preview URL
        $previewUrl = url('/') . '?theme_preview=' . $slug;

        // Dispatch event to open in new tab
        $this->dispatch('open-preview', url: $previewUrl);

        Notification::make()
            ->title('Preview opened')
            ->body("Preview of '{$theme->name}' opened in new tab.")
            ->info()
            ->send();
    }

    /**
     * Show theme details in modal
     */
    public function showThemeDetails(string $slug): void
    {
        $theme = Theme::find($slug);

        if (!$theme) {
            return;
        }

        $this->selectedTheme = $slug;
        $activeTheme = $this->getThemeManager()->getActiveTheme();

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
            'path' => $theme->path,
            'compatibility' => $theme->getCompatibility(),
            'isBuilt' => $theme->isBuilt(),
            'isPrebuilt' => $theme->isPrebuilt(),
            'isActive' => $activeTheme && $activeTheme->slug === $theme->slug,
            'meetsRequirements' => $theme->meetsRequirements(),
            'unmetRequirements' => $theme->getUnmetRequirements(),
            'screenshot' => $theme->getScreenshotUrl(),
            'gallery' => $theme->getGalleryScreenshots(),
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
        unset($this->themes);
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

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshThemes()),

            // Phase 2: Upload action (hidden by default)
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
                        ->helperText('Upload a theme package (.zip file). Maximum size: 50MB.'),
                ])
                ->action(function (array $data) {
                    // Phase 2 implementation
                    Notification::make()
                        ->title('Upload feature')
                        ->body('Theme upload will be available in a future update.')
                        ->info()
                        ->send();
                }),
        ];
    }
}
