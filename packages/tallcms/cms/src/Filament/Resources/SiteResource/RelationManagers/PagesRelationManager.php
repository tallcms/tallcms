<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Filament\Resources\CmsPages\CmsPageResource;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static ?string $title = 'Pages';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-text';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('slug')
                    ->searchable()
                    ->limit(30)
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContentStatus::from($state)->getLabel())
                    ->color(fn (string $state): string => ContentStatus::from($state)->getColor()),

                IconColumn::make('is_homepage')
                    ->label('Home')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Action::make('create_page')
                    ->label('Create Page')
                    ->icon('heroicon-m-plus')
                    ->action(function () {
                        // Set admin context to this site before redirecting
                        $siteId = $this->getOwnerRecord()->id;
                        session(['multisite_admin_site_id' => $siteId]);

                        $this->redirect(CmsPageResource::getUrl('create'));
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => CmsPageResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
