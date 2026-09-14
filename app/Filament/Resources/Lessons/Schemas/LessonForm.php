<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\Course;
use App\Models\Lesson;
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
                Section::make('Lesson')
                    ->columns(2)
                    ->schema([
                        // Saved by CreateLesson / EditLesson: the first course is
                        // the lesson's home, and the list is synced to course_lesson.
                        Select::make('course_ids')
                            ->label('Courses')
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
                            ->helperText('A lesson can be in more than one course. It is the same lesson in each: a change shows everywhere, and a student who finishes it in one course has it finished in all of them. The first course owns the lesson.')
                            ->rules([
                                fn (?Lesson $record): Closure => function (string $attribute, $value, Closure $fail) use ($record): void {
                                    $user = auth()->user();

                                    if (! $user?->isCreator()) {
                                        return;
                                    }

                                    $current = $record?->courses()->pluck('courses.id')->map(fn ($id): int => (int) $id)->all() ?? [];

                                    foreach ((array) $value as $courseId) {
                                        if (! in_array((int) $courseId, $current, true) && ! $user->canManageCourse(Course::find($courseId))) {
                                            $fail('You can only add lessons to a course for a product assigned to you.');

                                            return;
                                        }
                                    }
                                },
                            ]),

                        TextInput::make('sort_order')
                            ->label('Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Where it goes in its first course. Drag lessons on a course’s Lessons tab to order each course.'),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Part of the lesson’s web address. No two lessons in the same course may share it.')
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
                                        $fail('Another lesson in one of these courses already uses this address. Choose a different one.');
                                    }
                                },
                            ]),

                        Textarea::make('summary')
                            ->label('Short summary')
                            ->rows(2)
                            ->columnSpanFull(),

                        Select::make('media_item_id')
                            ->label('Cover image')
                            ->relationship('mediaItem', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->helperText('Pick an image from the library, or add a new one — uploaded images are reusable across lessons. Leave empty for a placeholder.')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('A label to find this image later.'),
                                FileUpload::make('path')
                                    ->label('Image')
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
                            ->label('Videos')
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(5)
                            ->addActionLabel('Add video')
                            ->itemLabel(fn (array $state): ?string => (($state['type'] ?? 'youtube') === 'upload' ? 'Uploaded video' : 'YouTube video'))
                            ->collapsible()
                            ->collapsed()
                            ->columns(1)
                            ->helperText('Add up to five videos. Each one can be a YouTube link or an uploaded file.')
                            // A lesson saved before the Videos list: show the video
                            // students see now, so the next save carries it over.
                            ->afterStateHydrated(function (callable $set, $state, $record): void {
                                if (empty($state) && $record && $record->videoEntries() !== []) {
                                    $set('video_sources', $record->videoEntries());
                                }
                            })
                            ->schema([
                                Select::make('type')
                                    ->label('Source')
                                    ->options([
                                        'youtube' => 'YouTube',
                                        'upload' => 'Upload',
                                    ])
                                    ->default('youtube')
                                    ->required()
                                    // Swap the link box and the file box as soon
                                    // as the source changes.
                                    ->live(),

                                TextInput::make('youtube_url')
                                    ->label('YouTube link')
                                    ->url()
                                    ->visible(fn ($get): bool => ($get('type') ?? 'youtube') === 'youtube')
                                    ->required(fn ($get): bool => ($get('type') ?? 'youtube') === 'youtube')
                                    ->helperText('One video only, e.g. https://www.youtube.com/watch?v=XXXXXXXXXXX.')
                                    ->rules([
                                        fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                            if (filled($value) && Lesson::youtubeIdFrom($value) === null) {
                                                $fail('This is not a link to a single YouTube video, so students would see no video. Open the video itself on YouTube and copy the address from the address bar — playlist, channel and Vimeo links cannot be played here.');
                                            }
                                        },
                                    ]),

                                FileUpload::make('video_path')
                                    ->label('Uploaded video file')
                                    ->disk('public')
                                    ->directory('lesson-videos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                                    ->maxSize(204800) // 200 MB (server upload limits raised to match)
                                    ->visible(fn ($get): bool => ($get('type') ?? 'youtube') === 'upload')
                                    ->required(fn ($get): bool => ($get('type') ?? 'youtube') === 'upload')
                                    ->helperText('MP4 up to 200 MB.'),
                            ]),

                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(0),

                        Select::make('status')
                            ->label('Status')
                            ->options(Lesson::STATUS_LABELS)
                            ->default(Lesson::STATUS_PUBLISHED)
                            ->required()
                            ->helperText('Students see a lesson only once its course is published too. Set it to draft to keep this one lesson back.'),

                        RichEditor::make('content')
                            ->label('Lesson text')
                            ->columnSpanFull(),

                        Textarea::make('transcript')
                            ->label('Video transcript')
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText('What is said in the video, as plain text. Students can read it instead of watching, and it is the only way the words inside a video turn up in search. Paste the captions from YouTube if you have them.'),
                    ]),

                Section::make('Documentation links')
                    ->description('Shown to students after the lesson text. Link titles are in English.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('doc_links')
                            ->label('')
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New link')
                            ->defaultItems(0)
                            ->addActionLabel('Add link')
                            ->reorderable()
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title (English)')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->maxLength(2048),
                            ]),
                    ]),

                Section::make('Knowledge check (quiz)')
                    ->description('Add questions. Mark the correct option(s) with the toggle.')
                    ->schema([
                        TextInput::make('quiz_time_limit_minutes')
                            ->label('Time limit (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Optional. A countdown is shown; running out ends the attempt unsuccessfully. Applies to logged-in students.'),

                        TextInput::make('quiz_max_attempts')
                            ->label('Max attempts')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Optional. How many tries a student gets. Leave empty for unlimited.'),

                        Repeater::make('questions')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->itemLabel(fn (array $state): ?string => $state['prompt'] ?? 'New question')
                            ->collapsible()
                            ->collapsed()
                            ->defaultItems(0)
                            ->addActionLabel('Add question')
                            ->schema([
                                Textarea::make('prompt')
                                    ->label('Question')
                                    ->required()
                                    ->rows(2),

                                Section::make('Answer options')
                                    ->description('Tick the correct answer(s).')
                                    ->compact()
                                    ->schema([
                                        Repeater::make('options')
                                            ->relationship()
                                            ->orderColumn('sort_order')
                                            ->defaultItems(2)
                                            ->minItems(2)
                                            ->addActionLabel('Add answer option')
                                            ->columns(4)
                                            // Grading needs at least one right
                                            // answer: a question without one can
                                            // never be passed, and the student is
                                            // simply stuck on that lesson.
                                            ->rules([
                                                fn (): Closure => function (string $attribute, $value, Closure $fail): void {
                                                    $correct = collect($value)->filter(fn ($option): bool => (bool) ($option['is_correct'] ?? false));

                                                    if ($correct->isEmpty()) {
                                                        $fail('Tick the correct answer — a question with none can never be passed.');
                                                    }
                                                },
                                            ])
                                            ->schema([
                                                TextInput::make('text')
                                                    ->label('Answer')
                                                    ->required()
                                                    ->columnSpan(3),

                                                Toggle::make('is_correct')
                                                    ->label('Correct')
                                                    ->inline(false)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
