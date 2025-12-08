<?php

namespace App\Filament\Resources\CmsPosts\Pages;

use App\Filament\Resources\CmsPosts\CmsPostResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCmsPost extends EditRecord
{
    protected static string $resource = CmsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
