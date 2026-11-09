<?php

namespace App\Filament\Pages;

use App\Actions\GenerateCourseWithAi;
use App\Filament\Resources\Courses\CourseResource;
use App\Models\AiGenerationSetting;
use App\Models\AiProvider;
use App\Models\Product;
use App\Models\User;
use App\Services\Llm\LlmException;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Draft a course from notes, through ChatGPT or DeepSeek as already connected
 * under Settings → Integrations. The token never reaches this form.
 */
class GenerateCourse extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 6;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.course_generation.nav');
    }

    public function getTitle(): string
    {
        return __t('admin_nav.course_generation.nav');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.content');
    }

    public function getSubheading(): ?string
    {
        return __t('admin_pages.course_generation.subheading');
    }

    /** Admins, and creators an admin has allowed to spend the providers. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || ($user?->isCreator() && $user->hasPermission(User::PERMISSION_AI_GENERATE)));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $settings = AiGenerationSetting::current();
        $provider = $settings->default_provider;

        if (! is_string($provider) || ! AiProvider::availableForGeneration($provider)) {
            $provider = array_key_first(AiProvider::generationChoices());
        }

        $this->form->fill([
            'provider' => $provider,
            'model' => is_string($provider) ? AiProvider::preferredGenerationModel($provider) : null,
            'mode' => GenerateCourseWithAi::MODE_FULL,
            'allow_fallback' => (bool) $settings->fallback_enabled,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('provider')
                    ->label(fn (): string => __t('admin_pages.course_generation.provider'))
                    ->options(fn (): array => AiProvider::generationChoices())
                    ->helperText(fn (): ?string => AiProvider::generationChoices() === []
                        ? __t('admin_pages.course_generation.none')
                        : null)
                    ->native(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $set('model', $state ? AiProvider::preferredGenerationModel($state) : null);
                    }),

                Select::make('model')
                    ->label(fn (): string => __t('admin_pages.course_generation.model'))
                    ->options(fn (Get $get): array => AiProvider::modelsForGeneration((string) $get('provider')))
                    ->native(false)
                    ->required(),

                Select::make('mode')
                    ->label(fn (): string => __t('admin_pages.course_generation.mode'))
                    ->helperText(fn (): string => __t('admin_pages.course_generation.mode_help'))
                    ->options([
                        GenerateCourseWithAi::MODE_FULL => __t('admin_pages.course_generation.mode_full'),
                    ])
                    ->default(GenerateCourseWithAi::MODE_FULL)
                    ->native(false)
                    ->required(),

                Select::make('product_id')
                    ->label(fn (): string => __t('admin_pages.course_generation.product'))
                    ->options(fn (): array => $this->productOptions())
                    ->native(false)
                    ->searchable()
                    ->visible(fn (): bool => $this->productOptions() !== [])
                    ->required(fn (): bool => (bool) auth()->user()?->isCreator()),

                Textarea::make('brief')
                    ->label(fn (): string => __t('admin_pages.course_generation.brief'))
                    ->helperText(fn (): string => __t('admin_pages.course_generation.brief_help'))
                    ->rows(8)
                    ->required()
                    ->maxLength(4000)
                    ->columnSpanFull(),

                Checkbox::make('allow_fallback')
                    ->label(fn (): string => __t('admin_pages.course_generation.fallback'))
                    ->helperText(fn (): string => __t('admin_pages.course_generation.fallback_help'))
                    ->visible(fn (): bool => (bool) AiGenerationSetting::current()->fallback_enabled),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('generate')
                ->footer([
                    Actions::make([
                        Action::make('generate')
                            ->label(fn (): string => __t('admin_pages.course_generation.submit'))
                            ->submit('generate'),
                    ]),
                ]),
        ]);
    }

    public function generate(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $course = app(GenerateCourseWithAi::class)->handle(
                $user,
                (string) $data['provider'],
                (string) $data['model'],
                (string) $data['brief'],
                (string) $data['mode'],
                filled($data['product_id'] ?? null) ? (int) $data['product_id'] : null,
                (bool) ($data['allow_fallback'] ?? false),
            );
        } catch (LlmException $exception) {
            Notification::make()->title($this->failure($exception))->danger()->send();

            return;
        }

        Notification::make()->title(__t('admin_pages.course_generation.saved'))->success()->send();

        $this->redirect(CourseResource::getUrl('edit', ['record' => $course]));
    }

    private function failure(LlmException $exception): string
    {
        return match ($exception->errorCode) {
            'unavailable' => __t('admin_pages.course_generation.unavailable'),
            'unsupported_capability' => __t('admin_pages.course_generation.unsupported'),
            'limit' => __t('admin_pages.course_generation.limit'),
            'budget' => __t('admin_pages.course_generation.budget'),
            'invalid_response' => __t('admin_pages.course_generation.invalid'),
            'connection' => __t('admin_pages.course_generation.unreachable'),
            'forbidden' => __t('admin_pages.course_generation.forbidden'),
            'product' => __t('admin_pages.course_generation.product_invalid'),
            'brief' => __t('admin_pages.course_generation.brief_short'),
            default => $exception->isAuthProblem()
                ? __t('admin_pages.course_generation.rejected')
                : __t('admin_pages.course_generation.failed'),
        };
    }

    /** @return array<int|string, string> */
    private function productOptions(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $query = Product::query()->orderBy('name');

        if (! $user->isAdmin()) {
            $query->whereIn('id', $user->products()->pluck('products.id'));
        }

        return $query->pluck('name', 'id')->all();
    }
}
