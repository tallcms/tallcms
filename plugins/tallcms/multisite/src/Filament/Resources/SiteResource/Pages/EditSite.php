<?php

declare(strict_types=1);

namespace Tallcms\Multisite\Filament\Resources\SiteResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Tallcms\Multisite\Filament\Resources\SiteResource\SiteResource;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Delete')
                ->color('danger')
                ->icon('heroicon-m-trash')
                ->form([
                    Radio::make('content_action')
                        ->label('What should happen to this site\'s pages and menus?')
                        ->options([
                            'delete' => 'Delete all pages and menus belonging to this site',
                            'orphan' => 'Keep pages and menus (they will become unassigned)',
                        ])
                        ->default('delete')
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Delete Site')
                ->modalDescription(fn () => "Are you sure you want to delete \"{$this->record->name}\"? This cannot be undone.")
                ->action(function (array $data) {
                    $site = $this->record;

                    if ($data['content_action'] === 'delete') {
                        // Delete menu items first (through menus)
                        $menuIds = DB::table('tallcms_menus')->where('site_id', $site->id)->pluck('id');
                        DB::table('tallcms_menu_items')->whereIn('menu_id', $menuIds)->delete();
                        DB::table('tallcms_menus')->where('site_id', $site->id)->delete();
                        DB::table('tallcms_pages')->where('site_id', $site->id)->delete();
                    }
                    // 'orphan' = do nothing extra, FK nullOnDelete handles it

                    DB::table('tallcms_site_setting_overrides')->where('site_id', $site->id)->delete();
                    $site->delete();

                    Notification::make()
                        ->title('Site deleted')
                        ->body($data['content_action'] === 'delete'
                            ? "'{$site->name}' and all its content have been deleted."
                            : "'{$site->name}' deleted. Pages and menus are now unassigned.")
                        ->success()
                        ->send();

                    $this->redirect(SiteResource::getUrl());
                }),
        ];
    }
}
