<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingOverridesRelationManager extends RelationManager
{
    protected static string $relationship = 'settingOverrides';

    protected static ?string $title = 'Setting Overrides';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-adjustments-horizontal';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Setting')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('value')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->value)
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('key')
            ->emptyStateHeading('No overrides')
            ->emptyStateDescription('This site inherits all settings from the global defaults.')
            ->emptyStateIcon('heroicon-o-globe-alt')
            ->recordActions([
                Action::make('reset')
                    ->label('Reset to Global')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Reset to Global Default')
                    ->modalDescription(fn ($record) => "Remove the override for '{$record->key}'? This site will inherit the global value.")
                    ->action(function ($record) {
                        // Use canonical settings API (handles cache invalidation)
                        $siteId = $record->site_id;
                        $key = $record->key;

                        // Temporarily set session so resetToGlobal targets this site
                        $previousSession = session('multisite_admin_site_id');
                        session(['multisite_admin_site_id' => $siteId]);

                        try {
                            \TallCms\Cms\Models\SiteSetting::resetToGlobal($key);
                        } finally {
                            session(['multisite_admin_site_id' => $previousSession]);
                        }

                        Notification::make()
                            ->title('Reset to global')
                            ->body("'{$key}' now inherits the global default.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
