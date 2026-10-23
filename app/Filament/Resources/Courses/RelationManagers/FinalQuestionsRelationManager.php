<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Actions\ImportFinalQuizQuestions;
use App\Filament\Actions\TranslateQuestionsAction;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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

    protected static function getModelLabel(): ?string
    {
        return __t('admin_nav.questions.one');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __t('admin_nav.questions.many');
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
                TranslateQuestionsAction::forCourse($this->getOwnerRecord()),

                Action::make('addAllLessonQuestions')
                    ->label(__t('admin_courses.final_tab.add_all'))
                    ->tooltip(__t('admin_courses.final_tab.add_all'))
                    ->icon('heroicon-o-plus-circle')
                    ->iconButton()
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

                // The whole final quiz from one file, for a bank too long to
                // type in question by question — and the file to start from.
                ActionGroup::make([
                    Action::make('importFinalQuestions')
                        ->label(__t('admin_courses.final_import.upload'))
                        ->icon('heroicon-o-arrow-up-tray')
                        ->modalHeading(__t('admin_courses.final_import.heading'))
                        ->modalDescription(__t('admin_courses.final_import.description'))
                        ->modalSubmitActionLabel(__t('admin_courses.final_import.submit'))
                        ->schema([
                            FileUpload::make('file')
                                ->label(__t('admin_courses.final_import.file'))
                                ->helperText(__t('admin_courses.final_import.file_help', ['max' => ImportFinalQuizQuestions::MAX_QUESTIONS]))
                                ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                ->maxSize(1024)
                                // Read once and thrown away: the questions are
                                // what is kept, not the file.
                                ->storeFiles(false)
                                ->required(),
                        ])
                        ->action(function (array $data, Action $action): void {
                            $result = app(ImportFinalQuizQuestions::class)->handle($this->getOwnerRecord(), $data['file']->getRealPath());

                            if ($result['errors'] !== []) {
                                Notification::make()
                                    ->title(__t('admin_courses.final_import.failed'))
                                    ->body(implode("\n", array_slice($result['errors'], 0, 15)))
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }

                            Notification::make()
                                ->title(__t('admin_courses.final_import.done', ['count' => $result['added']]))
                                ->body($result['skipped'] > 0 ? __t('admin_courses.final_import.skipped', ['count' => $result['skipped']]) : null)
                                ->success()
                                ->send();
                        }),

                    Action::make('downloadFinalQuestionsExample')
                        ->label(__t('admin_courses.final_import.example'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn () => response()
                            ->download(app(ImportFinalQuizQuestions::class)->template(), 'final-quiz-example.xlsx')
                            ->deleteFileAfterSend()),
                ])
                    ->label(__t('admin_courses.final_import.group'))
                    ->tooltip(__t('admin_courses.final_import.group'))
                    ->icon('heroicon-o-table-cells')
                    ->color('gray')
                    ->iconButton(),
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
