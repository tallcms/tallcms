<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Users;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use TallCms\Cms\Filament\Resources\Users\Pages\CreateUser;
use TallCms\Cms\Filament\Resources\Users\Pages\EditUser;
use TallCms\Cms\Filament\Resources\Users\Pages\ListUsers;
use TallCms\Cms\Filament\Resources\Users\Schemas\UserForm;
use TallCms\Cms\Filament\Resources\Users\Tables\UsersTable;

class UserResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    public static function getModel(): string
    {
        return config('tallcms.plugin_mode.user_model', 'App\\Models\\User');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'User Management';
    }
}
