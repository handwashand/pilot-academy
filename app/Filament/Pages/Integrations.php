<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use App\Models\AiProvider;
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
        // Said once for the page, rather than under every token box.
        return __t('admin_integrations.subheading').' '.__t('admin_integrations.token_help');
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

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            // Small cards, two or three across, so the page stays tidy as integrations are added.
            ->components([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->schema(array_map(fn (string $provider): Section => $this->providerSection($provider), array_keys(AiProvider::PROVIDERS))),
            ])
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

        $this->mount();

        Notification::make()->title(__t('admin_integrations.saved'))->success()->send();
    }

    private function providerSection(string $provider): Section
    {
        $label = AiProvider::PROVIDERS[$provider]['label'];
        $saved = AiProvider::withToken($provider) !== null;

        return Section::make($label)
            ->compact()
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
