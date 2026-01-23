<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;

class CreateCmsPost extends CreateRecord
{
    use Translatable;

    protected static string $resource = CmsPostResource::class;
}
