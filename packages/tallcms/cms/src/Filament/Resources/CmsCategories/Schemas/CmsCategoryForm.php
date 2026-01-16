<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories\Schemas;

use TallCms\Cms\Models\CmsCategory;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CmsCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, ?string $old, callable $set) => $set('slug', Str::slug($state))
                    ),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(CmsCategory::class, 'slug', ignoreRecord: true)
                    ->rules(['alpha_dash'])
                    ->helperText('Used in the URL. Only letters, numbers, hyphens and underscores allowed.'),

                Select::make('parent_id')
                    ->label('Parent Category')
                    ->options(CmsCategory::query()
                        ->whereNull('parent_id')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                ColorPicker::make('color')
                    ->label('Category Color')
                    ->nullable()
                    ->helperText('Optional color for visual organization'),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(1),

                Textarea::make('description')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
