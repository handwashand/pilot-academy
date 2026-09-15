<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__t('admin_library.products.name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->helperText(__t('admin_library.products.name_help')),

                TextInput::make('slug')
                    ->label(__t('admin_common.slug'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->label(__t('admin_common.description'))
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('creators')
                    ->label(__t('admin_library.products.creators'))
                    ->relationship(
                        'creators',
                        'name',
                        fn ($query) => $query->where('role', User::ROLE_CREATOR),
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->helperText(__t('admin_library.products.creators_help')),
            ]);
    }
}
