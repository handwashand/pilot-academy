<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * Settings → Profile: the same name/email/photo/password form as the account
 * menu's Profile link (Filament's own ->profile() page), offered again here
 * so changing your own details does not mean leaving Settings. Both edit the
 * same signed-in user; neither is authoritative over the other, and either
 * one picks up what the other just saved.
 *
 * First by $navigationSort, so opening the Settings cluster lands here.
 */
class SettingsProfile extends Page
{
    protected static ?string $cluster = Settings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __t('admin_settings.overlay.profile');
    }

    public function getTitle(): string
    {
        return __t('admin_settings.overlay.profile');
    }

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'avatar_path' => $user->avatar_path,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

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

                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(fn (): string => __t('admin_settings.overlay.name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('email')
                            ->label(fn (): string => __t('admin_settings.overlay.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'users', column: 'email', ignorable: auth()->user())
                            ->live(debounce: 500),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('password')
                            ->label(fn (): string => __t('admin_settings.overlay.password'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->live(debounce: 500)
                            ->same('passwordConfirmation'),

                        TextInput::make('passwordConfirmation')
                            ->label(fn (): string => __t('admin_settings.overlay.password_confirmation'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->autocomplete('new-password')
                            ->visible(fn (Get $get): bool => filled($get('password')))
                            ->dehydrated(false),
                    ]),

                TextInput::make('currentPassword')
                    ->label(fn (): string => __t('admin_settings.overlay.current_password'))
                    ->belowContent(fn (): string => __t('admin_settings.overlay.current_password_help'))
                    ->password()
                    ->autocomplete('current-password')
                    ->currentPassword(guard: Filament::getAuthGuard())
                    ->visible(fn (Get $get): bool => filled($get('password')) || ($get('email') !== auth()->user()->email))
                    ->dehydrated(false),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make($this->getFormActions())]),
        ]);
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(fn (): string => __t('admin_settings.overlay.save'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();
        $oldAvatar = $user->avatar_path;

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->avatar_path = $data['avatar_path'] ?? null;

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->save();

        if (filled($oldAvatar) && $oldAvatar !== $user->avatar_path) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $this->mount();

        Notification::make()->title(__t('admin_settings.overlay.saved'))->success()->send();
    }
}
