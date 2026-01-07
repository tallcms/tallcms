<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Plugin Web Routes (Prefixed)
|--------------------------------------------------------------------------
|
| These routes are automatically prefixed with /_plugins/{vendor}/{slug}
| and have the 'web' middleware applied.
|
*/

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});
