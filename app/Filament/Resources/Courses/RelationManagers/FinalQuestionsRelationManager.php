<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Models\Question;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FinalQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'finalQuestions';

    protected static ?string $title = 'Final questions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('prompt')
                ->label('Question')
                ->required()
                ->rows(2)
                ->columnSpanFull(),

            Select::make('type')
                ->label('Answer type')
                ->options(Question::TYPE_LABELS)
                ->default(Question::TYPE_SINGLE)
                ->required(),

            Repeater::make('options')
                ->relationship()
                ->orderColumn('sort_order')
                ->label('Answer options')
                ->helperText('Tick the correct answer(s). Multiple ticks require an "answer type" of Multiple select.')
                ->defaultItems(2)
                ->minItems(2)
                ->addActionLabel('Add answer option')
                ->columns(4)
                ->columnSpanFull()
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
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('prompt')
            ->columns([
                TextColumn::make('prompt')
                    ->label('Question')
                    ->wrap()
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Question::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => $state === Question::TYPE_MULTIPLE ? 'warning' : 'gray'),

                TextColumn::make('lesson.title')
                    ->label('Source')
                    ->badge()
                    ->color(fn ($record): string => $record->lesson_id ? 'info' : 'success')
                    ->default('Course-only'),

                TextColumn::make('options_count')
                    ->label('Options')
                    ->counts('options')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(Question::TYPE_LABELS),
            ])
            ->headerActions([
                Action::make('addAllLessonQuestions')
                    ->label('Add all lesson questions')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Adds every question from this course\'s lessons to the final quiz bank. Already-added questions are left as they are.')
                    ->action(function () {
                        $course = $this->getOwnerRecord();
                        $ids = Question::whereHas('lesson', fn (Builder $q) => $q->where('course_id', $course->id))
                            ->pluck('id')
                            ->all();
                        $course->finalQuestions()->syncWithoutDetaching($ids);
                    }),

                AttachAction::make()
                    ->label('Attach lesson question')
                    ->recordSelectSearchColumns(['prompt'])
                    ->recordTitle(fn (Question $record): string => Str::limit($record->prompt, 70))
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereHas(
                        'lesson',
                        fn (Builder $q) => $q->where('course_id', $this->getOwnerRecord()->id)
                    )),

                CreateAction::make()
                    ->label('New final question')
                    ->modalHeading('New course-only question'),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make()
                    ->label('Remove from bank'),
                DeleteAction::make()
                    ->label('Delete question')
                    ->visible(fn (Question $record): bool => $record->lesson_id === null),
            ]);
    }
}
