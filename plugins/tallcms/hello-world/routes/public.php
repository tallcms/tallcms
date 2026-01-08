<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plugin Public Routes
|--------------------------------------------------------------------------
|
| These routes are NOT prefixed and are available at the root level.
| Make sure to declare them in plugin.json under "public_routes".
|
| WARNING: Be careful not to conflict with existing routes.
|
*/

Route::get('/hello', function () {
    return view('tallcms-helloworld::hello');
})->name('hello');
