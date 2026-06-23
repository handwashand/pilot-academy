<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
                        Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('sort_order')
                            ->label('Order in course')
                            ->numeric()
                            ->default(0),

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
                            ->helperText('Unique within the course.'),

                        Textarea::make('summary')
                            ->label('Short summary')
                            ->rows(2)
                            ->columnSpanFull(),

                        FileUpload::make('image_path')
                            ->label('Cover image')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['16:9', '4:3', '1:1', null])
                            ->disk('public')
                            ->directory('lesson-images')
                            ->visibility('public')
                            ->maxSize(8192) // 8 MB
                            ->columnSpanFull()
                            ->helperText('Shown on the lesson card. Use the editor to crop/fit. Leave empty for a placeholder.'),

                        TextInput::make('youtube_url')
                            ->label('YouTube link')
                            ->url()
                            ->columnSpanFull()
                            ->helperText('Paste the full YouTube URL, e.g. https://www.youtube.com/watch?v=XXXXXXXXXXX'),

                        FileUpload::make('video_path')
                            ->label('Or upload a video file')
                            ->disk('public')
                            ->directory('lesson-videos')
                            ->visibility('public')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                            ->maxSize(204800) // 200 MB (server upload limits raised to match)
                            ->columnSpanFull()
                            ->helperText('MP4 up to 200 MB. If a file is uploaded it is used instead of the YouTube link.'),

                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(0),

                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(true),

                        RichEditor::make('content')
                            ->label('Lesson text')
                            ->columnSpanFull(),
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
