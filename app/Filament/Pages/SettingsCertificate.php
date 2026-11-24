<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use App\Models\Certificate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
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
 *
 * The signatory follows the same idea: the name and title in a small file on
 * the private disk (Certificate::SIGNATORY_FILE), the signature image as the
 * only file in Certificate::SIGNATURE_DIR.
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
        $signatory = Certificate::signatory();

        $this->form->fill([
            'background' => Certificate::standardTemplate(),
            'signatory_name' => $signatory['name'] ?? null,
            'signatory_title' => $signatory['title'] ?? null,
            'signature' => $signatory['signature'] ?? collect(Storage::disk('public')->files(Certificate::SIGNATURE_DIR))->first(),
        ]);
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

                // Printed on every certificate, course and all-courses alike.
                // With no name, no signature block is printed at all.
                Section::make(fn (): string => __t('admin_settings.certificate.signatory'))
                    ->description(fn (): string => __t('admin_settings.certificate.signatory_help'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('signatory_name')
                            ->label(fn (): string => __t('admin_settings.certificate.signatory_name'))
                            ->maxLength(80),
                        TextInput::make('signatory_title')
                            ->label(fn (): string => __t('admin_settings.certificate.signatory_title'))
                            ->placeholder(fn (): string => __t('admin_settings.certificate.signatory_title_placeholder'))
                            ->maxLength(80),
                        FileUpload::make('signature')
                            ->label(fn (): string => __t('admin_settings.certificate.signature'))
                            ->helperText(fn (): string => __t('admin_settings.certificate.signature_help'))
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->disk('public')
                            ->directory(Certificate::SIGNATURE_DIR)
                            ->visibility('public')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),
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
        $state = $this->form->getState();

        // One file of each at a time: anything else in the folder goes.
        foreach ([Certificate::STANDARD_DIR => $state['background'] ?? null, Certificate::SIGNATURE_DIR => $state['signature'] ?? null] as $dir => $kept) {
            foreach (Storage::disk('public')->files($dir) as $file) {
                if ($file !== $kept) {
                    Storage::disk('public')->delete($file);
                }
            }
        }

        Storage::disk('local')->put(Certificate::SIGNATORY_FILE, (string) json_encode([
            'name' => trim(strip_tags((string) ($state['signatory_name'] ?? ''))),
            'title' => trim(strip_tags((string) ($state['signatory_title'] ?? ''))),
        ], JSON_UNESCAPED_UNICODE));

        $this->mount();

        Notification::make()->title(__t('admin_settings.overlay.saved'))->success()->send();
    }
}
