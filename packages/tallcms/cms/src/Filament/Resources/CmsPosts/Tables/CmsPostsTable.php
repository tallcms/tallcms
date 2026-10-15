<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Models\CmsCategory;

class CmsPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label(__('tallcms::fields.image'))
                    ->square()
                    ->imageSize(50)
                    ->disk(cms_media_disk()),

                TextColumn::make('title')->label(__('tallcms::fields.title'))
                    ->searchable()
                    ->limit(50),

                TextColumn::make('excerpt')->label(__('tallcms::fields.excerpt'))
                    ->searchable()
                    ->limit(60)
                    ->toggleable()
                    ->color('gray'),

                TextColumn::make('status')->label(__('tallcms::fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::from($state)->getLabel())
                    ->color(fn (string $state): string => ContentStatus::from($state)->getColor())
                    ->icon(fn (string $state): string => ContentStatus::from($state)->getIcon()),

                ToggleColumn::make('is_featured')
                    ->label(__('tallcms::fields.featured')),

                TagsColumn::make('categories.name')
                    ->label(tallcms_label('categories', 'plural'))
                    ->limit(3),

                TextColumn::make('author.name')
                    ->label(__('tallcms::fields.author'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('views')
                    ->label(__('tallcms::fields.views'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')->label(__('tallcms::fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')->label(__('tallcms::fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        ContentStatus::Draft->value => ContentStatus::Draft->getLabel(),
                        ContentStatus::Pending->value => ContentStatus::Pending->getLabel(),
                        ContentStatus::Published->value => ContentStatus::Published->getLabel(),
                    ]),

                SelectFilter::make('is_featured')
                    ->label(__('tallcms::fields.featured'))
                    ->options([
                        '1' => __('tallcms::fields.featured'),
                        '0' => __('tallcms::fields.not_featured'),
                    ]),

                self::categoryFilter(),

                SelectFilter::make('author')
                    ->relationship('author', 'name'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function categoryFilter(): SelectFilter
    {
        return SelectFilter::make('categories')
            ->label(tallcms_label('categories', 'plural'))
            ->multiple()
            ->options(fn (): array => CmsCategory::translatedSelectOptions())
            ->getSearchResultsUsing(fn (?string $search): array => CmsCategory::translatedSelectOptions(search: $search))
            ->getOptionLabelsUsing(fn (array $values): array => CmsCategory::translatedSelectOptions(ids: $values))
            ->query(function (Builder $query, array $data): Builder {
                $values = array_values(array_filter(
                    (array) ($data['values'] ?? []),
                    fn (mixed $value): bool => filled($value),
                ));

                if ($values === []) {
                    return $query;
                }

                return $query->whereHas(
                    'categories',
                    fn (Builder $categories): Builder => $categories->whereKey($values),
                );
            });
    }
}
