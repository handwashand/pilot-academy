<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Certificate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * Settings → Certificate: the standard certificate background every course
 * uses unless it has its own. Swapping the standard is an upload, not a code
 * change. With nothing uploaded, the built-in layout is drawn.
 *
 * Stored as the only file in Certificate::STANDARD_DIR, so no table or
 * setting row is needed; saving removes any older file there.
 */
class SettingsCertificate extends Page
{
    protected static ?string $cluster = Settings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 40;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin_settings.certificate.nav');
    }

    public function getTitle(): string
    {
        return __t('admin_settings.certificate.nav');
    }

    public function getSubheading(): ?string
    {
        return __t('admin_settings.certificate.subheading');
    }

    public function mount(): void
    {
        $this->form->fill(['background' => Certificate::standardTemplate()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('background')
                    ->label(fn (): string => __t('admin_settings.certificate.background'))
                    ->helperText(fn (): string => __t('admin_settings.certificate.background_help'))
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg'])
                    ->disk('public')
                    ->directory(Certificate::STANDARD_DIR)
                    ->visibility('public')
                    ->maxSize(8192),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([
                    Action::make('save')
                        ->label(fn (): string => __t('admin_settings.overlay.save'))
                        ->submit('save'),
                ])]),
        ]);
    }

    public function save(): void
    {
        $kept = $this->form->getState()['background'] ?? null;

        // One standard at a time: anything else in the folder goes.
        foreach (Storage::disk('public')->files(Certificate::STANDARD_DIR) as $file) {
            if ($file !== $kept) {
                Storage::disk('public')->delete($file);
            }
        }

        $this->mount();

        Notification::make()->title(__t('admin_settings.overlay.saved'))->success()->send();
    }
}
