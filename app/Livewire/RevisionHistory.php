<?php

namespace App\Livewire;

use App\Models\CmsRevision;
use App\Services\ContentDiffService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;

class RevisionHistory extends Component
{
    public Model $record;

    // Selection can be revision ID or 'current' for current content
    public string|int|null $selectedRevision = null;

    public string|int|null $compareRevision = null;

    public ?array $diff = null;

    // Cached revisions collection
    protected ?Collection $revisionsCache = null;

    public function mount(Model $record): void
    {
        $this->record = $record;
    }

    /**
     * Get revisions for this record (cached)
     */
    public function getRevisionsProperty(): Collection
    {
        if ($this->revisionsCache === null) {
            $this->revisionsCache = $this->record->revisions()
                ->with('user')
                ->orderByDesc('revision_number')
                ->get();
        }

        return $this->revisionsCache;
    }

    /**
     * Find a revision that belongs to this record (security: scoped lookup)
     */
    protected function findRevision(int $id): ?CmsRevision
    {
        return $this->revisions->firstWhere('id', $id);
    }

    public function selectRevision(string|int $revisionId): void
    {
        // Validate revision belongs to this record
        if ($revisionId !== 'current' && ! $this->findRevision($revisionId)) {
            return;
        }

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
        if (! $this->findRevision($revisionId)) {
            return;
        }

        $this->selectedRevision = $revisionId;
        $this->compareRevision = 'current';
        $this->calculateDiff();
    }

    public function compareToPrevious(int $revisionId): void
    {
        $revision = $this->findRevision($revisionId);
        if (! $revision) {
            return;
        }

        // Find the previous revision from cached collection
        $previous = $this->revisions
            ->where('revision_number', '<', $revision->revision_number)
            ->sortByDesc('revision_number')
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

        // Get content for both sides - don't mutate selections, compute locally
        $firstContent = $this->getContentForSelection($this->selectedRevision);
        $secondContent = $this->getContentForSelection($this->compareRevision);

        if ($firstContent === null && $secondContent === null) {
            $this->diff = null;

            return;
        }

        // Determine older/newer without mutating user selections
        $firstId = $this->selectedRevision;
        $secondId = $this->compareRevision;
        $firstLabel = $this->getLabelForSelection($firstId);
        $secondLabel = $this->getLabelForSelection($secondId);

        // Determine which is older (for display order only)
        $shouldSwap = $this->isNewer($firstId, $secondId);

        if ($shouldSwap) {
            $olderId = $secondId;
            $newerId = $firstId;
            $olderLabel = $secondLabel;
            $newerLabel = $firstLabel;
            $olderContent = $secondContent;
            $newerContent = $firstContent;
        } else {
            $olderId = $firstId;
            $newerId = $secondId;
            $olderLabel = $firstLabel;
            $newerLabel = $secondLabel;
            $olderContent = $firstContent;
            $newerContent = $secondContent;
        }

        // Find restorable revision (any non-current revision in the comparison)
        $restorableId = null;
        if (is_int($newerId)) {
            $restorableId = $newerId;
        } elseif (is_int($olderId)) {
            $restorableId = $olderId;
        }

        $this->diff = [
            'older_label' => $olderLabel,
            'newer_label' => $newerLabel,
            'older_id' => $olderId,
            'newer_id' => $newerId,
            'restorable_id' => $restorableId,
            'restorable_label' => $restorableId ? $this->getLabelForSelection($restorableId) : null,
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

    /**
     * Check if first selection is newer than second (for ordering display)
     */
    protected function isNewer(string|int $first, string|int $second): bool
    {
        // Current is always newest
        if ($first === 'current') {
            return true;
        }
        if ($second === 'current') {
            return false;
        }

        // Compare revision numbers from cached collection
        $rev1 = $this->findRevision($first);
        $rev2 = $this->findRevision($second);

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

        $revision = $this->findRevision($selection);
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

        $revision = $this->findRevision($selection);

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
        // Security: verify revision belongs to this record
        $revision = $this->findRevision($revisionId);
        if (! $revision) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Revision not found or does not belong to this record.')
                ->send();

            return;
        }

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

        $this->record->restoreRevision($revision);

        // Refresh the record to get updated data
        $this->record->refresh();

        Notification::make()
            ->success()
            ->title('Revision Restored')
            ->body("Content restored to revision #{$revision->revision_number}")
            ->send();

        $this->clearSelection();
        $this->revisionsCache = null; // Clear cache to refresh

        // Redirect to the edit page to refresh the form with restored content
        $routeName = $this->record instanceof \App\Models\CmsPost
            ? 'filament.admin.resources.cms-posts.edit'
            : 'filament.admin.resources.cms-pages.edit';

        $this->redirect(route($routeName, ['record' => $this->record]), navigate: true);
    }

    public function render()
    {
        return view('livewire.revision-history');
    }
}
