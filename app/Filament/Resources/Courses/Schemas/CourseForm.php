<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Course;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Course title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('URL slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in the page address, e.g. /courses/pilot-quick-start'),

                Textarea::make('description')
                    ->label('Short description')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('level')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                    ])
                    ->default('beginner')
                    ->required(),

                Select::make('audience')
                    ->label('Who it\'s for')
                    ->options(Course::AUDIENCES)
                    ->placeholder('Not specified')
                    ->helperText('Shown as a badge on the course card.'),

                TextInput::make('duration_minutes')
                    ->label('Duration (minutes)')
                    ->numeric()
                    ->minValue(0),

                TextInput::make('sort_order')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first.'),

                Toggle::make('is_published')
                    ->label('Published')
                    ->default(true),

                Section::make('Final quiz & certificate')
                    ->description('A course-wide quiz students take after finishing every lesson. Passing issues a certificate. Manage the question bank in the "Final questions" tab after saving.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('final_quiz_enabled')
                            ->label('Enable final quiz')
                            ->live()
                            ->columnSpanFull()
                            ->helperText('When on, students who complete all lessons can take the final quiz.'),

                        TextInput::make('pass_percent')
                            ->label('Pass mark (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(80)
                            ->required()
                            ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),

                        TextInput::make('questions_per_attempt')
                            ->label('Questions per attempt')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('All')
                            ->helperText('Randomly drawn from the bank each attempt. Leave empty to use every question.')
                            ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),

                        TextInput::make('final_quiz_max_attempts')
                            ->label('Max attempts')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Unlimited')
                            ->helperText('Leave empty for unlimited attempts.')
                            ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),

                        FileUpload::make('certificate_template')
                            ->label('Certificate background (optional)')
                            ->image()
                            ->disk('public')
                            ->directory('certificate-templates')
                            ->visibility('public')
                            ->maxSize(8192)
                            ->columnSpanFull()
                            ->helperText('Full-page A4 landscape background (≈3508×2480 px). Leave empty to use the built-in framed layout. Name, course, date, number and QR are overlaid automatically.')
                            ->visible(fn ($get) => (bool) $get('final_quiz_enabled')),
                    ]),
            ]);
    }
}
