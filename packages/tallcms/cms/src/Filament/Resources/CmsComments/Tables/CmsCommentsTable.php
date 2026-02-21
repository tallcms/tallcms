<?php

namespace TallCms\Cms\Filament\Resources\CmsComments\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CmsCommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('status')
                    ->label('')
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        'spam' => 'heroicon-o-shield-exclamation',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'spam' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('author_display')
                    ->label('Author')
                    ->state(fn ($record) => $record->getAuthorName() ?? 'Anonymous')
                    ->description(fn ($record) => $record->getAuthorEmail())
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('author_name', 'like', "%{$search}%")
                                ->orWhere('author_email', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('content')
                    ->limit(80)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('post.title')
                    ->label('Post')
                    ->limit(40)
                    ->sortable()
                    ->url(fn ($record) => $record->post ? route(
                        'filament.'.config('tallcms.filament.panel_id', 'admin').'.resources.cms-posts.edit',
                        $record->post
                    ) : null),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'spam' => 'Spam',
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->isPending() && auth()->user()?->can('Approve:CmsComment'))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->approve(auth()->user())),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => ($record->isPending() || $record->isApproved()) && auth()->user()?->can('Reject:CmsComment'))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->reject()),

                Action::make('unreject')
                    ->label('Unreject')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn ($record) => $record->isRejected() && auth()->user()?->can('Reject:CmsComment'))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->unreject()),

                Action::make('mark_spam')
                    ->label('Spam')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('gray')
                    ->visible(fn ($record) => ! $record->isSpam() && auth()->user()?->can('MarkAsSpam:CmsComment'))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->markAsSpam()),

                Action::make('not_spam')
                    ->label('Not Spam')
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->visible(fn ($record) => $record->isSpam() && auth()->user()?->can('MarkAsSpam:CmsComment'))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->unmarkSpam()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn () => auth()->user()?->can('Approve:CmsComment'))
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn ($record) => $record->approve(auth()->user())))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('reject_selected')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn () => auth()->user()?->can('Reject:CmsComment'))
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->reject())
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_spam_selected')
                        ->label('Mark as Spam')
                        ->icon('heroicon-o-shield-exclamation')
                        ->visible(fn () => auth()->user()?->can('MarkAsSpam:CmsComment'))
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->markAsSpam())
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No comments yet')
            ->emptyStateDescription('When visitors submit comments on your posts, they will appear here for moderation.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}
