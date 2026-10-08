<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * The account menu's "Profile" link (panel ->profile()). Filament's own
 * EditProfile, plus a photo field — the only difference from the stock page.
 *
 * Settings → Profile (App\Filament\Pages\SettingsProfile) edits the same
 * user and the same avatar_path, for admins who reach Settings first; this
 * page stays the vendor one everywhere else (layout, validation, rate
 * limiting, multi-factor section) so there is exactly one place that logic
 * lives.
 */
class AccountProfile extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar_path')
                    ->label(fn (): string => __t('admin_settings.overlay.photo'))
                    ->avatar()
                    ->disk('public')
                    ->directory('avatars')
                    ->visibility('public'),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldAvatar = $record->avatar_path;

        $record = parent::handleRecordUpdate($record, $data);

        if (filled($oldAvatar) && $oldAvatar !== $record->avatar_path) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $record;
    }
}
