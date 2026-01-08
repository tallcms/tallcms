<?php

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Tallcms\Pro\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| TallCMS Pro Public Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the PluginServiceProvider and wrapped in
| ['web', 'throttle:60,1'] middleware.
|
| IMPORTANT: External webhooks must disable CSRF via withoutMiddleware()
|
*/

Route::post('/tallcms-pro-webhook', [WebhookController::class, 'handleAnystack'])
    ->withoutMiddleware(VerifyCsrfToken::class);
