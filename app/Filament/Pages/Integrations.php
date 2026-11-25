<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use App\Models\AiProvider;
use App\Services\DeepL\DeepLClient;
use App\Services\Descript\DescriptClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Settings → Integrations: the text translation providers — DeepL, ChatGPT,
 * DeepSeek, Claude — and Descript (lesson video translation).
 *
 * Any text provider switched on here, with a token, drafts translations
 * wherever the academy has Translate (TranslateContentAction::ENGINES), for
 * admins and for people given its right; Descript adds the lesson's Translate
 * video button the same way.
 *
 * One short card per provider: the switch in its header, one token box whose
 * hint is the saved token's last four characters, one line saying who can use
 * the saved token with a Remove link, who can use it, and the model.
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
            // Who can use it starts at the saved shared token's reach.
            $state[$provider] = [
                'enabled' => (bool) $row->enabled,
                'model' => $row->model,
                'api_key' => '',
                'scope' => filled($row->api_key) ? ($row->scope ?? AiProvider::SCOPE_EVERYONE) : AiProvider::SCOPE_EVERYONE,
            ];
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            // Grouped by what they translate, each group headed by who may use
            // it; two cards to a row.
            ->components([
                $this->group('text', [AiProvider::DEEPL, AiProvider::CHATGPT, AiProvider::DEEPSEEK, AiProvider::CLAUDE]),
                $this->group('video', [AiProvider::DESCRIPT]),
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
                Grid::make(['default' => 1, 'lg' => 2])
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

            // A new token goes where its owner chose: their own row, or the
            // shared one. With the box empty, the choice is the shared token's
            // reach (a shared token is never "just me").
            if ($token !== '' && $scope === AiProvider::SCOPE_PERSONAL) {
                $own->fill(['api_key' => $token, 'scope' => AiProvider::SCOPE_PERSONAL, 'added_by' => $user->id])->save();
            } elseif ($token !== '') {
                $row->fill(['api_key' => $token, 'scope' => $scope, 'added_by' => $user->id]);
            } elseif (filled($row->api_key) && $scope !== AiProvider::SCOPE_PERSONAL) {
                $row->scope = $scope;
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
     * Descript has no personal tokens.
     */
    private function scopeFor(string $provider, mixed $scope): string
    {
        return in_array($scope, array_keys($this->scopeOptions($provider)), true) ? $scope : AiProvider::SCOPE_EVERYONE;
    }

    /** @return array<string, string> */
    private function scopeOptions(string $provider): array
    {
        $scopes = [AiProvider::SCOPE_PERSONAL, AiProvider::SCOPE_ADMINS, AiProvider::SCOPE_EVERYONE];

        if (in_array($provider, AiProvider::SHARED_ONLY, true)) {
            $scopes = array_slice($scopes, 1);
        }

        return collect($scopes)->mapWithKeys(fn (string $scope): array => [$scope => __t("admin_integrations.scopes.{$scope}")])->all();
    }

    /**
     * Under the token box: who can use the saved shared token and who added
     * it, and the admin's own token, each with a Remove link. Never the token.
     *
     * @return array<int, Text|Action>
     */
    private function savedTokenLines(string $provider): array
    {
        $label = AiProvider::PROVIDERS[$provider]['label'];
        $row = AiProvider::for($provider);
        $lines = [];

        if (filled($row->api_key)) {
            $lines[] = Text::make(__t('admin_integrations.shared_line', [
                'scope' => __t('admin_integrations.scopes.'.($row->scope ?? AiProvider::SCOPE_EVERYONE)),
                'name' => $row->addedBy?->name ?? __t('admin_integrations.added_by_unknown'),
            ]));
            $lines[] = $this->removeAction('remove_shared', $provider, $label);
        }

        if (! in_array($provider, AiProvider::SHARED_ONLY, true)) {
            $own = AiProvider::personalFor($provider, auth()->user());

            if (filled($own->api_key)) {
                $lines[] = Text::make(__t('admin_integrations.own_line', ['ending' => $own->tokenEnding()]));
                $lines[] = $this->removeAction('remove_own', $provider, $label);
            }
        }

        return $lines;
    }

    /** Remove a saved token at once, after a confirmation: the shared one, or the admin's own. */
    private function removeAction(string $name, string $provider, string $label): Action
    {
        $shared = $name === 'remove_shared';

        return Action::make($name)
            ->label(fn (): string => __t('admin_integrations.remove'))
            ->link()
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (): string => __t($shared ? 'admin_integrations.remove_shared_heading' : 'admin_integrations.remove_own_heading', ['provider' => $label]))
            ->modalDescription(fn (): string => __t('admin_integrations.remove_description', ['provider' => $label]))
            ->action(function () use ($shared, $provider): void {
                if ($shared) {
                    $row = AiProvider::for($provider);
                    $row->api_key = null;
                    $row->save();
                } else {
                    AiProvider::personalFor($provider, auth()->user())->delete();
                }

                $this->mount();

                Notification::make()->title(__t('admin_integrations.removed'))->success()->send();
            });
    }

    private function providerSection(string $provider): Section
    {
        $label = AiProvider::PROVIDERS[$provider]['label'];
        $saved = AiProvider::withToken($provider) !== null;
        $shared = AiProvider::for($provider);
        $own = in_array($provider, AiProvider::SHARED_ONLY, true) ? null : AiProvider::personalFor($provider, auth()->user());
        // The hint in the token box: the shared token's ending, else the admin's own.
        $ending = filled($shared->api_key) ? $shared->tokenEnding() : (filled($own?->api_key) ? $own->tokenEnding() : null);
        $on = $this->isOn($provider);
        $hasModel = AiProvider::PROVIDERS[$provider]['model'] !== '';

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
            // What it does, on demand, and the switch: the card itself stays short.
            ->afterHeader([
                Icon::make(Heroicon::OutlinedInformationCircle)
                    ->color('gray')
                    ->tooltip(fn (): string => __t("admin_integrations.description.{$provider}")),
                Toggle::make('enabled')
                    ->label(fn (): string => __t('admin_integrations.on'))
                    ->inline(),
            ])
            ->schema([
                TextInput::make('api_key')
                    ->label(fn (): string => __t('admin_integrations.token'))
                    ->password()
                    ->autocomplete('new-password')
                    ->maxLength(300)
                    ->placeholder(fn (): string => $ending !== null
                        ? __t('admin_integrations.token_saved', ['ending' => $ending])
                        : __t('admin_integrations.token_empty'))
                    ->belowContent(fn (): array => $this->savedTokenLines($provider)),

                Grid::make(['default' => 1, 'sm' => $hasModel ? 2 : 1])
                    ->schema([
                        Select::make('scope')
                            ->label(fn (): string => __t('admin_integrations.scope'))
                            ->hintIcon(Heroicon::OutlinedInformationCircle, tooltip: fn (): string => __t('admin_integrations.scope_help'))
                            ->options(fn (): array => $this->scopeOptions($provider))
                            ->native(false)
                            ->selectablePlaceholder(false),

                        // Defaults to the provider's middle model; a dearer one
                        // says how much more it costs as soon as it is picked.
                        Select::make('model')
                            ->visible($hasModel)
                            ->label(fn (): string => __t('admin_integrations.model'))
                            ->options(AiProvider::PROVIDERS[$provider]['model_options'])
                            ->native(false)
                            ->live()
                            ->placeholder(fn (): string => __t('admin_integrations.model_help', [
                                'model' => AiProvider::modelLabel($provider, AiProvider::PROVIDERS[$provider]['model']),
                            ]))
                            ->belowContent(fn (Get $get): array => ($times = AiProvider::costMultiple($provider, $get('model'))) === null ? [] : [
                                Text::make(__t('admin_integrations.model_warning', [
                                    'model' => AiProvider::modelLabel($provider, (string) $get('model')),
                                    'times' => $times,
                                    'default' => AiProvider::modelLabel($provider, AiProvider::PROVIDERS[$provider]['model']),
                                ]))
                                    ->color('warning')
                                    ->icon(Heroicon::OutlinedExclamationTriangle),
                            ]),
                    ]),
            ]);
    }
}
