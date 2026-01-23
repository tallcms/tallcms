<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsCategories\CmsCategoryResource;

class CreateCmsCategory extends CreateRecord
{
    use Translatable;

    protected static string $resource = CmsCategoryResource::class;
}
