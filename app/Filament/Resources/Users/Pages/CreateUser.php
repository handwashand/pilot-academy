<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $permissions = $this->form->getRawState()['permission_names'] ?? [];

        foreach ($permissions as $permission) {
            $this->record->permissions()->create(['permission' => $permission]);
        }
    }
}
