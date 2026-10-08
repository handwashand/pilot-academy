<?php

namespace App\Livewire;

use App\Filament\Pages\Integrations;
use App\Filament\Pages\MailCheck;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\Translations\TranslationResource;
use App\Models\AiProvider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasRenderHookScopes;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The Settings overlay: one click from the sidebar opens a dialog with
 * Profile, Integrations, Mail, Translations and Languages down the side —
 * instead of five separate sidebar links to five separate pages.
 *
 * Profile and Integrations are real forms here, saved together by the one
 * Save button — the field definitions and the per-provider save rules are
 * shared with the standalone pages (Integrations::providerSections() /
 * ::persist(), MailCheck::summary()) so nothing here disagrees with them.
 *
 * Mail, Translations and Languages are not: Mail's test-send action and
 * Translations'/Languages' full interactive tables are built on Filament
 * machinery (table actions, a 1,000+ row searchable table) that a form-only
 * Action modal cannot host. Their tabs show a short summary and a button
 * that closes this dialog and takes the admin to the real page — the
 * standalone pages (Integrations, MailCheck, LanguageResource,
 * TranslationResource) are otherwise unchanged and still fully featured;
 * only their sidebar links moved here (see each one's
 * shouldRegisterNavigation()).
 *
 * Mounted once, globally, via PanelsRenderHook::BODY_END in
 * AdminPanelProvider — see resources/views/livewire/settings-panel.blade.php,
 * which is the only place a HasActions component not extending a Filament
 * Page needs to render its own action modals.
 */
class SettingsPanel extends Component implements HasActions, HasRenderHookScopes, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[On('open-settings-modal')]
    public function open(): void
    {
        $this->mountAction('settings');
    }

    /**
     * Not covered by InteractsWithActions / InteractsWithSchemas — Filament's
     * own Page classes implement it directly (see Filament\Pages\BasePage)
     * rather than through either trait.
     *
     * @return array<string>
     */
    public function getRenderHookScopes(): array
    {
        return [static::class];
    }

    public function settingsAction(): Action
    {
        return Action::make('settings')
            ->label(fn (): string => __t('admin_nav.groups.settings'))
            ->modalHeading(fn (): string => __t('admin_nav.groups.settings'))
            ->modalWidth('3xl')
            ->modalSubmitActionLabel(fn (): string => __t('admin_settings.overlay.save'))
            ->fillForm(fn (): array => $this->initialState())
            ->schema([
                Tabs::make('settings')
                    ->vertical()
                    ->persistTabInQueryString(false)
                    ->tabs(array_filter([
                        $this->profileTab(),
                        Integrations::canAccess() ? $this->integrationsTab() : null,
                        MailCheck::canAccess() ? $this->linkOutTab(
                            key: 'mail',
                            label: __t('admin_nav.mail.nav'),
                            icon: Heroicon::OutlinedEnvelope,
                            description: __t('admin_settings.overlay.mail_description'),
                            url: MailCheck::getUrl(),
                        ) : null,
                        TranslationResource::canAccess() ? $this->linkOutTab(
                            key: 'translations',
                            label: __t('admin.translations'),
                            icon: Heroicon::OutlinedLanguage,
                            description: __t('admin_settings.overlay.translations_description'),
                            url: TranslationResource::getUrl(),
                        ) : null,
                        LanguageResource::canAccess() ? $this->linkOutTab(
                            key: 'languages',
                            label: __t('admin.languages'),
                            icon: Heroicon::OutlinedLanguage,
                            description: __t('admin_settings.overlay.languages_description'),
                            url: LanguageResource::getUrl(),
                        ) : null,
                    ])),
            ])
            ->action(fn (array $data) => $this->save($data));
    }

    /** @return array<string, mixed> */
    protected function initialState(): array
    {
        $user = auth()->user();

        $state = [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'password' => null,
                'passwordConfirmation' => null,
                'currentPassword' => null,
            ],
        ];

        if (Integrations::canAccess()) {
            foreach (array_keys(AiProvider::PROVIDERS) as $provider) {
                $row = AiProvider::for($provider);

                // The token is deliberately left out: it never comes back to the browser.
                $state['integrations'][$provider] = [
                    'enabled' => (bool) $row->enabled,
                    'model' => $row->model,
                    'api_key' => '',
                    'clear_key' => false,
                ];
            }
        }

        return $state;
    }

    protected function profileTab(): Tab
    {
        return Tab::make('profile')
            ->label(fn (): string => __t('admin_settings.overlay.profile'))
            ->icon(Heroicon::OutlinedUserCircle)
            ->statePath('profile')
            ->schema([
                TextInput::make('name')
                    ->label(fn (): string => __t('admin_settings.overlay.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(fn (): string => __t('admin_settings.overlay.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'users', column: 'email', ignorable: auth()->user())
                    ->live(debounce: 500),

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
                    ->visible(fn (Get $get): bool => filled($get('password'))),

                TextInput::make('currentPassword')
                    ->label(fn (): string => __t('admin_settings.overlay.current_password'))
                    ->belowContent(fn (): string => __t('admin_settings.overlay.current_password_help'))
                    ->password()
                    ->autocomplete('current-password')
                    ->currentPassword(guard: Filament::getAuthGuard())
                    ->visible(fn (Get $get): bool => filled($get('password')) || ($get('email') !== auth()->user()->email)),
            ]);
    }

    protected function integrationsTab(): Tab
    {
        return Tab::make('integrations')
            ->label(fn (): string => __t('admin_nav.integrations.nav'))
            ->icon(Heroicon::OutlinedKey)
            ->statePath('integrations')
            ->schema(Integrations::providerSections());
    }

    protected function linkOutTab(string $key, string $label, string|BackedEnum $icon, string $description, string $url): Tab
    {
        return Tab::make($key)
            ->label($label)
            ->icon($icon)
            ->schema([
                Text::make($description)->size('sm'),

                Actions::make([
                    Action::make("open_{$key}")
                        ->label(fn (): string => __t('admin_settings.overlay.open', ['page' => $label]))
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url($url),
                ]),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    protected function save(array $data): void
    {
        $profile = $data['profile'] ?? [];
        $user = auth()->user();

        $user->name = $profile['name'] ?? $user->name;
        $user->email = $profile['email'] ?? $user->email;

        if (filled($profile['password'] ?? null)) {
            $user->password = $profile['password'];
        }

        $user->save();

        if (Integrations::canAccess() && isset($data['integrations']) && (! Integrations::persist($data['integrations']))) {
            return;
        }

        Notification::make()->title(__t('admin_settings.overlay.saved'))->success()->send();
    }

    public function render(): View
    {
        return view('livewire.settings-panel');
    }
}
