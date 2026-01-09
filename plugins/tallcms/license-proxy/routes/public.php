<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Tallcms\LicenseProxy\Http\Controllers\LicenseProxyController;

// Public routes for license proxy - exempt from CSRF for external API calls
Route::post('/license-proxy/activate', [LicenseProxyController::class, 'activate'])
    ->withoutMiddleware(ValidateCsrfToken::class);

Route::post('/license-proxy/validate', [LicenseProxyController::class, 'validate'])
    ->withoutMiddleware(ValidateCsrfToken::class);

Route::post('/license-proxy/deactivate', [LicenseProxyController::class, 'deactivate'])
    ->withoutMiddleware(ValidateCsrfToken::class);
