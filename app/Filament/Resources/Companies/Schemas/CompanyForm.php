<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__t('admin_people.companies.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('region')
                    ->label(__t('admin_people.companies.region'))
                    ->maxLength(255)
                    ->helperText(__t('admin_people.companies.region_help')),

                TextInput::make('industry')
                    ->label(__t('admin_people.companies.industry'))
                    ->maxLength(255)
                    ->helperText(__t('admin_people.companies.industry_help')),
            ]);
    }
}
