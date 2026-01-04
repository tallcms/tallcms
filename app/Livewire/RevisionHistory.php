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

    // Selection can be revision ID or 'current' for current content
    public string|int|null $selectedRevision = null;

    public string|int|null $compareRevision = null;

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

    public function selectRevision(string|int $revisionId): void
    {
        // Toggle off if clicking same one
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

    public function compareToCurrent(int $revisionId): void
    {
        $this->selectedRevision = $revisionId;
        $this->compareRevision = 'current';
        $this->calculateDiff();
    }

    public function compareToPrevious(int $revisionId): void
    {
        $revision = CmsRevision::find($revisionId);
        if (! $revision) {
            return;
        }

        // Find the previous revision
        $previous = $this->record->revisions()
            ->where('revision_number', '<', $revision->revision_number)
            ->orderByDesc('revision_number')
            ->first();

        if ($previous) {
            $this->selectedRevision = $previous->id;
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
        if ($this->selectedRevision === null || $this->compareRevision === null) {
            $this->diff = null;

            return;
        }

        $diffService = app(ContentDiffService::class);

        // Get content for both sides
        $olderContent = $this->getContentForSelection($this->selectedRevision);
        $newerContent = $this->getContentForSelection($this->compareRevision);

        if ($olderContent === null && $newerContent === null) {
            $this->diff = null;

            return;
        }

        // Determine which is older based on revision number or current
        $olderLabel = $this->getLabelForSelection($this->selectedRevision);
        $newerLabel = $this->getLabelForSelection($this->compareRevision);

        // Swap if needed (current is always newest, otherwise compare revision numbers)
        if ($this->shouldSwap()) {
            [$olderContent, $newerContent] = [$newerContent, $olderContent];
            [$olderLabel, $newerLabel] = [$newerLabel, $olderLabel];
        }

        $this->diff = [
            'older_label' => $olderLabel,
            'newer_label' => $newerLabel,
            'title' => $this->diffField('title', $olderContent, $newerContent),
            'excerpt' => $this->diffField('excerpt', $olderContent, $newerContent),
            'meta_title' => $this->diffField('meta_title', $olderContent, $newerContent),
            'meta_description' => $this->diffField('meta_description', $olderContent, $newerContent),
            'content' => $diffService->diff(
                $olderContent['content'] ?? null,
                $newerContent['content'] ?? null
            ),
        ];
    }

    protected function shouldSwap(): bool
    {
        // Current is always newest
        if ($this->selectedRevision === 'current') {
            return true;
        }
        if ($this->compareRevision === 'current') {
            return false;
        }

        // Compare revision numbers
        $rev1 = CmsRevision::find($this->selectedRevision);
        $rev2 = CmsRevision::find($this->compareRevision);

        if (! $rev1 || ! $rev2) {
            return false;
        }

        return $rev1->revision_number > $rev2->revision_number;
    }

    protected function getContentForSelection(string|int $selection): ?array
    {
        if ($selection === 'current') {
            return [
                'title' => $this->record->title,
                'excerpt' => $this->record->excerpt,
                'content' => $this->record->content,
                'meta_title' => $this->record->meta_title,
                'meta_description' => $this->record->meta_description,
            ];
        }

        $revision = CmsRevision::find($selection);
        if (! $revision) {
            return null;
        }

        return [
            'title' => $revision->title,
            'excerpt' => $revision->excerpt,
            'content' => $revision->content,
            'meta_title' => $revision->meta_title,
            'meta_description' => $revision->meta_description,
        ];
    }

    protected function getLabelForSelection(string|int $selection): string
    {
        if ($selection === 'current') {
            return 'Current Version';
        }

        $revision = CmsRevision::find($selection);

        return $revision ? "Revision #{$revision->revision_number}" : 'Unknown';
    }

    protected function diffField(string $field, ?array $older, ?array $newer): ?array
    {
        $oldValue = $older[$field] ?? null;
        $newValue = $newer[$field] ?? null;

        if ($oldValue === $newValue) {
            return null;
        }

        return [
            'old' => $oldValue,
            'new' => $newValue,
        ];
    }

    public function restoreRevision(int $revisionId): void
    {
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

        $this->record->restoreRevision($revision);

        Notification::make()
            ->success()
            ->title('Revision Restored')
            ->body("Content restored to revision #{$revision->revision_number}")
            ->send();

        $this->clearSelection();
        $this->dispatch('revision-restored');
    }

    public function render()
    {
        return view('livewire.revision-history');
    }
}
