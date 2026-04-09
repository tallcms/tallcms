<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Services;

use Illuminate\Support\Facades\DB;
use Tallcms\Multisite\Models\Site;

class SiteCloneService
{
    /**
     * Clone a site with all its pages, menus, menu items, and setting overrides.
     *
     * Runs in a transaction. Uses raw DB queries to bypass model events
     * (no revisions, no search indexing, no homepage conflicts).
     */
    public function clone(Site $source, string $newName, string $newDomain): Site
    {
        $newSite = DB::transaction(function () use ($source, $newName, $newDomain) {
            $newSite = $this->cloneSiteRecord($source, $newName, $newDomain);
            $this->cloneSettingOverrides($source->id, $newSite->id);
            $pageIdMap = $this->clonePages($source->id, $newSite->id);
            $this->cloneMenus($source->id, $newSite->id, $pageIdMap);

            return $newSite;
        });

        // Rebuild search index for cloned pages (raw DB inserts bypass model events)
        $this->reindexClonedPages($newSite->id);

        return $newSite;
    }

    /**
     * Reindex cloned pages for search. Raw DB::table() inserts bypass
     * model events, so Scout observers never fire.
     */
    protected function reindexClonedPages(int $siteId): void
    {
        try {
            $pages = \TallCms\Cms\Models\CmsPage::withoutGlobalScopes()
                ->where('site_id', $siteId)
                ->get();

            foreach ($pages as $page) {
                if (method_exists($page, 'searchable')) {
                    $page->searchable();
                }
            }
        } catch (\Throwable) {
            // Search not configured or Scout not installed — skip silently
        }
    }

    protected function cloneSiteRecord(Site $source, string $newName, string $newDomain): Site
    {
        return Site::create([
            'name' => $newName,
            'domain' => Site::normalizeDomain($newDomain),
            'theme' => $source->theme,
            'locale' => $source->locale,
            'user_id' => $source->user_id, // Preserve ownership
            'is_default' => false,
            'is_active' => true,
            'metadata' => $source->metadata,
        ]);
    }

    protected function cloneSettingOverrides(int $sourceId, int $newSiteId): void
    {
        $overrides = DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $sourceId)
            ->get();

        foreach ($overrides as $override) {
            DB::table('tallcms_site_setting_overrides')->insert([
                'site_id' => $newSiteId,
                'key' => $override->key,
                'value' => $override->value,
                'type' => $override->type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Clone all pages for a site, preserving parent-child hierarchy.
     *
     * @return array<int, int> Map of old page ID → new page ID
     */
    protected function clonePages(int $sourceId, int $newSiteId): array
    {
        $pages = DB::table('tallcms_pages')
            ->where('site_id', $sourceId)
            ->whereNull('deleted_at')
            ->orderByRaw('parent_id IS NOT NULL, parent_id ASC, sort_order ASC')
            ->get();

        $idMap = [];

        // First pass: clone top-level pages (no parent)
        foreach ($pages->where('parent_id', null) as $page) {
            $idMap[$page->id] = $this->clonePage($page, $newSiteId, null);
        }

        // Second pass: clone child pages with remapped parent_id
        foreach ($pages->whereNotNull('parent_id') as $page) {
            $newParentId = $idMap[$page->parent_id] ?? null;
            $idMap[$page->id] = $this->clonePage($page, $newSiteId, $newParentId);
        }

        return $idMap;
    }

    protected function clonePage(object $page, int $newSiteId, ?int $newParentId): int
    {
        return DB::table('tallcms_pages')->insertGetId([
            'site_id' => $newSiteId,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'search_content' => $page->search_content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'featured_image' => $page->featured_image,
            'status' => $page->status,
            'is_homepage' => $page->is_homepage,
            'published_at' => $page->published_at,
            'parent_id' => $newParentId,
            'sort_order' => $page->sort_order,
            'show_breadcrumbs' => $page->show_breadcrumbs,
            'template' => $page->template,
            'sidebar_widgets' => $page->sidebar_widgets,
            'content_width' => $page->content_width,
            'author_id' => $page->author_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Clone all menus and their items for a site.
     *
     * @param  array<int, int>  $pageIdMap  Map of old page ID → new page ID
     */
    protected function cloneMenus(int $sourceId, int $newSiteId, array $pageIdMap): void
    {
        $menus = DB::table('tallcms_menus')
            ->where('site_id', $sourceId)
            ->get();

        foreach ($menus as $menu) {
            $newMenuId = DB::table('tallcms_menus')->insertGetId([
                'site_id' => $newSiteId,
                'name' => $menu->name,
                'location' => $menu->location,
                'description' => $menu->description,
                'is_active' => $menu->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->cloneMenuItems($menu->id, $newMenuId, $pageIdMap);
        }
    }

    /**
     * Clone menu items, remap parent_id and page_id, then rebuild nested set.
     */
    protected function cloneMenuItems(int $sourceMenuId, int $newMenuId, array $pageIdMap): void
    {
        $items = DB::table('tallcms_menu_items')
            ->where('menu_id', $sourceMenuId)
            ->orderBy('_lft')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $itemIdMap = [];

        // First pass: insert all items with temporary parent_id = null
        foreach ($items as $item) {
            $newItemId = DB::table('tallcms_menu_items')->insertGetId([
                'menu_id' => $newMenuId,
                'label' => $item->label,
                'type' => $item->type,
                'page_id' => isset($pageIdMap[$item->page_id]) ? $pageIdMap[$item->page_id] : $item->page_id,
                'url' => $item->url,
                'meta' => $item->meta,
                'is_active' => $item->is_active,
                'parent_id' => null,
                '_lft' => $item->_lft,
                '_rgt' => $item->_rgt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $itemIdMap[$item->id] = $newItemId;
        }

        // Second pass: remap parent_id using the item ID map
        foreach ($items as $item) {
            if ($item->parent_id && isset($itemIdMap[$item->parent_id])) {
                DB::table('tallcms_menu_items')
                    ->where('id', $itemIdMap[$item->id])
                    ->update(['parent_id' => $itemIdMap[$item->parent_id]]);
            }
        }

        // Rebuild nested set values for the new menu
        // NodeTrait's fixTree() uses the scope attribute (menu_id)
        \TallCms\Cms\Models\TallcmsMenuItem::scoped(['menu_id' => $newMenuId])->fixTree();
    }
}
