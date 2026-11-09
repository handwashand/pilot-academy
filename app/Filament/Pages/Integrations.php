<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use App\Models\AiGenerationSetting;
use App\Models\AiProvider;
use App\Services\DeepL\DeepLClient;
use App\Services\Descript\DescriptClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                'clear_key' => false,
            ];
        }

        $generation = AiGenerationSetting::current();
        $state['generation'] = [
            'default_provider' => $generation->default_provider,
            'fallback_enabled' => (bool) $generation->fallback_enabled,
            'daily_generation_limit' => $generation->daily_generation_limit,
            'monthly_budget_usd' => $generation->monthly_budget_usd,
        ];

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
                $this->generationSection(),
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

        foreach (array_keys(AiProvider::PROVIDERS) as $provider) {
            $input = $data[$provider] ?? [];
            $row = AiProvider::for($provider);

            if (! empty($input['clear_key'])) {
                $row->api_key = null;
            } elseif (filled($input['api_key'] ?? null)) {
                $row->api_key = trim((string) $input['api_key']);
            }

            $row->enabled = (bool) ($input['enabled'] ?? false);
            $row->model = filled($input['model'] ?? null) ? trim((string) $input['model']) : null;

            if ($row->enabled && blank($row->api_key)) {
                Notification::make()
                    ->title(__t('admin_integrations.needs_token', ['provider' => $row->label()]))
                    ->danger()
                    ->send();

                return;
            }

            $row->save();
        }

        $this->saveGeneration($data['generation'] ?? null);

        $this->mount();

        Notification::make()->title(__t('admin_integrations.saved'))->success()->send();
    }

    private function generationSection(): Section
    {
        $providers = [];

        foreach (AiProvider::GENERATION as $provider) {
            $providers[$provider] = AiProvider::PROVIDERS[$provider]['label'];
        }

        return Section::make(fn (): string => __t('admin_integrations.generation.heading'))
            ->description(fn (): string => __t('admin_integrations.generation.help'))
            ->statePath('generation')
            ->columns(2)
            ->schema([
                Select::make('default_provider')
                    ->label(fn (): string => __t('admin_integrations.generation.default_provider'))
                    ->helperText(fn (): string => __t('admin_integrations.generation.default_provider_help'))
                    ->options($providers)
                    ->native(false)
                    ->placeholder(fn (): string => __t('admin_integrations.generation.none')),

                Toggle::make('fallback_enabled')
                    ->label(fn (): string => __t('admin_integrations.generation.fallback'))
                    ->helperText(fn (): string => __t('admin_integrations.generation.fallback_help')),

                TextInput::make('daily_generation_limit')
                    ->label(fn (): string => __t('admin_integrations.generation.daily_limit'))
                    ->helperText(fn (): string => __t('admin_integrations.generation.daily_limit_help'))
                    ->numeric()
                    ->rules(['nullable'])
                    ->minValue(1)
                    ->maxValue(500),

                TextInput::make('monthly_budget_usd')
                    ->label(fn (): string => __t('admin_integrations.generation.budget'))
                    ->helperText(fn (): string => __t('admin_integrations.generation.budget_help'))
                    ->numeric()
                    ->rules(['nullable'])
                    ->minValue(0)
                    ->maxValue(100000)
                    ->step(0.01),
            ]);
    }

    /** @param  array<string, mixed>|null  $generation */
    private function saveGeneration(?array $generation): void
    {
        if ($generation === null) {
            return;
        }

        $provider = $generation['default_provider'] ?? null;
        $limit = $generation['daily_generation_limit'] ?? null;
        $budget = $generation['monthly_budget_usd'] ?? null;

        AiGenerationSetting::current()->fill([
            'default_provider' => in_array($provider, AiProvider::GENERATION, true) ? $provider : null,
            'fallback_enabled' => (bool) ($generation['fallback_enabled'] ?? false),
            'daily_generation_limit' => is_numeric($limit) && (int) $limit >= 1 ? (int) $limit : null,
            'monthly_budget_usd' => is_numeric($budget) && (float) $budget >= 0
                ? number_format((float) $budget, 2, '.', '')
                : null,
        ])->save();
    }

    private function providerSection(string $provider): Section
    {
        $label = AiProvider::PROVIDERS[$provider]['label'];
        $saved = AiProvider::withToken($provider) !== null;
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

                TextInput::make('api_key')
                    ->label(fn (): string => __t('admin_integrations.token'))
                    ->password()
                    ->autocomplete('new-password')
                    ->maxLength(300)
                    ->placeholder(fn (): string => $saved ? __t('admin_integrations.token_saved') : __t('admin_integrations.token_empty')),

                Checkbox::make('clear_key')
                    ->label(fn (): string => __t('admin_integrations.clear_token'))
                    ->visible($saved),

                Select::make('model')
                    ->visible(AiProvider::PROVIDERS[$provider]['model'] !== '')
                    ->label(fn (): string => __t('admin_integrations.model'))
                    ->options(AiProvider::PROVIDERS[$provider]['model_options'])
                    ->native(false)
                    ->placeholder(fn (): string => __t('admin_integrations.model_help', ['model' => AiProvider::PROVIDERS[$provider]['model']])),
            ]);
    }
}
