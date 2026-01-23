<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories\Schemas;

use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Rules\UniqueTranslatableSlug;
use TallCms\Cms\Services\LocaleRegistry;
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
                    ->required(function ($livewire) {
                        if (! tallcms_i18n_enabled()) {
                            return true;
                        }
                        // Require name for default locale when i18n enabled
                        $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                        $defaultLocale = app(LocaleRegistry::class)->getDefaultLocale();

                        return $activeLocale === $defaultLocale;
                    })
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, ?string $old, callable $set) => $set('slug', Str::slug($state))
                    ),

                TextInput::make('slug')
                    ->required(function ($livewire) {
                        if (! tallcms_i18n_enabled()) {
                            return true;
                        }
                        // Require slug for default locale when i18n enabled
                        $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                        $defaultLocale = app(LocaleRegistry::class)->getDefaultLocale();

                        return $activeLocale === $defaultLocale;
                    })
                    ->maxLength(255)
                    ->rules(function (?CmsCategory $record, $livewire) {
                        $rules = ['alpha_dash'];

                        if (tallcms_i18n_enabled()) {
                            // Block locale codes as slugs
                            $reserved = app(LocaleRegistry::class)->getReservedSlugs();
                            $rules[] = 'not_in:'.implode(',', $reserved);

                            // Unique per locale
                            $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                            $rules[] = new UniqueTranslatableSlug(
                                table: 'tallcms_categories',
                                column: 'slug',
                                locale: $activeLocale,
                                ignoreId: $record?->id
                            );
                        } else {
                            // Traditional unique constraint
                            $rules[] = 'unique:tallcms_categories,slug'.($record ? ','.$record->id : '');
                        }

                        return $rules;
                    })
                    ->validationMessages([
                        'not_in' => 'This slug is reserved (matches a language code).',
                    ])
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
