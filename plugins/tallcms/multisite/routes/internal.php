<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/internal/tls/verify', function (Request $request) {
    $expectedToken = config('tallcms.multisite.tls_verify_token');
    if ($expectedToken && $request->header('X-Internal-Token') !== $expectedToken) {
        return response('Unauthorized', 401);
    }

    $domain = $request->query('domain');
    if (! $domain || ! is_string($domain)) {
        return response('Bad request', 400);
    }

    // Fail closed: any error returns 503, never a false positive.
    try {
        if (! class_exists(\Tallcms\Multisite\Models\Site::class)) {
            return response('Multisite not installed', 503);
        }

        if (! Schema::hasTable('tallcms_sites')) {
            return response('Multisite not migrated', 503);
        }

        $site = \Tallcms\Multisite\Models\Site::findVerifiedByDomain($domain);

        return $site
            ? response('OK', 200)
            : response('Not found', 404);
    } catch (\Throwable) {
        return response('Service unavailable', 503);
    }
});
