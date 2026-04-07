<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    /**
     * Build author profile fields based on which columns exist on the user table.
     * This ensures plugin-mode compatibility — host apps may not have run all migrations.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    protected static function getAuthorProfileFields(string $model): array
    {
        try {
            $table = (new $model)->getTable();
        } catch (\Throwable) {
            $table = 'users';
        }

        $fields = [];

        if (DbSchema::hasColumn($table, 'slug')) {
            $fields[] = TextInput::make('slug')
                ->label('Author Slug')
                ->disabled()
                ->dehydrated(false)
                ->helperText('Auto-generated from name. Used in author archive URL.');
        }

        if (DbSchema::hasColumn($table, 'bio')) {
            $fields[] = Textarea::make('bio')
                ->label('Biography')
                ->rows(3)
                ->maxLength(1000)
                ->helperText('Short bio displayed on posts and author page');
        }

        if (DbSchema::hasColumn($table, 'twitter_handle')) {
            $fields[] = TextInput::make('twitter_handle')
                ->label('X / Twitter Handle')
                ->prefix('@')
                ->maxLength(50);
        }

        if (DbSchema::hasColumn($table, 'job_title')) {
            $fields[] = TextInput::make('job_title')
                ->label('Job Title')
                ->maxLength(255)
                ->placeholder('e.g., Senior Editor');
        }

        if (DbSchema::hasColumn($table, 'company')) {
            $fields[] = TextInput::make('company')
                ->label('Company / Organization')
                ->maxLength(255);
        }

        if (DbSchema::hasColumn($table, 'linkedin_url')) {
            $fields[] = TextInput::make('linkedin_url')
                ->label('LinkedIn URL')
                ->url()
                ->maxLength(500)
                ->placeholder('https://linkedin.com/in/...');
        }

        return $fields;
    }

    public static function configure(Schema $schema): Schema
    {
        $model = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

        return $schema
            ->components([
                Section::make('User Information')
                    ->description('Basic user profile information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter full name'),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique($model, 'email', ignoreRecord: true)
                            ->placeholder('user@example.com'),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rule(Password::default())
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->placeholder('Enter secure password')
                            ->helperText('Leave blank to keep current password when editing'),
                    ])
                    ->columns(2),

                Section::make('Author Profile')
                    ->description('Public author information displayed on posts and author archives')
                    ->schema(static::getAuthorProfileFields($model))
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn () => ! empty(static::getAuthorProfileFields($model))),

                Section::make('Role Assignment')
                    ->description('Assign roles to this user')
                    ->schema([
                        Select::make('roles')
                            ->label('User Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Select one or more roles for this user')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
