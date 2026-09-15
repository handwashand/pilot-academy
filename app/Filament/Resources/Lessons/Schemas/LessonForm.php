<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Filament\Resources\Courses\Schemas\CourseForm;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\Translator;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__t('admin_common.lesson'))
                    ->columns(2)
                    ->schema([
                        // Saved by CreateLesson / EditLesson: the first course is
                        // the lesson's home, and the list is synced to course_lesson.
                        Select::make('course_ids')
                            ->label(__t('admin_common.courses'))
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function (?Lesson $record): array {
                                $user = auth()->user();

                                return Course::query()
                                    // A creator may only add lessons to their own
                                    // courses. Courses it is already in stay listed,
                                    // so saving never drops one they cannot see.
                                    ->when($user?->isCreator(), fn (Builder $query) => $query->where(fn (Builder $courses) => $courses
                                        ->whereIn('product_id', $user->products()->pluck('products.id'))
                                        ->orWhereIn('id', $record?->courses()->pluck('courses.id') ?? [])))
                                    ->orderBy('title')
                                    ->pluck('title', 'id')
                                    ->all();
                            })
                            ->afterStateHydrated(function (Select $component, ?Lesson $record): void {
                                if ($record) {
                                    // The home course first: it owns the lesson.
                                    $component->state($record->courses()->pluck('courses.id')
                                        ->map(fn ($id): int => (int) $id)
                                        ->sortBy(fn (int $id): int => $id === (int) $record->course_id ? 0 : 1)
                                        ->values()
                                        ->all());
                                }
                            })
                            // A new lesson is written in its first course's language.
                            ->live()
                            ->afterStateUpdated(function (string $operation, $state, callable $set): void {
                                $first = collect($state)->first();

                                if ($operation === 'create' && $first) {
                                    $set('language', Course::whereKey($first)->value('language') ?: app(Translator::class)->defaultCode());
                                }
                            })
                            ->helperText(__t('admin_lessons.form.courses_help'))
                            ->rules([
                                fn (?Lesson $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                                    $user = auth()->user();

                                    if (! $user?->isCreator()) {
                                        return;
                                    }

                                    $current = $record?->courses()->pluck('courses.id')->map(fn ($id): int => (int) $id)->all() ?? [];

                                    foreach ((array) $value as $courseId) {
                                        if (! in_array((int) $courseId, $current, true) && ! $user->canManageCourse(Course::find($courseId))) {
                                            $fail(__t('admin_lessons.form.course_not_yours'));

                                            return;
                                        }
                                    }
                                },
                            ]),

                        CourseForm::writtenIn(),

                        TextInput::make('sort_order')
                            ->label(__t('admin_lessons.form.order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__t('admin_lessons.form.order_help')),

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
                            ->helperText(__t('admin_lessons.form.slug_help'))
                            // The student URL finds a lesson by this inside its
                            // course; a second one with the same slug is unreachable.
                            ->rules([
                                fn (Get $get, ?Lesson $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record): void {
                                    $taken = Lesson::query()
                                        ->where('slug', $value)
                                        ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                        ->whereHas('courses', fn (Builder $courses) => $courses->whereIn('courses.id', (array) $get('course_ids')))
                                        ->exists();

                                    if ($taken) {
                                        $fail(__t('admin_lessons.form.slug_taken'));
                                    }
                                },
                            ]),

                        Textarea::make('summary')
                            ->label(__t('admin_lessons.form.summary'))
                            ->rows(2)
                            ->columnSpanFull(),

                        Select::make('media_item_id')
                            ->label(__t('admin_lessons.form.cover'))
                            ->relationship('mediaItem', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->helperText(__t('admin_lessons.form.cover_help'))
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(__t('admin_lessons.form.image_name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText(__t('admin_lessons.form.image_name_help')),
                                FileUpload::make('path')
                                    ->label(__t('admin_lessons.form.image'))
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios(['16:9', '4:3', '1:1', null])
                                    ->disk('public')
                                    ->directory('media-library')
                                    ->visibility('public')
                                    ->maxSize(8192)
                                    ->required(),
                            ]),

                        Repeater::make('video_sources')
                            ->label(__t('admin_lessons.form.videos'))
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(5)
                            ->addActionLabel(__t('admin_lessons.form.add_video'))
                            ->itemLabel(fn (array $state): ?string => (($state['type'] ?? 'youtube') === 'upload' ? __t('admin_lessons.form.uploaded_video') : __t('admin_lessons.form.youtube_video')))
                            ->collapsible()
                            ->collapsed()
                            ->columns(1)
                            ->helperText(__t('admin_lessons.form.videos_help'))
                            // A lesson saved before the Videos list: show the video
                            // students see now, so the next save carries it over.
                            ->afterStateHydrated(function (callable $set, $state, $record): void {
                                if (empty($state) && $record && $record->videoEntries() !== []) {
                                    $set('video_sources', $record->videoEntries());
                                }
                            })
                            ->schema([
                                Select::make('type')
                                    ->label(__t('admin_lessons.form.source'))
                                    ->options([
                                        'youtube' => 'YouTube',
                                        'upload' => __t('admin_lessons.form.upload'),
                                    ])
                                    ->default('youtube')
                                    ->required()
                                    // Swap the link box and the file box as soon
                                    // as the source changes.
                                    ->live(),

                                TextInput::make('youtube_url')
                                    ->label(__t('admin_lessons.form.youtube_link'))
                                    ->url()
                                    ->visible(fn ($get): bool => ($get('type') ?? 'youtube') === 'youtube')
                                    ->required(fn ($get): bool => ($get('type') ?? 'youtube') === 'youtube')
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
                                    ->directory('lesson-videos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                                    ->maxSize(204800) // 200 MB (server upload limits raised to match)
                                    ->visible(fn ($get): bool => ($get('type') ?? 'youtube') === 'upload')
                                    ->required(fn ($get): bool => ($get('type') ?? 'youtube') === 'upload')
                                    ->helperText(__t('admin_lessons.form.video_file_help')),
                            ]),

                        TextInput::make('duration_minutes')
                            ->label(__t('admin_common.duration_minutes'))
                            ->numeric()
                            ->minValue(0),

                        Select::make('status')
                            ->label(__t('admin_common.status'))
                            ->options(Lesson::statusLabels())
                            ->default(Lesson::STATUS_PUBLISHED)
                            ->required()
                            ->helperText(__t('admin_lessons.form.status_help')),

                        RichEditor::make('content')
                            ->label(__t('admin_lessons.form.content'))
                            ->columnSpanFull(),

                        Textarea::make('transcript')
                            ->label(__t('admin_lessons.form.transcript'))
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText(__t('admin_lessons.form.transcript_help')),
                    ]),

                Section::make(__t('admin_lessons.form.docs_section'))
                    ->description(__t('admin_lessons.form.docs_help'))
                    ->collapsible()
                    ->schema([
                        Repeater::make('doc_links')
                            // The section heading names it on screen. The label is
                            // still read out by screen readers, so it is translated
                            // too: left empty, Filament used the field name, "Doc links".
                            ->label(__t('admin_lessons.form.docs_section'))
                            ->hiddenLabel()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? __t('admin_lessons.form.new_link'))
                            ->defaultItems(0)
                            ->addActionLabel(__t('admin_lessons.form.add_link'))
                            ->reorderable()
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__t('admin_lessons.form.link_title'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('url')
                                    ->label(__t('admin_lessons.form.url'))
                                    ->url()
                                    ->required()
                                    ->maxLength(2048),
                            ]),
                    ]),

                Section::make(__t('admin_lessons.form.quiz_section'))
                    ->description(__t('admin_lessons.form.quiz_help'))
                    ->schema([
                        TextInput::make('quiz_time_limit_minutes')
                            ->label(__t('admin_lessons.form.time_limit'))
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__t('admin_lessons.form.time_limit_help')),

                        TextInput::make('quiz_max_attempts')
                            ->label(__t('admin_common.max_attempts'))
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__t('admin_lessons.form.max_attempts_help')),

                        Repeater::make('questions')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->itemLabel(fn (array $state): ?string => $state['prompt'] ?? __t('admin_lessons.form.new_question'))
                            ->collapsible()
                            ->collapsed()
                            ->defaultItems(0)
                            ->addActionLabel(__t('admin_lessons.form.add_question'))
                            ->schema([
                                Textarea::make('prompt')
                                    ->label(__t('admin_common.question'))
                                    ->required()
                                    ->rows(2),

                                Section::make(__t('admin_common.answer_options'))
                                    ->description(__t('admin_lessons.form.tick_correct'))
                                    ->compact()
                                    ->schema([
                                        Repeater::make('options')
                                            ->relationship()
                                            ->orderColumn('sort_order')
                                            ->defaultItems(2)
                                            ->minItems(2)
                                            ->addActionLabel(__t('admin_common.add_answer_option'))
                                            ->columns(4)
                                            // Grading needs at least one right
                                            // answer: a question without one can
                                            // never be passed, and the student is
                                            // simply stuck on that lesson.
                                            ->rules([
                                                fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                                    $correct = collect($value)->filter(fn ($option): bool => (bool) ($option['is_correct'] ?? false));

                                                    if ($correct->isEmpty()) {
                                                        $fail(__t('admin_lessons.form.no_correct'));
                                                    }
                                                },
                                            ])
                                            ->schema([
                                                TextInput::make('text')
                                                    ->label(__t('admin_common.answer'))
                                                    ->required()
                                                    ->columnSpan(3),

                                                Toggle::make('is_correct')
                                                    ->label(__t('admin_common.correct'))
                                                    ->inline(false)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
