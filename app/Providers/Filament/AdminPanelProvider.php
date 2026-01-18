<?php

namespace App\Providers\Filament;

use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\ThemeResolver;
use TallCms\Cms\TallCmsPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
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
            // preview.css loads daisyUI for block previews.
            // The renderHook below syncs Filament's dark mode with DaisyUI's data-theme attribute.
            ->viteTheme([
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/admin/preview.css',
            ])
            // Sync Filament's dark mode class with DaisyUI's data-theme attribute
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString(<<<'HTML'
                <script>
                    // Sync Filament dark mode (.dark class) with DaisyUI (data-theme attribute)
                    (function() {
                        function syncDarkMode() {
                            const isDark = document.documentElement.classList.contains('dark');
                            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
                        }
                        // Initial sync
                        syncDarkMode();
                        // Watch for changes
                        const observer = new MutationObserver(syncDarkMode);
                        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                    })();
                </script>
                HTML)
            )
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
            ->pages([
                Dashboard::class,
            ])
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
            ->plugins([
                TallCmsPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('View Site')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-globe-alt'),
            ])
            ->bootUsing(function () {
                // Trigger automatic update check on admin panel load (rate-limited)
                try {
                    app(PluginLicenseService::class)->checkForUpdatesAutomatically();
                } catch (\Throwable $e) {
                    // Don't let update check failures break the admin panel
                    \Illuminate\Support\Facades\Log::debug('Auto update check skipped', [
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }

}
