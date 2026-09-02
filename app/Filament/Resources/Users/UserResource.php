<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('completedLessons');
    }

    protected static ?string $recordTitleAttribute = 'name';

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    /**
     * Two partners can easily have a "Jan Kowalski"; the role and company are
     * what tell you which one you are about to open.
     *
     * @return array<string, string|null>
     */
    public static function getGlobalSearchResultDetails(mixed $record): array
    {
        return [
            'Role' => $record->roleLabel(),
            'Partner' => $record->company?->name ?? '—',
            'Email' => $record->email,
        ];
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
        return [
            RelationManagers\CompletedLessonsRelationManager::class,
            RelationManagers\QuizAttemptsRelationManager::class,
            RelationManagers\CertificatesRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
