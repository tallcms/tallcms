<?php

namespace TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\Pages;

use Filament\Resources\Pages\ListRecords;
use TallCms\Cms\Filament\Resources\TallcmsContactSubmissions\TallcmsContactSubmissionResource;

class ListTallcmsContactSubmissions extends ListRecords
{
    protected static string $resource = TallcmsContactSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - submissions come from the frontend
        ];
    }
}
