<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Actions\AddLessonsToCourse;
use App\Actions\FindContentProblems;
use App\Filament\Resources\Lessons\Tables\LessonPickerTable;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\Translator;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // The writing on the left, the settings beside it — so the page
            // is not one long column of fields stretched across the screen.
            ->columns(['lg' => 3])
            ->components([
                Group::make([
                    Section::make(__t('admin_common.course'))
                        ->schema([
                            TextInput::make('title')
                                ->label(__t('admin_courses.form.title'))
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            Textarea::make('description')
                                ->label(__t('admin_courses.form.description'))
                                ->rows(3)
                                ->columnSpanFull(),

                            Select::make('product_id')
                                ->label(__t('admin_courses.form.product'))
                                ->relationship('product', 'name', function ($query) {
                                    $user = auth()->user();

                                    // A creator may only file a course under a product they own.
                                    return $user?->isCreator()
                                        ? $query->whereIn('products.id', $user->products()->pluck('products.id'))
                                        : $query;
                                })
                                ->searchable()
                                ->preload()
                                ->required(fn (): bool => (bool) auth()->user()?->isCreator())
                                ->default(fn () => auth()->user()?->isCreator() ? auth()->user()->products()->value('products.id') : null)
                                ->helperText(__t('admin_courses.form.product_help'))
                                ->rules([
                                    fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                        $user = auth()->user();

                                        if ($user && $user->isCreator() && ! $user->products()->whereKey($value)->exists()) {
                                            $fail(__t('admin_courses.form.product_not_yours'));
                                        }
                                    },
                                ]),
                        ]),

                    // Fill the course while creating it. After that the Lessons
                    // tab does the same job, with ordering and removing.
                    Section::make(__t('admin_nav.tabs.lessons'))
                        ->visibleOn('create')
                        ->schema([
                            ModalTableSelect::make('lesson_ids')
                                ->label(__t('admin_courses.form.lessons'))
                                ->hiddenLabel()
                                ->helperText(__t('admin_courses.form.lessons_help'))
                                ->multiple()
                                ->badge()
                                ->tableConfiguration(LessonPickerTable::class)
                                ->getOptionLabelsUsing(fn (array $values): array => Lesson::query()
                                    ->whereIn('id', $values)
                                    ->pluck('title', 'id')
                                    ->all())
                                // Two lessons with one slug cannot share a course.
                                ->rules([
                                    fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                        $clashes = AddLessonsToCourse::clashes(new Course, Lesson::query()->whereIn('id', (array) $value)->get());

                                        if ($clashes->isNotEmpty()) {
                                            $fail(__t('admin_courses.lessons_tab.slug_clash_body', ['lessons' => $clashes->implode(', ')]));
                                        }
                                    },
                                ]),
                        ])
                        ->columnSpanFull(),

                    Section::make(__t('admin_courses.form.final_section'))
                        ->description(__t('admin_courses.form.final_section_help'))
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            Toggle::make('final_quiz_enabled')
                                ->label(__t('admin_courses.form.final_enabled'))
                                ->live()
                                ->columnSpanFull()
                                ->helperText(__t('admin_courses.form.final_enabled_help')),

                            TextInput::make('pass_percent')
                                ->label(__t('admin_courses.form.pass_percent'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100)
                                ->default(80)
                                ->required()
                                ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),

                            TextInput::make('questions_per_attempt')
                                ->label(__t('admin_courses.form.questions_per_attempt'))
                                ->numeric()
                                ->minValue(1)
                                ->placeholder(__t('admin_common.all'))
                                ->helperText(__t('admin_courses.form.questions_per_attempt_help'))
                                ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),

                            TextInput::make('final_quiz_max_attempts')
                                ->label(__t('admin_common.max_attempts'))
                                ->numeric()
                                ->minValue(1)
                                ->placeholder(__t('admin_courses.form.unlimited'))
                                ->helperText(__t('admin_courses.form.max_attempts_help'))
                                ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),

                            FileUpload::make('certificate_template')
                                ->label(__t('admin_courses.form.certificate_template'))
                                ->image()
                                ->disk('public')
                                ->directory('certificate-templates')
                                ->visibility('public')
                                ->maxSize(8192)
                                ->columnSpanFull()
                                ->helperText(__t('admin_courses.form.certificate_template_help'))
                                ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make(__t('admin_nav.groups.settings'))
                        ->schema([
                            Select::make('status')
                                ->label(__t('admin_common.status'))
                                ->options(Course::statusLabels())
                                ->default(Course::STATUS_DRAFT)
                                ->required()
                                // New courses are always drafts — publishing is a separate,
                                // deliberate step from the course list.
                                ->disabled(fn (string $operation): bool => $operation === 'create')
                                ->helperText(__t('admin_courses.form.status_help'))
                                ->rules([
                                    fn (?Course $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                                        if ($value === Course::STATUS_PUBLISHED && $record && ! $record->canBePublished()) {
                                            $fail(__t('admin_courses.form.needs_a_lesson'));

                                            return;
                                        }

                                        // Only when it is going live now: a course already
                                        // live must stay editable, or nobody could save the
                                        // rest of the form while fixing it.
                                        if ($value === Course::STATUS_PUBLISHED && $record && ! $record->isPublished()) {
                                            $problems = app(FindContentProblems::class)->forCourse($record);

                                            if ($problems->isNotEmpty()) {
                                                $fail(__t('admin_courses.form.fix_first', ['problems' => FindContentProblems::plainList($problems)]));
                                            }
                                        }
                                    },
                                ]),

                            TextInput::make('slug')
                                ->label(__t('admin_courses.form.slug'))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->helperText(__t('admin_courses.form.slug_help')),

                            static::writtenIn(),

                            Select::make('level')
                                ->label(__t('admin_common.level'))
                                ->options(Course::levelLabels())
                                ->default('beginner')
                                ->required(),

                            Select::make('audience')
                                ->label(__t('admin_courses.form.audience'))
                                ->options(Course::audienceLabels())
                                ->placeholder(__t('admin_courses.form.audience_none'))
                                ->helperText(__t('admin_courses.form.audience_help')),

                            TextInput::make('duration_minutes')
                                ->label(__t('admin_common.duration_minutes'))
                                ->numeric()
                                ->minValue(0),

                            TextInput::make('sort_order')
                                ->label(__t('admin_courses.form.sort_order'))
                                ->numeric()
                                ->default(0)
                                ->helperText(__t('admin_courses.form.sort_order_help')),

                            FileUpload::make('thumbnail')
                                ->label(__t('admin_courses.form.thumbnail'))
                                ->image()
                                ->disk('public')
                                ->directory('course-thumbnails')
                                ->visibility('public')
                                ->maxSize(2048)
                                ->helperText(__t('admin_courses.form.thumbnail_help')),

                            // The automatic cover for lessons without their own:
                            // each option shows a swatch of what it looks like.
                            Select::make('cover_pattern')
                                ->label(__t('admin_courses.form.cover_pattern'))
                                ->options(fn (): array => collect(Course::coverPatternLabels())
                                    ->mapWithKeys(fn (string $label, string $pattern): array => [
                                        $pattern => static::coverSwatch(Course::coverThemeFor($pattern, 'pilot'), $label),
                                    ])->all())
                                ->allowHtml()
                                ->native(false)
                                ->placeholder(__t('labels.cover_pattern.dots'))
                                ->in(Course::COVER_PATTERNS),

                            Select::make('cover_color')
                                ->label(__t('admin_courses.form.cover_color'))
                                ->options(fn (): array => collect(Course::coverColorLabels())
                                    ->mapWithKeys(fn (string $label, string $color): array => [
                                        $color => static::coverSwatch(Course::coverThemeFor('dots', $color), $label),
                                    ])->all())
                                ->allowHtml()
                                ->native(false)
                                ->placeholder(__t('labels.cover_color.pilot'))
                                ->in(array_keys(Course::COVER_COLORS))
                                ->helperText(__t('admin_courses.form.cover_help')),

                            // Audit information, never edited: who wrote the
                            // course and when. Courses from before the column
                            // existed say so instead of guessing.
                            TextEntry::make('created_by_on')
                                ->hiddenLabel()
                                ->state(fn (?Course $record): ?string => $record?->creator
                                    ? __t('admin_courses.form.created_by_on', [
                                        'name' => $record->creator->name,
                                        'date' => $record->created_at?->isoFormat('LL'),
                                    ])
                                    : ($record ? __t('admin_courses.form.created_by_unknown') : null))
                                ->dehydrated(false)
                                ->visible(fn (?Course $record): bool => (bool) $record)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /**
     * The language the content is written in — shared with the lesson form.
     * English unless the trainer writes in another; Translate adds the rest.
     */
    public static function writtenIn(): Select
    {
        $translator = app(Translator::class);

        return Select::make('language')
            ->label(__t('admin_courses.form.written_in'))
            ->options(function () use ($translator): array {
                $languages = $translator->activeLanguages()->pluck('native_name', 'code')->all();

                // No languages set up yet: the default is still a valid choice.
                return $languages !== [] ? $languages : [$translator->defaultCode() => strtoupper($translator->defaultCode())];
            })
            ->default(fn (): string => $translator->defaultCode())
            // Saved before this field existed: written in the default language.
            ->afterStateHydrated(function (Select $component, $state) use ($translator): void {
                if (blank($state)) {
                    $component->state($translator->defaultCode());
                }
            })
            ->required()
            ->helperText(__t('admin_courses.form.written_in_help'));
    }

    /**
     * A picker option: a small cover swatch, then the name. Inline styles,
     * because the panel theme does not scan this folder for utilities. The
     * values come from Course::coverThemeFor(), never from user input.
     *
     * @param  array{pattern: string, from: string, to: string}  $theme
     */
    public static function coverSwatch(array $theme, string $label): string
    {
        return '<span style="display:inline-flex;align-items:center;gap:.6rem">'
            .'<span style="flex:none;width:72px;height:34px;border-radius:6px;'
            // Centred, so a large tile (route, transit) shows its motif, not a corner.
            .'background:url('.e($theme['pattern']).') center,linear-gradient(135deg,'.e($theme['from']).','.e($theme['to']).')"></span>'
            .e($label).'</span>';
    }
}
