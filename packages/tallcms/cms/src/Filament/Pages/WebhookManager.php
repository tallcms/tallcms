<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use TallCms\Cms\Models\Webhook;
use TallCms\Cms\Models\WebhookDelivery;
use TallCms\Cms\Services\WebhookUrlValidator;

class WebhookManager extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $title = 'Webhooks';

    protected string $view = 'tallcms::filament.pages.webhook-manager';

    public ?int $selectedWebhookId = null;

    public ?array $webhookDetails = null;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-arrow-path-rounded-square';
    }

    public static function getNavigationLabel(): string
    {
        return 'Webhooks';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return config('tallcms.filament.navigation_sort') ?? 66;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('tallcms.webhooks.enabled', false);
    }

    /**
     * Get all webhooks.
     */
    #[Computed]
    public function webhooks(): Collection
    {
        return Webhook::with('creator')
            ->withCount(['deliveries as recent_success_count' => function ($query) {
                $query->where('success', true)->where('created_at', '>=', now()->subDays(7));
            }])
            ->withCount(['deliveries as recent_failure_count' => function ($query) {
                $query->where('success', false)->where('created_at', '>=', now()->subDays(7));
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Webhook $webhook) => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'creator_name' => $webhook->creator?->name ?? 'Unknown',
                'recent_success_count' => $webhook->recent_success_count,
                'recent_failure_count' => $webhook->recent_failure_count,
                'created_at' => $webhook->created_at->format('M d, Y'),
            ]);
    }

    /**
     * Show webhook details.
     */
    public function showWebhookDetails(int $id): void
    {
        $webhook = Webhook::with(['creator', 'deliveries' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(20);
        }])->find($id);

        if (! $webhook) {
            return;
        }

        $this->selectedWebhookId = $id;
        $this->webhookDetails = [
            'id' => $webhook->id,
            'name' => $webhook->name,
            'url' => $webhook->url,
            'events' => $webhook->events,
            'is_active' => $webhook->is_active,
            'timeout' => $webhook->timeout,
            'creator_name' => $webhook->creator?->name ?? 'Unknown',
            'created_at' => $webhook->created_at->format('M d, Y H:i'),
            'deliveries' => $webhook->deliveries->map(fn (WebhookDelivery $d) => [
                'id' => $d->id,
                'delivery_id' => $d->delivery_id,
                'event' => $d->event,
                'attempt' => $d->attempt,
                'status_code' => $d->status_code,
                'duration_ms' => $d->duration_ms,
                'success' => $d->success,
                'created_at' => $d->created_at->format('M d, Y H:i:s'),
            ])->toArray(),
        ];

        $this->dispatch('open-modal', id: 'webhook-details-modal');
    }

    /**
     * Close webhook details modal.
     */
    public function closeWebhookDetails(): void
    {
        $this->selectedWebhookId = null;
        $this->webhookDetails = null;
    }

    /**
     * Create webhook action.
     */
    public function createWebhookAction(): Action
    {
        return Action::make('createWebhook')
            ->label('Add Webhook')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->form([
                TextInput::make('name')
                    ->label('Name')
                    ->placeholder('e.g., Netlify Build Hook, Slack Notification')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('Endpoint URL')
                    ->placeholder('https://example.com/webhook')
                    ->required()
                    ->url()
                    ->maxLength(2048)
                    ->helperText('Must be HTTPS. IP addresses are not allowed.'),
                CheckboxList::make('events')
                    ->label('Events')
                    ->options([
                        'page.created' => 'Page Created',
                        'page.updated' => 'Page Updated',
                        'page.published' => 'Page Published',
                        'page.unpublished' => 'Page Unpublished',
                        'page.deleted' => 'Page Deleted',
                        'post.created' => 'Post Created',
                        'post.updated' => 'Post Updated',
                        'post.published' => 'Post Published',
                        'post.unpublished' => 'Post Unpublished',
                        'post.deleted' => 'Post Deleted',
                        'category.created' => 'Category Created',
                        'category.updated' => 'Category Updated',
                        'category.deleted' => 'Category Deleted',
                        'media.created' => 'Media Uploaded',
                        'media.updated' => 'Media Updated',
                        'media.deleted' => 'Media Deleted',
                    ])
                    ->columns(2)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                TextInput::make('timeout')
                    ->label('Timeout (seconds)')
                    ->numeric()
                    ->default(30)
                    ->minValue(5)
                    ->maxValue(60),
            ])
            ->action(function (array $data) {
                // Validate URL
                $validator = app(WebhookUrlValidator::class);
                $result = $validator->validateOnCreate($data['url']);

                if (! $result['valid']) {
                    Notification::make()
                        ->title('Invalid URL')
                        ->body($result['error'])
                        ->danger()
                        ->send();

                    return;
                }

                Webhook::create([
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'events' => $data['events'],
                    'is_active' => $data['is_active'] ?? true,
                    'timeout' => $data['timeout'] ?? 30,
                    'created_by' => auth()->id(),
                ]);

                unset($this->webhooks);

                Notification::make()
                    ->title('Webhook created')
                    ->body('The webhook has been created successfully.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Edit webhook action.
     */
    public function editWebhookAction(): Action
    {
        return Action::make('editWebhook')
            ->label('Edit')
            ->icon('heroicon-o-pencil')
            ->color('gray')
            ->form([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('Endpoint URL')
                    ->required()
                    ->url()
                    ->maxLength(2048),
                CheckboxList::make('events')
                    ->label('Events')
                    ->options([
                        'page.created' => 'Page Created',
                        'page.updated' => 'Page Updated',
                        'page.published' => 'Page Published',
                        'page.unpublished' => 'Page Unpublished',
                        'page.deleted' => 'Page Deleted',
                        'post.created' => 'Post Created',
                        'post.updated' => 'Post Updated',
                        'post.published' => 'Post Published',
                        'post.unpublished' => 'Post Unpublished',
                        'post.deleted' => 'Post Deleted',
                        'category.created' => 'Category Created',
                        'category.updated' => 'Category Updated',
                        'category.deleted' => 'Category Deleted',
                        'media.created' => 'Media Uploaded',
                        'media.updated' => 'Media Updated',
                        'media.deleted' => 'Media Deleted',
                    ])
                    ->columns(2)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active'),
                TextInput::make('timeout')
                    ->label('Timeout (seconds)')
                    ->numeric()
                    ->minValue(5)
                    ->maxValue(60),
            ])
            ->fillForm(function (array $arguments) {
                $webhook = Webhook::find($arguments['id']);

                return [
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'is_active' => $webhook->is_active,
                    'timeout' => $webhook->timeout,
                ];
            })
            ->action(function (array $data, array $arguments) {
                $webhook = Webhook::find($arguments['id']);

                if (! $webhook) {
                    return;
                }

                // Validate URL if changed
                if ($data['url'] !== $webhook->url) {
                    $validator = app(WebhookUrlValidator::class);
                    $result = $validator->validateOnCreate($data['url']);

                    if (! $result['valid']) {
                        Notification::make()
                            ->title('Invalid URL')
                            ->body($result['error'])
                            ->danger()
                            ->send();

                        return;
                    }
                }

                $webhook->update($data);

                unset($this->webhooks);
                $this->closeWebhookDetails();

                Notification::make()
                    ->title('Webhook updated')
                    ->body('The webhook has been updated successfully.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Delete webhook action.
     */
    public function deleteWebhookAction(): Action
    {
        return Action::make('deleteWebhook')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete Webhook')
            ->modalDescription('Are you sure you want to delete this webhook? This will also delete all delivery logs.')
            ->modalSubmitActionLabel('Yes, Delete')
            ->action(function (array $arguments) {
                $webhook = Webhook::find($arguments['id']);

                if ($webhook) {
                    $webhook->delete();
                }

                unset($this->webhooks);
                $this->closeWebhookDetails();

                Notification::make()
                    ->title('Webhook deleted')
                    ->body('The webhook and its delivery logs have been deleted.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Test webhook action.
     */
    public function testWebhookAction(): Action
    {
        return Action::make('testWebhook')
            ->label('Send Test')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->action(function (array $arguments) {
                $webhook = Webhook::find($arguments['id']);

                if (! $webhook) {
                    return;
                }

                $testPayload = [
                    'event' => 'test',
                    'timestamp' => now()->toIso8601String(),
                    'data' => [
                        'id' => 0,
                        'type' => 'test',
                        'attributes' => [
                            'message' => 'This is a test webhook delivery',
                        ],
                    ],
                    'meta' => [
                        'triggered_by' => [
                            'id' => auth()->id(),
                            'name' => auth()->user()->name,
                        ],
                    ],
                ];

                \TallCms\Cms\Jobs\DispatchWebhook::dispatchSync(
                    $webhook,
                    $testPayload,
                    'wh_test_'.now()->timestamp
                );

                Notification::make()
                    ->title('Test sent')
                    ->body('A test webhook has been sent. Check the delivery logs for results.')
                    ->success()
                    ->send();

                // Refresh details if viewing
                if ($this->selectedWebhookId === $webhook->id) {
                    $this->showWebhookDetails($webhook->id);
                }
            });
    }

    /**
     * Get header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->createWebhookAction(),
        ];
    }
}
