<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Forms;

use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Models\CmsPage;

/**
 * Helper for Filament pickers that need to list pages belonging to the
 * same site as the record currently being edited.
 *
 * After the multisite refactor removed the ambient session-based scope,
 * raw `CmsPage::where(...)->pluck(...)` calls inside block forms and the
 * page form would list pages across all sites. These helpers thread the
 * owning site's id through explicitly.
 *
 * Fallback rule: if no owner site can be resolved from the Livewire
 * context (e.g. console, unexpected component state), return an empty
 * option set rather than leaking cross-site data. Callers can surface
 * that as an empty dropdown with an explanatory helper text.
 */
class OwnerSitePicker
{
    /**
     * Resolve the site_id owning the record currently being edited.
     *
     * Handles both edit and create flows:
     *  - Edit: `$livewire->record` is set; read its site_id.
     *  - Create: `$livewire->ownerSiteId` is set by the create page's
     *    mount() (see CreateCmsPage), captured from the ?site=<id> URL.
     *
     * Returns null when no owner context is available (test helpers
     * calling the schema in isolation, or non-multisite installs where
     * site_id isn't meaningful).
     */
    public static function resolveOwnerSiteId(mixed $livewire): ?int
    {
        if ($livewire === null) {
            return null;
        }

        $ownerSiteId = data_get($livewire, 'ownerSiteId');
        if (is_numeric($ownerSiteId)) {
            return (int) $ownerSiteId;
        }

        $record = data_get($livewire, 'record');
        if ($record && isset($record->site_id) && is_numeric($record->site_id)) {
            return (int) $record->site_id;
        }

        return null;
    }

    /**
     * Build `[id => title]` options for a page picker scoped to the
     * owning site. Filters to `status = 'published'` and resolves
     * translatable titles through the model accessor so JSON columns
     * don't render as `[object Object]`.
     *
     * If multisite isn't active (no `site_id` column on the pages
     * table), returns all published pages — single-site behaviour is
     * unchanged.
     */
    public static function publishedPages(mixed $livewire): array
    {
        $query = CmsPage::withoutGlobalScopes()->where('status', 'published');

        if (Schema::hasColumn('tallcms_pages', 'site_id')) {
            $ownerSiteId = static::resolveOwnerSiteId($livewire);

            if ($ownerSiteId === null) {
                // Safe fallback — no context resolved, no options shown.
                // Preferable to a default-site fallback that would leak
                // cross-site pages into the picker.
                return [];
            }

            $query->where('site_id', $ownerSiteId);
        }

        return $query->get()
            ->mapWithKeys(fn (CmsPage $page) => [
                $page->id => (string) ($page->title ?: __('Untitled')),
            ])
            ->all();
    }

    /**
     * Build `[id => title]` options for the "Parent Page" picker on the
     * page form. Scoped to the same site, limited to top-level pages, and
     * excludes the current record (a page can't be its own parent).
     */
    public static function parentPageOptions(mixed $livewire): array
    {
        $query = CmsPage::withoutGlobalScopes()->whereNull('parent_id');

        if (Schema::hasColumn('tallcms_pages', 'site_id')) {
            $ownerSiteId = static::resolveOwnerSiteId($livewire);

            if ($ownerSiteId === null) {
                return [];
            }

            $query->where('site_id', $ownerSiteId);
        }

        $currentRecord = data_get($livewire, 'record');
        if ($currentRecord && isset($currentRecord->id)) {
            $query->where('id', '!=', $currentRecord->id);
        }

        return $query->get()
            ->mapWithKeys(fn (CmsPage $page) => [
                $page->id => (string) ($page->title ?: __('Untitled')),
            ])
            ->all();
    }
}
