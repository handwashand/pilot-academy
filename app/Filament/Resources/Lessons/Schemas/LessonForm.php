<?php

namespace App\Filament\Resources\Lessons\Schemas;

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

                        TextInput::make('youtube_url')
                            ->label('YouTube link')
                            ->url()
                            ->columnSpanFull()
                            ->helperText('Paste the full YouTube URL, e.g. https://www.youtube.com/watch?v=XXXXXXXXXXX'),

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
                        Repeater::make('questions')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->itemLabel(fn (array $state): ?string => $state['prompt'] ?? null)
                            ->collapsible()
                            ->defaultItems(0)
                            ->addActionLabel('Add question')
                            ->schema([
                                Textarea::make('prompt')
                                    ->label('Question')
                                    ->required()
                                    ->rows(2),

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
            ]);
    }
}
