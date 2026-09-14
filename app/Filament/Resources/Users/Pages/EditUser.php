<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $permissions = $this->form->getRawState()['permission_names'] ?? [];

        $this->record->permissions()->delete();

        foreach ($permissions as $permission) {
            $this->record->permissions()->create(['permission' => $permission]);
        }
    }
}
