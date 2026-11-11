<?php

namespace App\Filament\Resources\Lessons\Tables;

use App\Actions\AddLessonsToCourse;
use App\Actions\FindContentProblems;
use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Lesson;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Newest first: the lesson just written is the one being worked on.
            // Filament adds the key sort itself, so equal timestamps stay stable.
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['contentTranslations', 'courses.contentTranslations', 'creator']))
            ->columns([
                ImageColumn::make('cover')
                    ->label(__t('admin_lessons.table.cover'))
                    ->disk('public')
                    ->height(36)
                    ->state(fn ($record) => $record->media_item_id ? $record->mediaItem?->path : $record->image_path),

                // Titles in the admin's panel language where translated.
                TextColumn::make('courses.title')
                    ->label(__t('admin_common.courses'))
                    ->state(fn (Lesson $record): array => $record->courses->map(fn ($course): string => (string) $course->translated('title'))->all())
                    ->badge()
                    ->placeholder(__t('admin_lessons.table.no_course'))
                    // Orders by how many courses the lesson is in — what the
                    // badges show — rather than by their text. The count goes
                    // through the relation, so deleted courses are left out.
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->withCount('courses')
                        ->orderBy('courses_count', $direction)),

                // The lesson's place in its course, read from course_lesson: the
                // lessons.sort_order copy is not updated when a course's Lessons
                // tab is reordered, and a lesson in no course has no place at all.
                TextColumn::make('sort_order')
                    ->label('#')
                    ->state(fn (Lesson $record): ?int => static::placeInCourse($record)?->pivot->sort_order)
                    ->placeholder('—')
                    ->tooltip(fn (Lesson $record): ?string => static::placeInCourse($record)?->translated('title'))
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__t('admin_common.title'))
                    ->formatStateUsing(fn (Lesson $record): string => (string) $record->translated('title'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->matching($search))
                    ->sortable()
                    ->weight('bold'),

                static::creatorColumn(),

                TextColumn::make('color_tag')
                    ->label(__t('admin_lessons.table.color_tag'))
                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                        Lesson::COLOR_TAG_RED => '<span aria-hidden="true" style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#dc2626"></span>',
                        Lesson::COLOR_TAG_YELLOW => '<span aria-hidden="true" style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#eab308"></span>',
                        Lesson::COLOR_TAG_BLUE => '<span aria-hidden="true" style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#2563eb"></span>',
                        default => null,
                    })
                    ->html()
                    ->alignCenter()
                    ->size('xs')
                    ->tooltip(fn (Lesson $record): ?string => filled($record->color_tag)
                        ? collect([
                            Lesson::colorTagLabels()[$record->color_tag] ?? null,
                            $record->color_tag_note,
                        ])->filter()->implode(' · ')
                        : null)
                    ->sortable(),

                // Flagged where the work happens, not only on Content health.
                TextColumn::make('attention')
                    ->label(__t('admin_common.attention'))
                    ->state(function (Lesson $record): ?string {
                        $count = FindContentProblems::forCurrentUser()->where('lesson_id', $record->id)->count();

                        return $count > 0 ? __tc('admin_common.problems', $count) : null;
                    })
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->tooltip(fn (Lesson $record): ?string => FindContentProblems::forCurrentUser()
                        ->where('lesson_id', $record->id)
                        ->pluck('what')
                        ->unique()
                        ->implode(' · ') ?: null),

                TextColumn::make('questions_count')
                    ->label(__t('admin_lessons.table.quiz_questions'))
                    ->counts('questions')
                    ->badge(),

                IconColumn::make('has_video')
                    ->label(__t('admin_lessons.table.video'))
                    ->boolean()
                    ->state(fn ($record) => ! empty($record->videoEntries())),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Lesson::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Lesson::STATUS_PUBLISHED => 'success',
                        Lesson::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                Filter::make('needs_attention')
                    ->label(__t('admin_common.needs_attention'))
                    ->query(fn (Builder $query): Builder => $query->whereIn(
                        'id',
                        FindContentProblems::forCurrentUser()->pluck('lesson_id')->filter()->unique()->values()->all(),
                    )),

                static::noCourseFilter(),

                static::statusFilter(),

                SelectFilter::make('color_tag')
                    ->label(__t('admin_lessons.table.color_tag'))
                    ->options(Lesson::colorTagLabels()),

                static::courseFilter(),

                static::tagFilter(),

                static::creatorFilter(),

                static::addedFilter(),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label(__t('admin_common.publish'))
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_lessons.table.publish_heading'))
                    // Say what is wrong before anyone presses the button.
                    ->modalDescription(function (Lesson $record): string {
                        $problems = static::problemsIfPublished($record);

                        return $problems->isEmpty()
                            ? __t('admin_lessons.table.publish_ready', ['title' => $record->title])
                            : __t('admin_lessons.table.publish_blocked', ['problems' => FindContentProblems::plainList($problems)]);
                    })
                    ->visible(fn (Lesson $record): bool => $record->status === Lesson::STATUS_DRAFT)
                    ->authorize(fn (Lesson $record): bool => auth()->user()->canManageLesson($record))
                    ->action(function (Lesson $record): void {
                        $problems = static::problemsIfPublished($record);

                        if ($problems->isNotEmpty()) {
                            Notification::make()
                                ->title(__t('admin_common.fix_before_publishing'))
                                ->body(FindContentProblems::plainList($problems))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $record->publish();

                        Notification::make()->title(__t('admin_lessons.table.published'))->body(__t('admin_common.visible_now'))->success()->send();
                    }),

                Action::make('unpublish')
                    ->label(__t('admin_common.unpublish'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_lessons.table.unpublish_heading'))
                    ->modalDescription(function (Lesson $record): string {
                        $description = __t('admin_lessons.table.unpublish_description');

                        $emptied = static::liveCoursesItIsLastIn($record);

                        return $emptied->isNotEmpty()
                            ? __tc('admin_lessons.table.only_lesson_in', $emptied->count(), ['courses' => '"'.$emptied->implode('", "').'"']).' '.$description
                            : $description;
                    })
                    ->visible(fn (Lesson $record): bool => $record->isPublished())
                    ->authorize(fn (Lesson $record): bool => auth()->user()->canManageLesson($record))
                    ->action(function (Lesson $record): void {
                        $record->unpublish();

                        Notification::make()->title(__t('admin_lessons.table.unpublished'))->body(__t('admin_common.draft_again'))->warning()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('tagColor')
                        ->label(__t('admin_lessons.table.bulk_color_tag'))
                        ->icon('heroicon-o-tag')
                        ->modalWidth('xs')
                        ->schema([
                            ToggleButtons::make('color_tag')
                                ->label(__t('admin_lessons.form.color_tag'))
                                ->hiddenLabel()
                                ->options(Lesson::colorTagLabels())
                                ->icons([
                                    Lesson::COLOR_TAG_RED => new HtmlString('<span aria-hidden="true" style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:#dc2626"></span>'),
                                    Lesson::COLOR_TAG_YELLOW => new HtmlString('<span aria-hidden="true" style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:#eab308"></span>'),
                                    Lesson::COLOR_TAG_BLUE => new HtmlString('<span aria-hidden="true" style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:#2563eb"></span>'),
                                ])
                                ->tooltips(Lesson::colorTagLabels())
                                ->inline()
                                ->grouped()
                                ->hiddenButtonLabels()
                                ->required(),
                        ])
                        ->modalSubmitActionLabel(__t('admin_lessons.table.apply_color_tag'))
                        ->authorizeIndividualRecords(fn (Lesson $record): bool => auth()->user()->canManageLesson($record))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['color_tag' => $data['color_tag']]);
                            }

                            Notification::make()
                                ->title(__tc('admin_lessons.table.color_tag_applied', $records->count()))
                                ->success()
                                ->send();
                        }),

                    // Find the lessons with the list's own search and filters,
                    // then put them in a course in one go.
                    BulkAction::make('addToCourse')
                        ->label(__t('admin_lessons.table.add_to_course'))
                        ->icon('heroicon-o-plus-circle')
                        ->modalWidth('md')
                        ->schema([
                            Select::make('course_id')
                                ->label(__t('admin_common.course'))
                                ->options(fn (): array => CourseResource::getEloquentQuery()->orderBy('title')->pluck('title', 'id')->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->modalSubmitActionLabel(__t('admin_lessons.table.add_to_course_submit'))
                        ->authorizeIndividualRecords(fn (Lesson $record): bool => auth()->user()->canManageLesson($record))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data): void {
                            $course = Course::query()->find($data['course_id']);

                            if (! $course) {
                                return;
                            }

                            static::reportAdded(app(AddLessonsToCourse::class)->handle($course, $records->modelKeys(), auth()->user()), $course);
                        }),

                    BulkAction::make('publish')
                        ->label(__t('admin_common.publish'))
                        ->icon('heroicon-o-rocket-launch')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription(__t('admin_lessons.table.bulk_publish_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $skipped = [];

                            $ready = $records->filter(function (Lesson $lesson) use (&$skipped): bool {
                                $problems = static::problemsIfPublished($lesson);

                                if ($problems->isNotEmpty()) {
                                    $skipped[] = "{$lesson->title} ({$problems->pluck('what')->implode('; ')})";

                                    return false;
                                }

                                return true;
                            });

                            $ready->each->publish();

                            if ($ready->isNotEmpty()) {
                                Notification::make()->title(__tc('admin_lessons.table.bulk_published', $ready->count()))->success()->send();
                            }

                            if ($skipped !== []) {
                                Notification::make()
                                    ->title(__tc('admin_lessons.table.bulk_skipped', count($skipped)))
                                    ->body(implode('; ', $skipped))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                    BulkAction::make('unpublish')
                        ->label(__t('admin_common.unpublish'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalDescription(__t('admin_lessons.table.bulk_unpublish_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each->unpublish();

                            Notification::make()->title(__tc('admin_lessons.table.bulk_unpublished', $records->count()))->warning()->send();

                            $emptied = Course::query()
                                ->publishedButEmpty()
                                ->whereIn('id', CourseLesson::query()->whereIn('lesson_id', $records->pluck('id'))->pluck('course_id')->unique())
                                ->pluck('title');

                            if ($emptied->isNotEmpty()) {
                                Notification::make()
                                    ->title(__t('admin_lessons.table.empty_course'))
                                    ->body(__t('admin_lessons.table.empty_course_body', ['courses' => $emptied->implode(', ')]))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * What happened when lessons were put in a course — shared by the Lessons
     * list, the course's Browse lessons and the course form.
     *
     * @param  array{added: Collection, already: Collection, clashes: Collection}  $result
     */
    public static function reportAdded(array $result, Course $course): bool
    {
        if ($result['clashes']->isNotEmpty()) {
            Notification::make()
                ->title(__t('admin_courses.lessons_tab.slug_clash'))
                ->body(__t('admin_courses.lessons_tab.slug_clash_body', ['lessons' => $result['clashes']->implode(', ')]))
                ->danger()
                ->persistent()
                ->send();

            return false;
        }

        Notification::make()
            ->title(__tc('admin_lessons.table.added_to_course', $result['added']->count(), ['course' => $course->title]))
            ->body($result['already']->isNotEmpty()
                ? __tc('admin_lessons.table.already_in_course', $result['already']->count())
                : null)
            ->success()
            ->send();

        return true;
    }

    /** The course whose order the # column shows: the lesson's home course, else the first it is in. */
    protected static function placeInCourse(Lesson $lesson): ?Course
    {
        return $lesson->courses->firstWhere('id', $lesson->course_id) ?? $lesson->courses->first();
    }

    public static function creatorColumn(): TextColumn
    {
        return TextColumn::make('creator.name')
            ->label(__t('admin_lessons.table.created_by'))
            ->placeholder('—')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    // Removed from every course: students cannot reach these.
    public static function noCourseFilter(): Filter
    {
        return Filter::make('no_course')
            ->label(__t('admin_lessons.table.no_course'))
            ->query(fn (Builder $query): Builder => $query->whereDoesntHave('courses'));
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label(__t('admin_common.status'))
            ->options(Lesson::statusLabels());
    }

    public static function courseFilter(): SelectFilter
    {
        return SelectFilter::make('course')
            ->label(__t('admin_common.course'))
            ->relationship('courses', 'title', function ($query) {
                $user = auth()->user();

                return $user?->isCreator()
                    ? $query->whereIn('product_id', $user->products()->pluck('products.id'))
                    : $query;
            })
            ->searchable()
            ->preload();
    }

    public static function tagFilter(): SelectFilter
    {
        return SelectFilter::make('tag')
            ->label(__t('admin_lessons.table.tag'))
            ->options(fn (): array => Lesson::tagOptions())
            ->searchable()
            ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                ? $query->tagged($data['value'])
                : $query);
    }

    // A creator only ever sees their own lessons and courses, so only their own name.
    public static function creatorFilter(): SelectFilter
    {
        return SelectFilter::make('created_by')
            ->label(__t('admin_lessons.table.created_by'))
            ->options(function (): array {
                $user = auth()->user();

                return User::query()
                    ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_CREATOR])
                    ->when($user?->isCreator(), fn (Builder $users): Builder => $users->whereKey($user->id))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all();
            })
            ->searchable();
    }

    public static function addedFilter(): Filter
    {
        return Filter::make('added')
            ->label(__t('admin_lessons.table.added'))
            ->schema([
                DatePicker::make('from')->label(__t('admin_lessons.table.added_from')),
                DatePicker::make('until')->label(__t('admin_lessons.table.added_until')),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('lessons.created_at', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('lessons.created_at', '<=', $date)));
    }

    /**
     * What a student would hit if this lesson went live now. Only a live course
     * matters — in a draft course nobody sees the lesson yet, and it stays
     * editable in peace.
     */
    protected static function problemsIfPublished(Lesson $lesson): Collection
    {
        if (! $lesson->courses()->published()->exists()) {
            return collect();
        }

        $problems = collect();

        if (! $lesson->questions()->exists()) {
            $problems->push(['what' => __t('admin_lessons.table.no_questions'), 'name' => $lesson->title]);
        }

        if ($lesson->questions()->withoutCorrectAnswer()->exists()) {
            $problems->push(['what' => __t('admin_lessons.table.no_correct_answer'), 'name' => $lesson->title]);
        }

        if ($lesson->hasUnplayableYoutubeLink()) {
            $problems->push(['what' => __t('admin_lessons.table.bad_youtube'), 'name' => $lesson->title]);
        }

        return $problems;
    }

    /** Titles of the live courses this lesson is the only published lesson in. */
    protected static function liveCoursesItIsLastIn(Lesson $lesson): Collection
    {
        if (! $lesson->isPublished()) {
            return collect();
        }

        return $lesson->courses()->published()->withCount('publishedLessons')->get()
            ->filter(fn (Course $course): bool => $course->published_lessons_count === 1)
            ->pluck('title');
    }
}
