<?php

namespace App\Filament\Resources\CmsCategories\Pages;

use App\Filament\Resources\CmsCategories\CmsCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsCategory extends CreateRecord
{
    protected static string $resource = CmsCategoryResource::class;
}
