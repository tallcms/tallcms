<?php

namespace App\Providers\Filament;

use App\Services\PluginManager;
use App\Services\ThemeResolver;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->multiFactorAuthentication([
                AppAuthentication::make(),
            ])
            ->colors([
                'primary' => ThemeResolver::getCurrentTheme()->getColorPalette()['primary'],
                'secondary' => ThemeResolver::getCurrentTheme()->getColorPalette()['secondary'],
                'success' => ThemeResolver::getCurrentTheme()->getColorPalette()['success'],
                'warning' => ThemeResolver::getCurrentTheme()->getColorPalette()['warning'],
                'danger' => ThemeResolver::getCurrentTheme()->getColorPalette()['danger'],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins($this->getFilamentPlugins())
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('View Site')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-globe-alt'),
            ]);
    }

    /**
     * Get all Filament plugins including those from installed plugins
     */
    protected function getFilamentPlugins(): array
    {
        // Core plugins
        $plugins = [
            FilamentShieldPlugin::make()
                ->navigationGroup('User Management'),
        ];

        // Add plugins from installed TallCMS plugins
        try {
            $pluginManager = app(PluginManager::class);
            $pluginPlugins = $pluginManager->getFilamentPlugins();
            $plugins = array_merge($plugins, $pluginPlugins);
        } catch (\Throwable $e) {
            // Log but don't fail if plugin loading fails
            \Illuminate\Support\Facades\Log::warning('Failed to load Filament plugins from installed plugins', [
                'error' => $e->getMessage(),
            ]);
        }

        return $plugins;
    }
}
