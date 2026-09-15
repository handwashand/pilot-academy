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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinalQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'finalQuestions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __t('admin_nav.tabs.final_questions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('prompt')
                ->label(__t('admin_common.question'))
                ->required()
                ->rows(2)
                ->columnSpanFull(),

            Select::make('type')
                ->label(__t('admin_courses.final_tab.answer_type'))
                ->options(Question::typeLabels())
                ->default(Question::TYPE_SINGLE)
                ->required(),

            Repeater::make('options')
                ->relationship()
                ->orderColumn('sort_order')
                ->label(__t('admin_common.answer_options'))
                ->helperText(__t('admin_courses.final_tab.options_help'))
                ->defaultItems(2)
                ->minItems(2)
                ->addActionLabel(__t('admin_common.add_answer_option'))
                ->columns(4)
                ->columnSpanFull()
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
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('prompt')
            ->columns([
                TextColumn::make('prompt')
                    ->label(__t('admin_common.question'))
                    ->wrap()
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('type')
                    ->label(__t('admin_common.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Question::typeLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => $state === Question::TYPE_MULTIPLE ? 'warning' : 'gray'),

                TextColumn::make('lesson.title')
                    ->label(__t('admin_courses.final_tab.source'))
                    ->badge()
                    ->color(fn ($record): string => $record->lesson_id ? 'info' : 'success')
                    ->default(__t('admin_courses.final_tab.course_only')),

                TextColumn::make('options_count')
                    ->label(__t('admin_courses.final_tab.options'))
                    ->counts('options')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__t('admin_common.type'))
                    ->options(Question::typeLabels()),
            ])
            ->headerActions([
                Action::make('addAllLessonQuestions')
                    ->label(__t('admin_courses.final_tab.add_all'))
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(__t('admin_courses.final_tab.add_all_description'))
                    ->action(function () {
                        $course = $this->getOwnerRecord();
                        $ids = Question::whereHas('lesson.courses', fn (Builder $q) => $q->whereKey($course->id))
                            ->pluck('id')
                            ->all();
                        $course->finalQuestions()->syncWithoutDetaching($ids);
                    }),

                AttachAction::make()
                    ->label(__t('admin_courses.final_tab.attach'))
                    ->recordSelectSearchColumns(['prompt'])
                    ->recordTitle(fn (Question $record): string => Str::limit($record->prompt, 70))
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereHas(
                        'lesson.courses',
                        fn (Builder $q) => $q->whereKey($this->getOwnerRecord()->id)
                    )),

                CreateAction::make()
                    ->label(__t('admin_courses.final_tab.create'))
                    ->modalHeading(__t('admin_courses.final_tab.create_heading')),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make()
                    ->label(__t('admin_courses.final_tab.detach')),
                DeleteAction::make()
                    ->label(__t('admin_courses.final_tab.delete'))
                    ->visible(fn (Question $record): bool => $record->lesson_id === null),
            ]);
    }
}
