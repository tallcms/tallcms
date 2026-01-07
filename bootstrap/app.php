<?php

// Handle installer bootstrap before Laravel fully loads
require __DIR__.'/installer.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Minimal middleware for installer - no database dependencies
            Route::middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            ])->group(base_path('routes/installer.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'maintenance.mode' => \App\Http\Middleware\MaintenanceModeMiddleware::class,
            'installer.gate' => \App\Http\Middleware\InstallerGate::class,
            'theme.preview' => \App\Http\Middleware\ThemePreviewMiddleware::class,
        ]);

        // Add theme preview middleware to web group
        $middleware->web(append: [
            \App\Http\Middleware\ThemePreviewMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
