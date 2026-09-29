<?php

namespace App\Filament\Resources\Webinars\Pages;

use App\Filament\Resources\Webinars\WebinarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebinars extends ListRecords
{
    protected static string $resource = WebinarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
