<?php

namespace App\Filament\Resources\MediaItems\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('A label to find this image later (e.g. "Map screenshot").'),

                FileUpload::make('path')
                    ->label('Image')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios(['16:9', '4:3', '1:1', null])
                    ->disk('public')
                    ->directory('media-library')
                    ->visibility('public')
                    ->maxSize(8192)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
