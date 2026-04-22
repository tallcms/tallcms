<?php

namespace TallCms\Cms\Filament\Resources\CmsPages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Schema;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;

class CreateCmsPage extends CreateRecord
{
    use Translatable;

    protected static string $resource = CmsPageResource::class;

    /**
     * Owning site id, captured from the ?site=<id> query parameter attached by
     * the Site resource's Pages relation manager. Persisted as a Livewire
     * property so it survives the Livewire update cycle that runs on save —
     * request()->query() on /livewire/update has no access to the original
     * page URL's query string.
     */
    public ?int $ownerSiteId = null;

    public function mount(): void
    {
        parent::mount();

        $siteParam = request()->query('site');
        if ($siteParam !== null && is_numeric($siteParam)) {
            $this->ownerSiteId = (int) $siteParam;
        }
    }

    /**
     * Set site_id from the captured query param so the new page is created
     * explicitly scoped to the owning site — no ambient admin-session scoping.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->ownerSiteId !== null
            && Schema::hasColumn('tallcms_pages', 'site_id')
            && empty($data['site_id'])
        ) {
            $data['site_id'] = $this->ownerSiteId;
        }

        return $data;
    }
}
