<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use TallCms\Cms\Validation\TokenAbilityValidator;

class ApiTokens extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $title = 'API Tokens';

    protected string $view = 'tallcms::filament.pages.api-tokens';

    public ?string $newToken = null;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-key';
    }

    public static function getNavigationLabel(): string
    {
        return 'API Tokens';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('tallcms.filament.navigation_group') ?? 'Settings';
    }

    public static function getNavigationSort(): ?int
    {
        return config('tallcms.filament.navigation_sort') ?? 65;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('tallcms.api.enabled', false);
    }

    /**
     * Get current user's tokens.
     */
    #[Computed]
    public function tokens(): Collection
    {
        return auth()->user()->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'expires_at' => $token->expires_at?->format('M d, Y'),
                'is_expired' => $token->expires_at && $token->expires_at->isPast(),
                'created_at' => $token->created_at->format('M d, Y'),
            ]);
    }

    /**
     * Create token action.
     */
    public function createTokenAction(): Action
    {
        return Action::make('createToken')
            ->label('Create Token')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->form([
                TextInput::make('name')
                    ->label('Token Name')
                    ->placeholder('e.g., API Client, CI/CD, Mobile App')
                    ->required()
                    ->maxLength(255),
                CheckboxList::make('abilities')
                    ->label('Permissions')
                    ->options([
                        'pages:read' => 'Read Pages',
                        'pages:write' => 'Create/Update Pages',
                        'pages:delete' => 'Delete Pages',
                        'posts:read' => 'Read Posts',
                        'posts:write' => 'Create/Update Posts',
                        'posts:delete' => 'Delete Posts',
                        'categories:read' => 'Read Categories',
                        'categories:write' => 'Create/Update Categories',
                        'categories:delete' => 'Delete Categories',
                        'media:read' => 'Read Media',
                        'media:write' => 'Upload/Update Media',
                        'media:delete' => 'Delete Media',
                        'webhooks:manage' => 'Manage Webhooks',
                    ])
                    ->columns(2)
                    ->required(),
                TextInput::make('expires_in_days')
                    ->label('Expires In (Days)')
                    ->numeric()
                    ->default(config('tallcms.api.token_expiry_days', 365))
                    ->minValue(1)
                    ->maxValue(365)
                    ->suffix('days'),
            ])
            ->action(function (array $data) {
                $expiresAt = now()->addDays((int) $data['expires_in_days']);

                $token = auth()->user()->createToken(
                    $data['name'],
                    $data['abilities'],
                    $expiresAt
                );

                $this->newToken = $token->plainTextToken;

                unset($this->tokens);

                $this->dispatch('open-modal', id: 'token-created-modal');

                Notification::make()
                    ->title('Token created')
                    ->body('Your new API token has been created. Copy it now - it won\'t be shown again!')
                    ->success()
                    ->send();
            });
    }

    /**
     * Revoke token action.
     */
    public function revokeTokenAction(): Action
    {
        return Action::make('revokeToken')
            ->label('Revoke')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Revoke Token')
            ->modalDescription('Are you sure you want to revoke this token? Applications using this token will no longer be able to access the API.')
            ->modalSubmitActionLabel('Yes, Revoke')
            ->action(function (array $arguments) {
                auth()->user()->tokens()->where('id', $arguments['id'])->delete();

                unset($this->tokens);

                Notification::make()
                    ->title('Token revoked')
                    ->body('The API token has been revoked.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Close token modal.
     */
    public function closeTokenModal(): void
    {
        $this->newToken = null;
    }

    /**
     * Get header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->createTokenAction(),
        ];
    }
}
