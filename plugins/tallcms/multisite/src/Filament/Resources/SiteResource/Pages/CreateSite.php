<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Tallcms\Multisite\Filament\Resources\SiteResource\SiteResource;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;
}
