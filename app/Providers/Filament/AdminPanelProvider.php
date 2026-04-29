<?php

namespace App\Providers\Filament;

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
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TallCms\Cms\Services\PluginLicenseService;
use TallCms\Cms\Services\ThemeResolver;
use TallCms\Cms\TallCmsPlugin;
use Tallcms\FilamentRegistration\Filament\Pages\Register;
use Tallcms\Registration\Filament\RegistrationPlugin as TallcmsRegistrationBridge;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // The renderHook below syncs Filament's dark mode with DaisyUI's data-theme attribute.
            // Note: preview.css (daisyUI for block previews) is loaded per-page via CmsRichEditor,
            // NOT globally here — daisyUI's base styles conflict with Filament's dark mode.
            ->viteTheme('resources/css/filament/admin/theme.css')
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
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification(isRequired: fn () => (bool) config('registration.email_verification.enabled'))
            ->emailChangeVerification(fn () => (bool) config('registration.email_verification.enabled'))
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
            ->plugins(array_filter([
                TallCmsPlugin::make(),
                // Registration bridge ships as a local plugin under /plugins/tallcms/registration/.
                // Guard so fresh checkouts (CI release builds, plugin-mode users) without the
                // plugin installed don't crash on a missing class reference.
                class_exists(TallcmsRegistrationBridge::class) ? TallcmsRegistrationBridge::make() : null,
            ]))
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
