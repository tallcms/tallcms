<?php

namespace TallCms\Cms\Filament\Resources\CmsPages\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;

class CreateCmsPage extends CreateRecord
{
    use Translatable;

    protected static string $resource = CmsPageResource::class;
}
