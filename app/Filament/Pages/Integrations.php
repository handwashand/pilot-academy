<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use App\Models\AiProvider;
use App\Services\DeepL\DeepLClient;
use App\Services\Descript\DescriptClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Settings → Integrations: Descript (lesson video translation) and the text
 * translation providers — DeepL, ChatGPT, DeepSeek — an admin can switch on.
 *
 * An enabled text provider with a token adds a "Generate missing with …" button
 * to the Translate dialog for people given that right; Descript adds the
 * lesson's Translate video button the same way.
 *
 * The token is write-only. It is stored encrypted, the form never loads it
 * back, and a blank box keeps what is saved. Providers have fixed official
 * addresses (see AiProvider), so a token cannot be pointed at another host.
 * A token saved here for DeepL or Descript is used instead of the server's
 * .env values (DEEPL_*, DESCRIPT_API_TOKEN), which still work when nothing is
 * saved; once saved, this page's switch decides. A DeepL API Free key (ending in
 * ":fx") is sent to the Free host, any other key to the Pro host. Mail's
 * settings stay in .env only.
 */
class Integrations extends Page
{
    protected static ?string $cluster = Settings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 40;

    /** @var array<string, array<string, mixed>> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.integrations.nav');
    }

    public function getTitle(): string
    {
        return __t('admin_nav.integrations.nav');
    }

    /** Tokens spend money, so admins only. */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getSubheading(): ?string
    {
        // Said once for the page, rather than under every token box. Who may
        // use what is said by each group below.
        return __t('admin_integrations.token_help');
    }

