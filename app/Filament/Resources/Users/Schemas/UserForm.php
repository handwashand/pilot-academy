<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->maxLength(255)
                    ->helperText('Leave blank to keep the current password when editing.'),

                Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        TextInput::make('region'),
                        TextInput::make('industry'),
                    ])
                    ->helperText('Partner company this user belongs to (leave empty for admins).'),

                Select::make('role')
                    ->label('Role')
                    ->options(User::ROLE_LABELS)
                    ->default(User::ROLE_LEARNER)
                    ->required()
                    ->live()
                    ->helperText('Admins run the platform. Creators manage the training for their own products only. Learners take courses.'),

                Select::make('products')
                    ->label('Products / modules')
                    ->relationship('products', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->visible(fn ($get): bool => $get('role') === User::ROLE_CREATOR)
                    ->helperText('The products this creator owns the training for. They cannot see any other product\'s courses.'),

                CheckboxList::make('permission_names')
                    ->label('Extra permissions')
                    ->options([
                        User::PERMISSION_LANGUAGES_MANAGE => 'Manage languages',
                        User::PERMISSION_TRANSLATIONS_MANAGE => 'Manage translations',
                    ])
                    ->helperText('Granted per account. These are not included in the Admin or Creator roles by default.')
                    ->dehydrated(false)
                    ->afterStateHydrated(function (CheckboxList $component, ?User $record): void {
                        $component->state($record?->permissions()->pluck('permission')->all() ?? []);
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                    ->columnSpanFull(),
            ]);
    }
}
