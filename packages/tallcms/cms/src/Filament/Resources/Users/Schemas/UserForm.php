<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
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