    public function mount(): void
    {
        $state = [];

        foreach (array_keys(AiProvider::PROVIDERS) as $provider) {
            $row = AiProvider::for($provider);

            // The token is deliberately left out: it never comes back to the browser.
            $state[$provider] = [
                'enabled' => (bool) $row->enabled,
                'model' => $row->model,
                'api_key' => '',
                'scope' => AiProvider::SCOPE_EVERYONE,
                'shared_scope' => $row->scope ?? AiProvider::SCOPE_EVERYONE,
                'clear_shared' => false,
                'clear_own' => false,
            ];
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            // Grouped by what they translate, each group headed by who may use
            // it; the cards in a group sit in one even row.
            ->components([
                $this->group('text', [AiProvider::DEEPL, AiProvider::CHATGPT, AiProvider::DEEPSEEK]),
                $this->group('video', [AiProvider::DESCRIPT]),
                $this->group('course', [AiProvider::CLAUDE]),
            ])
            ->statePath('data');
    }

    /** @param  array<int, string>  $providers */
    private function group(string $name, array $providers): Section
    {
        return Section::make(fn (): string => __t("admin_integrations.groups.{$name}"))
            ->description(fn (): string => __t("admin_integrations.groups.{$name}_help"))
            ->contained(false)
            ->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->schema(array_map(fn (string $provider): Section => $this->providerSection($provider), $providers)),
            ]);
    }

    /**
     * Whether people can use the provider right now, read the way the buttons
     * that use it decide: DeepL and Descript fall back to the server's .env
     * when no token is saved here.
     */
    private function isOn(string $provider): bool
    {
        return match ($provider) {
            AiProvider::DESCRIPT => app(DescriptClient::class)->enabled(),
            AiProvider::DEEPL => ($saved = AiProvider::withToken(AiProvider::DEEPL))
                ? (new DeepLClient($saved->deeplSettings()))->enabled()
                : app(DeepLClient::class)->enabled(),
            default => AiProvider::usable($provider) !== null,
        };
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
                ->label(fn (): string => __t('admin_integrations.save'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        foreach (array_keys(AiProvider::PROVIDERS) as $provider) {
            $input = $data[$provider] ?? [];
            $row = AiProvider::for($provider);
            $own = AiProvider::personalFor($provider, $user);
            $token = trim((string) ($input['api_key'] ?? ''));
            $scope = $this->scopeFor($provider, $input['scope'] ?? null);

            if (! empty($input['clear_shared'])) {
                $row->api_key = null;
            }

            if (! empty($input['clear_own']) && $own->exists) {
                $own->delete();
                $own = AiProvider::personalFor($provider, $user);
            }

            // A new token goes where its owner chose: their own row, or the shared one.
            if ($token !== '' && $scope === AiProvider::SCOPE_PERSONAL) {
                $own->fill(['api_key' => $token, 'scope' => AiProvider::SCOPE_PERSONAL, 'added_by' => $user->id])->save();
            } elseif ($token !== '') {
                $row->fill(['api_key' => $token, 'scope' => $scope, 'added_by' => $user->id]);
            } elseif (filled($row->api_key)) {
                $row->scope = $this->scopeFor($provider, $input['shared_scope'] ?? null, shared: true);
            }

            $row->enabled = (bool) ($input['enabled'] ?? false);
            $row->model = filled($input['model'] ?? null) ? trim((string) $input['model']) : null;

            // Switching on needs a token someone can use: a shared one, or the
            // admin's own (then only they will see the buttons).
            if ($row->enabled && blank($row->api_key) && blank($own->api_key)) {
                Notification::make()
                    ->title(__t('admin_integrations.needs_token', ['provider' => $row->label()]))
                    ->danger()
                    ->send();

                // Typed tokens must not linger in the page data the browser
                // keeps; whatever was saved above stays saved.
                foreach (array_keys(AiProvider::PROVIDERS) as $key) {
                    $this->data[$key]['api_key'] = '';
                }

                return;
            }

            $row->save();
        }

        $this->mount();

        Notification::make()->title(__t('admin_integrations.saved'))->success()->send();
    }

    /**
     * A reach from the form, checked here because it arrives from the browser.
     * Descript has no personal tokens, and a shared row is never personal.
     */
    private function scopeFor(string $provider, mixed $scope, bool $shared = false): string
    {
        $allowed = array_keys($this->scopeOptions($provider, $shared));

        return in_array($scope, $allowed, true) ? $scope : AiProvider::SCOPE_EVERYONE;
    }

    /** @return array<string, string> */
    private function scopeOptions(string $provider, bool $shared = false): array
    {
        $scopes = [AiProvider::SCOPE_PERSONAL, AiProvider::SCOPE_ADMINS, AiProvider::SCOPE_EVERYONE];

        if ($shared || in_array($provider, AiProvider::SHARED_ONLY, true)) {
            $scopes = array_slice($scopes, 1);
        }

        return collect($scopes)->mapWithKeys(fn (string $scope): array => [$scope => __t("admin_integrations.scopes.{$scope}")])->all();
    }

    /** One line about the shared token: its ending, its reach and who added it. Never the token. */
    private function sharedTokenLine(string $provider): string
    {
        $row = AiProvider::for($provider);

        if (blank($row->api_key)) {
            return __t('admin_integrations.shared_none');
        }

        return __t('admin_integrations.shared_value', [
            'ending' => $row->tokenEnding(),
            'scope' => __t('admin_integrations.scopes.'.($row->scope ?? AiProvider::SCOPE_EVERYONE)),
            'name' => $row->addedBy?->name ?? __t('admin_integrations.added_by_unknown'),
        ]);
    }

    private function ownTokenLine(string $provider): string
    {
        $own = AiProvider::personalFor($provider, auth()->user());

        return filled($own->api_key)
            ? __t('admin_integrations.own_value', ['ending' => $own->tokenEnding()])
            : __t('admin_integrations.own_none');
    }

    private function providerSection(string $provider): Section
    {
        $label = AiProvider::PROVIDERS[$provider]['label'];
        $saved = AiProvider::withToken($provider) !== null;
        $sharedSaved = filled(AiProvider::for($provider)->api_key);
        $ownSaved = ! in_array($provider, AiProvider::SHARED_ONLY, true)
            && filled(AiProvider::personalFor($provider, auth()->user())->api_key);
        $on = $this->isOn($provider);

        return Section::make($label)
            // Its state at a glance, before reading any field.
            ->icon($on ? Heroicon::CheckCircle : Heroicon::OutlinedMinusCircle)
            ->iconColor($on ? 'success' : 'gray')
            ->description(fn (): string => __t('admin_integrations.status.'.match (true) {
                $on => 'on',
                $saved => 'off',
                default => 'not_connected',
            }))
            ->compact()
            // Cards in a row stay the same height whatever fields they carry.
            ->extraAttributes(['class' => 'pa-fill-row'])
            ->statePath($provider)
            ->schema([
                Toggle::make('enabled')
                    ->label(fn (): string => __t('admin_integrations.enabled', ['provider' => $label]))
                    // What it does, on demand: the card itself stays short.
                    ->hintIcon(Heroicon::OutlinedInformationCircle, tooltip: fn (): string => __t("admin_integrations.description.{$provider}")),

                // What is saved, as lines of text: never the token, only its last four characters.
                TextEntry::make('shared_token')
                    ->label(fn (): string => __t('admin_integrations.shared_token'))
                    ->state(fn (): string => $this->sharedTokenLine($provider)),

                Select::make('shared_scope')
                    ->label(fn (): string => __t('admin_integrations.shared_scope'))
                    ->options(fn (): array => $this->scopeOptions($provider, shared: true))
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->visible($sharedSaved),

                Checkbox::make('clear_shared')
                    ->label(fn (): string => __t('admin_integrations.clear_shared'))
                    ->visible($sharedSaved),

                TextEntry::make('own_token')
                    ->label(fn (): string => __t('admin_integrations.own_token'))
                    ->state(fn (): string => $this->ownTokenLine($provider))
                    ->visible(! in_array($provider, AiProvider::SHARED_ONLY, true)),

                Checkbox::make('clear_own')
                    ->label(fn (): string => __t('admin_integrations.clear_own'))
                    ->visible($ownSaved),

                TextInput::make('api_key')
                    ->label(fn (): string => __t('admin_integrations.token'))
                    ->password()
                    ->autocomplete('new-password')
                    ->maxLength(300)
                    ->placeholder(fn (): string => $saved ? __t('admin_integrations.token_saved') : __t('admin_integrations.token_empty')),

                Select::make('scope')
                    ->label(fn (): string => __t('admin_integrations.scope'))
                    ->helperText(fn (): string => __t('admin_integrations.scope_help'))
                    ->options(fn (): array => $this->scopeOptions($provider))
                    ->native(false)
                    ->selectablePlaceholder(false),

                Select::make('model')
                    ->visible(AiProvider::PROVIDERS[$provider]['model'] !== '')
                    ->label(fn (): string => __t('admin_integrations.model'))
                    ->options(AiProvider::PROVIDERS[$provider]['model_options'])
                    ->native(false)
                    ->placeholder(fn (): string => __t('admin_integrations.model_help', ['model' => AiProvider::PROVIDERS[$provider]['model']])),
            ]);
    }
}
