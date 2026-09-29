<?php

namespace App\Filament\Resources\CaseStudies\Schemas;

use App\Models\CaseStudy;
use App\Models\Lesson;
use App\Models\Product;
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
                Section::make('Case study')
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
                                        $fail('Choose one of your assigned products.');
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
                            ->label('Short problem statement')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('industry')
                            ->label('Industry')
                            ->maxLength(120)
                            ->datalist(['Delivery', 'Field service', 'Fuel logistics', 'Passenger transport', 'Construction']),

                        TagsInput::make('features_used')
                            ->label('Relevant Pilot features')
                            ->placeholder('Add a feature')
                            ->suggestions(['Geofences', 'Notifications', 'Reports', 'History', 'Speed control', 'Fuel sensors', 'Sensors', 'Object groups'])
                            ->columnSpanFull(),

                        Select::make('difficulty')
                            ->label('Difficulty')
                            ->options(CaseStudy::difficultyLabels())
                            ->default(CaseStudy::DIFFICULTY_INTERMEDIATE)
                            ->required(),

                        TextInput::make('implementation_time')
                            ->label('Estimated implementation time')
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
                                        $fail('Add the required sections and source note before publishing.');
                                    }
                                },
                            ]),
                    ]),

                Section::make('Privacy and verification')
                    ->description('Keep identifying customer details out by default. Store verification notes for any performance claim.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_anonymized')
                            ->label('Anonymized')
                            ->default(true),

                        Toggle::make('is_customer_approved')
                            ->label('Customer approved'),

                        Textarea::make('source_note')
                            ->label('Source or verification note')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('performance_claim_note')
                            ->label('Performance claim note')
                            ->rows(3)
                            ->helperText('Required when the study mentions measured savings, reductions, uptime, or other quantified outcomes.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->description('Use sanitized diagrams, screenshots, or attachments. Avoid customer names, locations, live data, credentials, and identifying images.')
                    ->columns(2)
                    ->schema([
                        static::mediaSelect('cover_media_item_id', 'Cover image'),
                        static::mediaSelect('diagram_media_item_id', 'Diagram or screenshot'),
                    ]),

                Section::make('Study sections')
                    ->schema([
                        static::rich('scenario_problem', 'Customer scenario and problem', true),
                        static::rich('desired_outcome', 'Desired outcome', true),
                        static::rich('prerequisites', 'Required devices, data, and prerequisites'),
                        static::rich('pilot_features', 'Pilot features used'),
                        static::rich('configuration_steps', 'Step-by-step configuration', true),
                        static::rich('testing_verification', 'How to test and verify the setup', true),
                        static::rich('expected_results', 'Expected results and limitations'),
                        static::rich('troubleshooting', 'Troubleshooting and common mistakes'),
                        static::rich('adaptation', 'Ways partners can adapt the solution'),
                    ]),

                Section::make('Related content')
                    ->columns(2)
                    ->schema([
                        Select::make('related_lesson_ids')
                            ->label('Related Academy lessons')
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
                            ->label('Documentation links')
                            ->defaultItems(0)
                            ->addActionLabel('Add link')
                            ->columns(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->maxLength(2048),
                            ]),
                    ]),
            ]);
    }

    protected static function rich(string $name, string $label, bool $required = false): RichEditor
    {
        $field = RichEditor::make($name)
            ->label($label)
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
