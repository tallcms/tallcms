<?php

namespace App\Livewire;

use App\Models\CmsRevision;
use App\Services\ContentDiffService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class RevisionHistory extends Component
{
    public Model $record;

    public ?int $selectedRevision = null;

    public ?int $compareRevision = null;

    public ?array $diff = null;

    public function mount(Model $record): void
    {
        $this->record = $record;
    }

    public function getRevisionsProperty()
    {
        return $this->record->revisions()
            ->with('user')
            ->orderByDesc('revision_number')
            ->get();
    }

    public function selectRevision(int $revisionId): void
    {
        if ($this->selectedRevision === $revisionId) {
            $this->selectedRevision = null;
            $this->compareRevision = null;
            $this->diff = null;

            return;
        }

        if ($this->selectedRevision === null) {
            $this->selectedRevision = $revisionId;
        } else {
            $this->compareRevision = $revisionId;
            $this->calculateDiff();
        }
    }

    public function clearSelection(): void
    {
        $this->selectedRevision = null;
        $this->compareRevision = null;
        $this->diff = null;
    }

    protected function calculateDiff(): void
    {
        if (! $this->selectedRevision || ! $this->compareRevision) {
            $this->diff = null;

            return;
        }

        $revision1 = CmsRevision::find($this->selectedRevision);
        $revision2 = CmsRevision::find($this->compareRevision);

        if (! $revision1 || ! $revision2) {
            $this->diff = null;

            return;
        }

        // Always compare older to newer
        if ($revision1->revision_number > $revision2->revision_number) {
            [$revision1, $revision2] = [$revision2, $revision1];
        }

        $this->diff = $revision2->diffWith($revision1);
    }

    public function restoreRevision(int $revisionId): void
    {
        // Check permission
        $permissionName = $this->record instanceof \App\Models\CmsPost
            ? 'RestoreRevision:CmsPost'
            : 'RestoreRevision:CmsPage';

        if (! auth()->user()?->can($permissionName)) {
            Notification::make()
                ->danger()
                ->title('Permission Denied')
                ->body('You do not have permission to restore revisions.')
                ->send();

            return;
        }

        $revision = CmsRevision::find($revisionId);

        if (! $revision) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Revision not found.')
                ->send();

            return;
        }

        // Restore the revision
        $this->record->restoreRevision($revision);

        Notification::make()
            ->success()
            ->title('Revision Restored')
            ->body("Content restored to revision #{$revision->revision_number}")
            ->send();

        // Clear selection and refresh
        $this->clearSelection();

        // Dispatch event to refresh the form
        $this->dispatch('revision-restored');
    }

    public function render()
    {
        return view('livewire.revision-history');
    }
}
