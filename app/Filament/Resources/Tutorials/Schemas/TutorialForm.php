<?php

namespace App\Filament\Resources\Tutorials\Schemas;

use App\Filament\Resources\Courses\Schemas\CourseForm;
use App\Models\Lesson;
use App\Models\Tutorial;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TutorialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__t('admin_tutorials.sections.main'))
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
                            ->label(__t('admin_tutorials.form.summary'))
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('duration_minutes')
                            ->label(__t('admin_common.duration_minutes'))
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('sort_order')
                            ->label(__t('admin_courses.form.sort_order'))
                            ->numeric()
                            ->default(0),

                        Select::make('status')
                            ->label(__t('admin_common.status'))
                            ->options(Tutorial::statusLabels())
                            ->default(Tutorial::STATUS_DRAFT)
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->required()
                            ->rules([
                                fn (?Tutorial $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                                    if ($value === Tutorial::STATUS_PUBLISHED && $record && ! $record->canBePublished()) {
                                        $fail(__t('admin_tutorials.form.before_publishing'));
                                    }
                                },
                            ]),
                    ]),

                // The same two sources a lesson video takes, so an admin who has
                // added one of those already knows this box.
                Section::make(__t('admin_tutorials.sections.video'))
                    ->description(__t('admin_tutorials.sections.video_hint'))
                    ->schema([
                        Select::make('type')
                            ->label(__t('admin_lessons.form.source'))
                            ->options([
                                Tutorial::TYPE_YOUTUBE => 'YouTube',
                                Tutorial::TYPE_UPLOAD => __t('admin_lessons.form.upload'),
                            ])
                            ->default(Tutorial::TYPE_YOUTUBE)
                            ->required()
                            // Swap the link box and the file box as soon as the
                            // source changes.
                            ->live(),

                        TextInput::make('youtube_url')
                            ->label(__t('admin_lessons.form.youtube_link'))
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn ($get): bool => ($get('type') ?? Tutorial::TYPE_YOUTUBE) === Tutorial::TYPE_YOUTUBE)
                            ->required(fn ($get): bool => ($get('type') ?? Tutorial::TYPE_YOUTUBE) === Tutorial::TYPE_YOUTUBE)
                            ->helperText(__t('admin_lessons.form.youtube_help'))
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                    if (filled($value) && Lesson::youtubeIdFrom($value) === null) {
                                        $fail(__t('admin_lessons.form.youtube_invalid'));
                                    }
                                },
                            ]),

                        FileUpload::make('video_path')
                            ->label(__t('admin_lessons.form.video_file'))
                            ->disk('public')
                            ->directory('tutorial-videos')
                            ->visibility('public')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                            ->maxSize(204800) // 200 MB, as for lesson videos.
                            ->visible(fn ($get): bool => ($get('type') ?? Tutorial::TYPE_YOUTUBE) === Tutorial::TYPE_UPLOAD)
                            ->required(fn ($get): bool => ($get('type') ?? Tutorial::TYPE_YOUTUBE) === Tutorial::TYPE_UPLOAD)
                            ->helperText(__t('admin_lessons.form.video_file_help')),
                    ]),
            ]);
    }
}
