<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tallcms\Multisite\Services\CurrentSiteResolver;

Route::post('/switch-site', function (Request $request) {
    $siteId = $request->input('site_id');

    $resolver = app(CurrentSiteResolver::class);
    $resolver->setAdminSite($siteId ? (int) $siteId : null);

    return redirect()->back();
})->middleware(['web', 'auth'])->name('multisite.switch-site');
