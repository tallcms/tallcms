<?php

declare(strict_types=1);

namespace TallCms\Cms\Observers;

use Illuminate\Database\Eloquent\Model;
use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Models\MediaCollection;
use TallCms\Cms\Models\TallcmsMedia;
use TallCms\Cms\Services\WebhookDispatcher;

class WebhookObserver
{
    public function __construct(
        protected WebhookDispatcher $dispatcher
    ) {}

    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        $event = $this->getEventName($model, 'created');
        $this->dispatcher->dispatch($event, $model);

        // For content with publishing workflow, also fire published event if created as published
        if ($this->hasPublishingWorkflow($model) && $model->status === ContentStatus::Published) {
            $this->dispatcher->dispatch($this->getEventName($model, 'published'), $model);
        }
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        $event = $this->getEventName($model, 'updated');
        $this->dispatcher->dispatch($event, $model);

        // Check for publish/unpublish status changes
        if ($this->hasPublishingWorkflow($model) && $model->wasChanged('status')) {
            $oldStatus = $model->getOriginal('status');
            $newStatus = $model->status;

            if ($newStatus === ContentStatus::Published && $oldStatus !== ContentStatus::Published) {
                $this->dispatcher->dispatch($this->getEventName($model, 'published'), $model);
            } elseif ($oldStatus === ContentStatus::Published && $newStatus !== ContentStatus::Published) {
                $this->dispatcher->dispatch($this->getEventName($model, 'unpublished'), $model);
            }
        }
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $event = $this->getEventName($model, 'deleted');
        $this->dispatcher->dispatch($event, $model);
    }

    /**
     * Handle the "restored" event (soft deletes).
     */
    public function restored(Model $model): void
    {
        $event = $this->getEventName($model, 'restored');
        $this->dispatcher->dispatch($event, $model);
    }

    /**
     * Get the event name for a model action.
     */
    protected function getEventName(Model $model, string $action): string
    {
        $type = match (true) {
            $model instanceof CmsPage => 'page',
            $model instanceof CmsPost => 'post',
            $model instanceof CmsCategory => 'category',
            $model instanceof TallcmsMedia => 'media',
            $model instanceof MediaCollection => 'media_collection',
            default => strtolower(class_basename($model)),
        };

        return "{$type}.{$action}";
    }

    /**
     * Check if a model has the publishing workflow.
     */
    protected function hasPublishingWorkflow(Model $model): bool
    {
        return $model instanceof CmsPage || $model instanceof CmsPost;
    }
}
