<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use TallCms\Cms\Filament\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
