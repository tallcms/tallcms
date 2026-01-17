<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Pages;

use TallCms\Cms\Filament\Resources\CmsPosts\CmsPostResource;
use TallCms\Cms\Services\PublishingWorkflowService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCmsPost extends EditRecord
{
    protected static string $resource = CmsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Workflow Actions Group
            ActionGroup::make([
                $this->getSubmitForReviewAction(),
                $this->getApproveAction(),
                $this->getRejectAction(),
            ])
                ->label('Workflow')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->button(),

            // Preview Actions Group
            ActionGroup::make([
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn () => route('tallcms.preview.post', ['post' => $this->record->id]))
                    ->openUrlInNewTab(),

                $this->getSharePreviewAction(),
                $this->getRevokePreviewLinksAction(),
            ])
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->button(),

            $this->getSaveSnapshotAction(),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getSaveSnapshotAction(): Action
    {
        return Action::make('saveSnapshot')
            ->label('Save Snapshot')
            ->icon('heroicon-o-camera')
            ->color('gray')
            ->visible(fn () => $this->record !== null && auth()->user()?->can('ViewRevisions:CmsPost'))
            ->form([
                Textarea::make('notes')
                    ->label('Snapshot Notes (optional)')
                    ->placeholder('Describe this milestone...')
                    ->rows(2),
            ])
            ->modalHeading('Save Snapshot')
            ->modalDescription('Save your current changes and create a pinned milestone in the revision history.')
            ->modalSubmitActionLabel('Save Snapshot')
            ->action(function (array $data) {
                // Skip ALL auto revision hooks for this save
                $this->record->skipRevisions();

                // Save form first to capture unsaved changes
                $this->save();
                $this->record->refresh();

                // Create manual snapshot directly (not via hooks)
                $this->record->createManualSnapshot($data['notes'] ?? null);

                Notification::make()
                    ->success()
                    ->title('Snapshot Saved')
                    ->body('Changes saved and snapshot created.')
                    ->send();
            });
    }

    protected function getSubmitForReviewAction(): Action
    {
        return Action::make('submitForReview')
            ->label('Submit for Review')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->visible(fn () => $this->record->canSubmitForReview() && auth()->user()?->can('SubmitForReview:CmsPost'))
            ->requiresConfirmation()
            ->modalHeading('Submit for Review')
            ->modalDescription('Are you sure you want to submit this post for review? An editor will need to approve it before it can be published.')
            ->modalSubmitActionLabel('Submit')
            ->action(function () {
                app(PublishingWorkflowService::class)->submitForReview($this->record);

                Notification::make()
                    ->title('Submitted for Review')
                    ->body('Your post has been submitted for review.')
                    ->success()
                    ->send();

                $this->refreshFormData(['status', 'submitted_at', 'submitted_by']);
            });
    }

    protected function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve & Publish')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn () => $this->record->canBeApproved() && auth()->user()?->can('Approve:CmsPost'))
            ->requiresConfirmation()
            ->modalHeading('Approve & Publish')
            ->modalDescription('Are you sure you want to approve and publish this post? It will be visible to the public.')
            ->modalSubmitActionLabel('Approve')
            ->action(function () {
                app(PublishingWorkflowService::class)->approve($this->record);

                Notification::make()
                    ->title('Post Approved')
                    ->body($this->record->isScheduled()
                        ? 'Post approved and scheduled for '.$this->record->published_at->format('M j, Y g:i A')
                        : 'Post approved and published.')
                    ->success()
                    ->send();

                $this->refreshFormData(['status', 'approved_at', 'approved_by', 'published_at']);
            });
    }

    protected function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => $this->record->canBeRejected() && auth()->user()?->can('Approve:CmsPost'))
            ->form([
                Textarea::make('rejection_reason')
                    ->label('Reason for Rejection')
                    ->required()
                    ->rows(4)
                    ->placeholder('Please explain why this post is being rejected and what changes are needed...'),
            ])
            ->modalHeading('Reject Post')
            ->modalDescription('Please provide a reason for rejection. The author will be notified.')
            ->modalSubmitActionLabel('Reject')
            ->action(function (array $data) {
                app(PublishingWorkflowService::class)->reject($this->record, $data['rejection_reason']);

                Notification::make()
                    ->title('Post Rejected')
                    ->body('The author has been notified with your feedback.')
                    ->warning()
                    ->send();

                $this->refreshFormData(['status', 'rejection_reason']);
            });
    }

    protected function getSharePreviewAction(): Action
    {
        return Action::make('sharePreview')
            ->label('Share Preview Link')
            ->icon('heroicon-o-share')
            ->visible(fn () => auth()->user()?->can('GeneratePreviewLink:CmsPost'))
            ->form([
                Radio::make('expiry')
                    ->label('Link Expires In')
                    ->options([
                        '1' => '1 hour',
                        '24' => '24 hours',
                        '168' => '7 days',
                        '720' => '30 days',
                    ])
                    ->default('24')
                    ->required(),
            ])
            ->modalHeading('Generate Shareable Preview Link')
            ->modalDescription('Create a link that allows anyone to preview this content without logging in.')
            ->modalSubmitActionLabel('Generate Link')
            ->action(function (array $data) {
                $hours = (int) $data['expiry'];
                $token = $this->record->createPreviewToken(Carbon::now()->addHours($hours));

                $url = $token->getPreviewUrl();

                Notification::make()
                    ->title('Preview Link Generated')
                    ->body("Link expires in {$hours} hour(s). Click to copy.")
                    ->success()
                    ->actions([
                        Action::make('copy')
                            ->label('Copy Link')
                            ->url($url)
                            ->openUrlInNewTab(),
                    ])
                    ->persistent()
                    ->send();
            });
    }

    protected function getRevokePreviewLinksAction(): Action
    {
        return Action::make('revokePreviewLinks')
            ->label('Revoke All Preview Links')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn () => $this->record->hasActivePreviewTokens() && auth()->user()?->can('GeneratePreviewLink:CmsPost'))
            ->requiresConfirmation()
            ->modalHeading('Revoke Preview Links')
            ->modalDescription(fn () => "This will invalidate all {$this->record->getActivePreviewTokenCount()} active preview link(s). This action cannot be undone.")
            ->modalSubmitActionLabel('Revoke All')
            ->action(function () {
                $count = $this->record->revokeAllPreviewTokens();

                Notification::make()
                    ->title('Preview Links Revoked')
                    ->body("{$count} preview link(s) have been revoked.")
                    ->success()
                    ->send();
            });
    }
}
