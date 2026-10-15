<?php

namespace App\Filament\Resources\CaseStudies\Schemas;

use App\Models\CaseStudy;
use App\Models\Lesson;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CaseStudyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // One form, not four cards scattered across the page: the
                // basics, the numbered steps and the related content below
                // them are all one piece of writing, so they read as one now
                // — full width, with the steps and related content still
                // marked off by their own inner heading so it is still clear
                // where one part ends and the next begins.
                Section::make(__t('admin_case_studies.sections.main'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label(__t('admin_common.product'))
                            ->relationship('product', 'name', function ($query) {
                                $user = auth()->user();

                                return $user?->isCreator()
                                    ? $query->whereIn('products.id', $user->products()->pluck('products.id'))
                                    : $query;
                            })
                            ->searchable()
                            ->preload()
                            ->required(fn (): bool => (bool) auth()->user()?->isCreator())
                            ->default(fn () => auth()->user()?->isCreator() ? auth()->user()->products()->value('products.id') : null)
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                    $user = auth()->user();

                                    if ($user && $user->isCreator() && ! $user->products()->whereKey($value)->exists()) {
                                        $fail(__t('admin_case_studies.form.own_product'));
                                    }
                                },
                            ]),

                        TextInput::make('title')
                            ->label(__t('admin_common.title'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label(__t('admin_common.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('short_problem')
                            ->label(__t('admin_case_studies.form.short_problem'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('industry')
                            ->label(__t('admin_case_studies.form.industry'))
                            ->maxLength(120)
                            ->datalist(['Delivery', 'Field service', 'Fuel logistics', 'Passenger transport', 'Construction']),

                        TagsInput::make('features_used')
                            ->label(__t('admin_case_studies.form.features'))
                            ->placeholder(__t('admin_case_studies.form.add_feature'))
                            // Pilot's own names, and the same spelling the seeded
                            // studies use — the listing filter matches them exactly.
                            ->suggestions(['GeoZones', 'Notifications', 'Reports', 'History', 'Speed control', 'Fuel sensors', 'Sensors', 'Object groups']),

                        // One row of four, not two rows of two: these are
                        // short, glanceable settings, not writing — grouped
                        // so they read as a strip rather than taking as much
                        // vertical space as the longer fields around them.
                        Grid::make(4)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('difficulty')
                                    ->label(__t('admin_case_studies.form.difficulty'))
                                    ->options(CaseStudy::difficultyLabels())
                                    ->default(CaseStudy::DIFFICULTY_INTERMEDIATE)
                                    ->required(),

                                TextInput::make('implementation_time')
                                    ->label(__t('admin_case_studies.form.implementation_time'))
                                    ->maxLength(120)
                                    ->placeholder('2-4 hours'),

                                TextInput::make('sort_order')
                                    ->label(__t('admin_courses.form.sort_order'))
                                    ->numeric()
                                    ->default(0),

                                Select::make('status')
                                    ->label(__t('admin_common.status'))
                                    ->options(CaseStudy::statusLabels())
                                    ->default(CaseStudy::STATUS_DRAFT)
                                    ->disabled(fn (string $operation): bool => $operation === 'create')
                                    ->required()
                                    ->rules([
                                        fn (?CaseStudy $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                                            if ($value === CaseStudy::STATUS_PUBLISHED && $record && ! $record->canBePublished()) {
                                                $fail(__t('admin_case_studies.form.before_publishing'));
                                            }
                                        },
                                    ]),
                            ]),

                        // No "Written in" field to fill: the study takes the
                        // language the editor is writing in right now — the
                        // panel's own current locale, which is their own
                        // account language whenever they have one set
                        // (SetLocale). A Russian-speaking creator with the
                        // panel in Russian gets a Russian study, not English
                        // by default; only an editor with no language
                        // preference of their own, on the site's own default
                        // language, gets that default. If the record already
                        // has a language of its own — every study written
                        // before this changed does — that value is left
                        // alone; this only decides what a brand new one gets.
                        Hidden::make('language')
                            ->default(fn (): string => app()->getLocale()),

                        static::studySections(),

                        static::relatedContent(),
                    ]),

                Section::make(__t('admin_case_studies.sections.privacy'))
                    ->description(__t('admin_case_studies.sections.privacy_hint'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_anonymized')
                            ->label(__t('admin_case_studies.form.anonymized'))
                            ->default(true),

                        Toggle::make('is_customer_approved')
                            ->label(__t('admin_case_studies.form.customer_approved')),

                        Textarea::make('source_note')
                            ->label(__t('admin_case_studies.form.source_note'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('performance_claim_note')
                            ->label(__t('admin_case_studies.form.performance_note'))
                            ->rows(3)
                            ->helperText(__t('admin_case_studies.form.performance_note_hint'))
                            ->columnSpanFull(),
                    ]),

                // Full width too, to match: once everything else spans the
                // page, a lone half-width box here would just leave empty
                // space beside it rather than reading as deliberate.
                Section::make(__t('admin_case_studies.sections.media'))
                    ->description(__t('admin_case_studies.sections.media_hint'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        static::mediaSelect('cover_media_item_id', __t('admin_case_studies.form.cover')),
                        static::mediaSelect('diagram_media_item_id', __t('admin_case_studies.form.diagram')),
                    ]),
            ]);
    }

    /**
     * The nine study steps, as one inner section inside the merged form — see
     * configure(). The editor writes under the same headings a partner
     * reads — see CaseStudy::sections() and academy.case_studies.sections.
     *
     * Nine fields in a row used to run together with nothing between them.
     * Each step is its own bordered, numbered sub-section — spacing and a
     * heading do the separating, not new fields. The four the study cannot
     * publish without (scenario, outcome, configuration, verification) are
     * always here; the other five start hidden unless the record already has
     * something written in them, and are brought in with "Add step" at the
     * end — see numberedStep() for why that is safe to hide and unhide.
     */
    protected static function studySections(): Section
    {
        return Section::make(__t('admin_case_studies.sections.study'))
            ->columnSpanFull()
            ->schema(function (): array {
                $required = [
                    ['scenario_problem', 'scenario', 1],
                    ['desired_outcome', 'outcome', 2],
                    ['configuration_steps', 'configuration', 5],
                    ['testing_verification', 'verification', 6],
                ];

                $optional = [
                    ['prerequisites', 'prerequisites', 3],
                    ['pilot_features', 'features', 4],
                    ['expected_results', 'results', 7],
                    ['troubleshooting', 'troubleshooting', 8],
                    ['adaptation', 'adaptation', 9],
                ];

                // Step-by-step configuration and expected results are the two
                // steps partners actually screenshot in practice (the
                // "before/after" of a setup); the other seven keep the
                // paperclip-in-text option only, so the form is not nine
                // upload boxes deep.
                $withGallery = ['configuration_steps', 'expected_results'];

                return [
                    // A virtual field, nowhere in the database: just which
                    // optional boxes are open right now. A ->default() here
                    // would only ever apply on Create — Filament skips a
                    // field's default on Edit, where state comes from the
                    // record instead — so this has to be worked out in
                    // afterStateHydrated(), which runs both times.
                    Hidden::make('active_optional_steps')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?CaseStudy $record) use ($optional): void {
                            $set('active_optional_steps', collect($optional)
                                ->reject(fn (array $step) => blank($record?->{$step[0]}))
                                ->map(fn (array $step): string => $step[1])
                                ->values()
                                ->all());
                        }),

                    ...collect($required)
                        ->map(fn (array $step): Section => static::numberedStep(...$step, withGallery: in_array($step[0], $withGallery, true)))
                        ->all(),

                    ...collect($optional)
                        ->map(fn (array $step): Section => static::numberedStep(...$step, withGallery: in_array($step[0], $withGallery, true), optional: true))
                        ->all(),

                    Select::make('add_step')
                        ->label(__t('admin_case_studies.form.add_step'))
                        ->placeholder(__t('admin_case_studies.form.add_step_placeholder'))
                        ->native(false)
                        ->dehydrated(false)
                        ->live()
                        ->options(fn (Get $get): array => collect($optional)
                            ->reject(fn (array $step) => in_array($step[1], (array) $get('active_optional_steps'), true))
                            ->mapWithKeys(fn (array $step): array => [$step[1] => __t("academy.case_studies.sections.{$step[1]}")])
                            ->all())
                        ->visible(fn (Get $get): bool => count((array) $get('active_optional_steps')) < count($optional))
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                            if (blank($state)) {
                                return;
                            }

                            $set('active_optional_steps', [...(array) $get('active_optional_steps'), $state]);
                            $set('add_step', null);
                        })
                        ->columnSpanFull(),
                ];
            });
    }

    /** Related lessons and documentation links, as the last inner section inside the merged form. */
    protected static function relatedContent(): Section
    {
        return Section::make(__t('admin_case_studies.sections.related'))
            ->columnSpanFull()
            ->columns(2)
            ->schema([
                Select::make('related_lesson_ids')
                    ->label(__t('admin_case_studies.form.related_lessons'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Lesson::query()
                        ->when(auth()->user()?->isCreator(), fn (Builder $query) => $query->whereHas('course', fn (Builder $course) => $course->whereIn('product_id', auth()->user()->products()->pluck('products.id'))))
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all())
                    ->columnSpanFull(),

                Repeater::make('related_links')
                    ->label(__t('admin_case_studies.form.links'))
                    ->defaultItems(0)
                    ->addActionLabel(__t('admin_case_studies.form.add_link'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label(__t('admin_common.title'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('url')
                            ->label(__t('admin_case_studies.form.link_url'))
                            ->url()
                            ->required()
                            ->maxLength(2048),
                    ]),
            ]);
    }

    /**
     * One step, boxed and numbered on its own so nine fields in a row no
     * longer read as one block of text.
     *
     * Required steps are always here. An optional one is only in the schema
     * while its key is in the hidden `active_optional_steps` list — see the
     * Hidden field above — so removing it is really just hiding it: a Filament
     * component that is not visible is not dehydrated, so saving never
     * overwrites or clears whatever that step already holds. It comes back
     * exactly as it was through "Add step".
     *
     * @param  string  $name  The model column this step writes to.
     * @param  string  $section  Its key under academy.case_studies.sections.
     */
    protected static function numberedStep(string $name, string $section, int $number, bool $withGallery, bool $optional = false): Section
    {
        $section_ = Section::make(__t('admin_case_studies.form.step_label', [
            'number' => $number,
            'name' => __t("academy.case_studies.sections.{$section}"),
        ]))
            ->schema($withGallery ? static::step($name, $section) : [static::rich($name)])
            ->columnSpanFull()
            // The required steps carry their own asterisk from ->required() on
            // the field; a box around each is separation enough without a
            // second "required" label repeating it.
            ->compact();

        if (! $optional) {
            return $section_;
        }

        return $section_
            ->visible(fn (Get $get): bool => in_array($section, (array) $get('active_optional_steps'), true))
            ->headerActions([
                Action::make("remove_{$section}")
                    ->label(__t('admin_case_studies.form.remove_step'))
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->link()
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_case_studies.form.remove_step_confirm_heading'))
                    ->modalDescription(__t('admin_case_studies.form.remove_step_confirm_description'))
                    ->modalSubmitActionLabel(__t('admin_case_studies.form.remove_step'))
                    ->action(function (Get $get, Set $set) use ($section): void {
                        $set('active_optional_steps', array_values(Arr::where(
                            (array) $get('active_optional_steps'),
                            fn (string $key): bool => $key !== $section,
                        )));
                    }),
            ]);
    }

    /**
     * One step: the words, then its pictures.
     *
     * Two ways in, because they suit different pictures. Something that belongs
     * mid-sentence goes in the text through the paperclip; a screenshot that
     * stands on its own goes in the box below, which also takes a paste from
     * the clipboard or a file dropped on it. Kept to the two steps partners
     * actually screenshot (see numberedStep()) — every step still has the
     * paperclip in its own text toolbar regardless.
     *
     * @param  string  $section  A key under academy.case_studies.sections.
     * @return array<int, Component>
     */
    protected static function step(string $name, string $section): array
    {
        return [
            static::rich($name),
            FileUpload::make("section_images.{$section}")
                ->label(__t('admin_case_studies.form.step_images', ['step' => __t("academy.case_studies.sections.{$section}")]))
                ->helperText(__t('admin_case_studies.form.step_images_help'))
                ->image()
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->openable()
                ->disk('public')
                ->directory('case-study-images')
                ->visibility('public')
                ->maxSize(8192)
                ->columnSpanFull(),
        ];
    }

    /**
     * One step of the study, with pictures.
     *
     * Every step takes images the same way: the paperclip in the toolbar, a
     * file dragged onto the box, or one pasted straight in. They land on the
     * public disk, because a partner has to be able to see them — the app's
     * default disk is private, so an attachment saved there would upload
     * happily and then show a broken image on the study page.
     *
     * No label of its own: the Section wrapping it (see numberedStep()) is
     * already headed with the step's name and number.
     */
    protected static function rich(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->hiddenLabel()
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('case-study-images')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsMaxSize(8192)
            ->helperText(__t('admin_case_studies.form.images_help'))
            ->required(in_array($name, ['scenario_problem', 'desired_outcome', 'configuration_steps', 'testing_verification'], true))
            ->columnSpanFull();
    }

    protected static function mediaSelect(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->relationship(Str::camel(str_replace('_id', '', $name)), 'name')
            ->searchable()
            ->preload()
            ->createOptionForm([
                TextInput::make('name')
                    ->label(__t('admin_common.name'))
                    ->required()
                    ->maxLength(255),
                FileUpload::make('path')
                    ->label(__t('admin_library.media.image'))
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios(['16:9', '4:3', '1:1', null])
                    ->disk('public')
                    ->directory('media-library')
                    ->visibility('public')
                    ->maxSize(8192)
                    ->required(),
            ]);
    }
}
