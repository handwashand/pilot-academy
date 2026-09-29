<?php

namespace App\Filament\Resources\CaseStudies\Schemas;

use App\Models\CaseStudy;
use App\Models\Lesson;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CaseStudyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__t('admin_case_studies.sections.main'))
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
                            ->suggestions(['GeoZones', 'Notifications', 'Reports', 'History', 'Speed control', 'Fuel sensors', 'Sensors', 'Object groups'])
                            ->columnSpanFull(),

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

                Section::make(__t('admin_case_studies.sections.privacy'))
                    ->description(__t('admin_case_studies.sections.privacy_hint'))
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

                Section::make(__t('admin_case_studies.sections.media'))
                    ->description(__t('admin_case_studies.sections.media_hint'))
                    ->columns(2)
                    ->schema([
                        static::mediaSelect('cover_media_item_id', __t('admin_case_studies.form.cover')),
                        static::mediaSelect('diagram_media_item_id', __t('admin_case_studies.form.diagram')),
                    ]),

                // The editor writes under the same headings a partner reads —
                // see CaseStudy::sections() and academy.case_studies.sections.
                Section::make(__t('admin_case_studies.sections.study'))
                    ->schema([
                        static::rich('scenario_problem', 'scenario', true),
                        static::rich('desired_outcome', 'outcome', true),
                        static::rich('prerequisites', 'prerequisites'),
                        static::rich('pilot_features', 'features'),
                        static::rich('configuration_steps', 'configuration', true),
                        static::rich('testing_verification', 'verification', true),
                        static::rich('expected_results', 'results'),
                        static::rich('troubleshooting', 'troubleshooting'),
                        static::rich('adaptation', 'adaptation'),
                    ]),

                Section::make(__t('admin_case_studies.sections.related'))
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
                    ]),
            ]);
    }

    /** @param  string  $section  A key under academy.case_studies.sections. */
    protected static function rich(string $name, string $section, bool $required = false): RichEditor
    {
        $field = RichEditor::make($name)
            ->label(__t("academy.case_studies.sections.{$section}"))
            ->columnSpanFull();

        return $required ? $field->required() : $field;
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
