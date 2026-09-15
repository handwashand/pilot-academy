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
                    ->label(__t('admin_common.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__t('admin_common.email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label(__t('admin_people.users.password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->maxLength(255)
                    ->helperText(__t('admin_people.users.password_help')),

                Select::make('company_id')
                    ->label(__t('admin_common.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->label(__t('admin_people.companies.name'))->required(),
                        TextInput::make('region')->label(__t('admin_people.companies.region')),
                        TextInput::make('industry')->label(__t('admin_people.companies.industry')),
                    ])
                    ->helperText(__t('admin_people.users.company_help')),

                Select::make('role')
                    ->label(__t('admin_people.users.role'))
                    ->options(User::roleLabels())
                    ->default(User::ROLE_LEARNER)
                    ->required()
                    ->live()
                    ->helperText(__t('admin_people.users.role_help')),

                Select::make('products')
                    ->label(__t('admin_people.users.products'))
                    ->relationship('products', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->visible(fn ($get): bool => $get('role') === User::ROLE_CREATOR)
                    ->helperText(__t('admin_people.users.products_help')),

                CheckboxList::make('permission_names')
                    ->label(__t('admin_people.users.permissions'))
                    ->options([
                        User::PERMISSION_LANGUAGES_MANAGE => __t('admin_people.users.manage_languages'),
                        User::PERMISSION_TRANSLATIONS_MANAGE => __t('admin_people.users.manage_translations'),
                    ])
                    ->helperText(__t('admin_people.users.permissions_help'))
                    ->dehydrated(false)
                    ->afterStateHydrated(function (CheckboxList $component, ?User $record): void {
                        $component->state($record?->permissions()->pluck('permission')->all() ?? []);
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                    ->columnSpanFull(),
            ]);
    }
}
