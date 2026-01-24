<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use TallCms\Cms\Filament\Concerns\HasTranslationCopying;
use TallCms\Cms\Filament\Resources\CmsCategories\CmsCategoryResource;

class EditCmsCategory extends EditRecord
{
    use Translatable, HasTranslationCopying {
        HasTranslationCopying::updatedActiveLocale insteadof Translatable;
    }

    protected static string $resource = CmsCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            $this->getCopyFromDefaultAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
