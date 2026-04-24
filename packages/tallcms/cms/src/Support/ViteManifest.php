<?php

declare(strict_types=1);

namespace TallCms\Cms\Support;

/**
 * Lightweight read-only Vite manifest probe.
 *
 * Lets package views guard `@vite()` calls so a missing entry in the
 * host app's manifest doesn't crash the page with ViteException. The
 * default behaviour of `@vite()` is to throw on a missing entry, which
 * is correct for first-party app code but wrong for plugin views — the
 * package can't know which entries the host's vite.config.js actually
 * builds.
 *
 * Used by the CMS rich editor view, which @vite()'s
 * resources/css/filament/admin/preview.css for the daisyUI block-preview
 * scoping. Standalone TallCMS ships that as a Vite entry; plugin-mode
 * adopters typically don't have it in their host config and rely on the
 * package's pre-built tallcms-preview.css (registered via FilamentAsset).
 */
class ViteManifest
{
    /**
     * Whether the host app's Vite manifest contains the given entry.
     *
     * Returns true in dev (`npm run dev`) mode regardless of the entry —
     * Vite's dev server resolves missing entries with a 404 instead of
     * a fatal exception, so deferring to `@vite()` is safe and avoids
     * silently dropping valid entries during development.
     */
    public static function hasEntry(string $path): bool
    {
        // Dev server hot mode — defer to @vite, which uses the dev URL.
        if (file_exists(public_path('hot'))) {
            return true;
        }

        $manifestPath = public_path('build/manifest.json');

        if (! file_exists($manifestPath)) {
            return false;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        return is_array($manifest) && isset($manifest[$path]);
    }
}
