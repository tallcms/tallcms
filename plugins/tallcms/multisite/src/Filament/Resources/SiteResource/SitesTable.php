<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Tallcms\Multisite\Models\Site;
use Tallcms\Multisite\Services\SiteCloneService;

class SitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('domain')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->color('gray'),

                TextColumn::make('theme')
                    ->placeholder('Global')
                    ->sortable(),

                TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Site $record): string {
                        if ($record->is_active && $record->is_default) {
                            return 'Primary';
                        }
                        if ($record->is_active) {
                            return 'Active';
                        }

                        return 'Inactive';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Primary' => 'success',
                        'Active' => 'info',
                        'Inactive' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('pages_count')
                    ->label('Pages')
                    ->counts('pages')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('menus_count')
                    ->label('Menus')
                    ->counts('menus')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('setting_overrides_count')
                    ->label('Overrides')
                    ->counts('settingOverrides')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->getStateUsing(function (Site $record): ?string {
                        $dates = array_filter([
                            $record->last_page_activity,
                            $record->last_menu_activity,
                            $record->last_menu_item_activity,
                        ]);

                        return $dates ? max($dates) : null;
                    })
                    ->dateTime()
                    ->since()
                    ->placeholder('No activity'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('No sites yet')
            ->emptyStateDescription('Create your first site to get started.')
            ->emptyStateIcon('heroicon-o-globe-alt')
            ->recordActions([
                Action::make('switch')
                    ->label('Switch to')
                    ->icon('heroicon-m-arrow-right-circle')
                    ->color('primary')
                    ->action(function (Site $record) {
                        session(['multisite_admin_site_id' => $record->id]);

                        // Track in recent sites
                        $recent = session('multisite_recent_sites', []);
                        $recent = array_filter($recent, fn ($id) => $id !== $record->id);
                        array_unshift($recent, $record->id);
                        session(['multisite_recent_sites' => array_slice($recent, 0, 5)]);

                        Notification::make()
                            ->title("Switched to {$record->name}")
                            ->success()
                            ->send();

                        return redirect(url(config('tallcms.filament.panel_path', 'admin')));
                    }),

                EditAction::make(),

                Action::make('clone')
                    ->icon('heroicon-m-document-duplicate')
                    ->label('Clone')
                    ->color('gray')
                    ->form([
                        TextInput::make('name')
                            ->label('New Site Name')
                            ->required()
                            ->maxLength(255)
                            ->default(fn (Site $record) => $record->name.' (Copy)'),
                        TextInput::make('domain')
                            ->label('New Domain')
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? Site::normalizeDomain($state) : $state)
                            ->rules([
                                fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                    $normalized = Site::normalizeDomain($value);
                                    if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $normalized)) {
                                        $fail('Enter a valid domain name (e.g. example.com). No protocol, port, or path.');

                                        return;
                                    }
                                    if (Site::where('domain', $normalized)->exists()) {
                                        $fail("The domain '{$normalized}' is already in use.");
                                    }
                                },
                            ])
                            ->helperText('e.g. new-site.example.com — lowercase, no protocol or port'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Clone Site')
                    ->modalDescription('This will create a near-exact duplicate including all pages, menus, site settings (name, branding, contact info, SEO), and theme assignment. You will need to update the cloned site\'s identity settings afterward.')
                    ->modalSubmitActionLabel('Clone Site')
                    ->action(function (Site $record, array $data) {
                        try {
                            app(SiteCloneService::class)->clone(
                                $record,
                                $data['name'],
                                $data['domain']
                            );

                            Notification::make()
                                ->title('Site cloned successfully')
                                ->body("'{$data['name']}' has been created with all content from '{$record->name}'.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Clone failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\Radio::make('content_action')
                            ->label('What should happen to this site\'s pages and menus?')
                            ->options([
                                'delete' => 'Delete all pages and menus',
                                'orphan' => 'Keep pages and menus (unassigned)',
                            ])
                            ->default('delete')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Delete Site')
                    ->modalDescription(fn (Site $record) => "Are you sure you want to delete \"{$record->name}\"?")
                    ->action(function (Site $record, array $data) {
                        if ($data['content_action'] === 'delete') {
                            $menuIds = \Illuminate\Support\Facades\DB::table('tallcms_menus')->where('site_id', $record->id)->pluck('id');
                            \Illuminate\Support\Facades\DB::table('tallcms_menu_items')->whereIn('menu_id', $menuIds)->delete();
                            \Illuminate\Support\Facades\DB::table('tallcms_menus')->where('site_id', $record->id)->delete();
                            \Illuminate\Support\Facades\DB::table('tallcms_pages')->where('site_id', $record->id)->delete();
                        }

                        \Illuminate\Support\Facades\DB::table('tallcms_site_setting_overrides')->where('site_id', $record->id)->delete();
                        $record->delete();

                        Notification::make()
                            ->title('Site deleted')
                            ->body($data['content_action'] === 'delete'
                                ? "'{$record->name}' and all its content have been deleted."
                                : "'{$record->name}' deleted. Pages and menus are now unassigned.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
