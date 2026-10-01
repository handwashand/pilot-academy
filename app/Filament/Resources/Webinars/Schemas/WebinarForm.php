<?php

namespace App\Filament\Resources\Webinars\Schemas;

use App\Filament\Resources\Courses\Schemas\CourseForm;
use App\Models\Webinar;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WebinarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__t('admin_webinars.sections.main'))
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
                            ->default(fn () => auth()->user()?->isCreator() ? auth()->user()->products()->value('products.id') : null),

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

                        CourseForm::writtenIn(),

                        TextInput::make('slug')
                            ->label(__t('admin_common.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('summary')
                            ->label(__t('admin_webinars.form.summary'))
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('presenter')
                            ->label(__t('admin_webinars.form.presenter'))
                            ->maxLength(255),

                        TextInput::make('duration_minutes')
                            ->label(__t('admin_common.duration_minutes'))
                            ->numeric()
                            ->minValue(0),

                        Select::make('status')
                            ->label(__t('admin_common.status'))
                            ->options(Webinar::statusLabels())
                            ->default(Webinar::STATUS_DRAFT)
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->required()
                            ->rules([
                                fn (?Webinar $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                                    if ($value === Webinar::STATUS_PUBLISHED && $record && ! $record->canBePublished()) {
                                        $fail(__t('admin_webinars.form.before_publishing'));
                                    }
                                },
                            ]),
                    ]),

                Section::make(__t('admin_webinars.sections.when'))
                    ->description(__t('admin_webinars.sections.when_hint'))
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__t('admin_webinars.form.starts_at'))
                            ->seconds(false)
                            ->helperText(__t('admin_webinars.form.starts_at_hint')),

                        TextInput::make('join_url')
                            ->label(__t('admin_webinars.form.join_url'))
                            ->url()
                            ->maxLength(2048),

                        TextInput::make('recording_url')
                            ->label(__t('admin_webinars.form.recording_url'))
                            ->url()
                            ->maxLength(2048)
                            ->helperText(__t('admin_webinars.form.recording_url_hint'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__t('admin_webinars.sections.about'))
                    ->schema([
                        RichEditor::make('description')
                            ->label(__t('admin_webinars.form.description'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
