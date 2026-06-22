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
                    ->label('Company name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('region')
                    ->maxLength(255)
                    ->helperText('e.g. EMEA, LATAM, CIS'),

                TextInput::make('industry')
                    ->maxLength(255)
                    ->helperText('e.g. Logistics, Construction'),
            ]);
    }
}
