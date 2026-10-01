<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Schemas\LessonForm;
use App\Models\Lesson;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __t('admin_nav.tabs.lessons');
    }

    // Names the records in Filament's own messages ("No lessons", "Delete
    // lesson"); left alone it reads the English model name.
    protected static function getModelLabel(): ?string
    {
        return __t('admin_nav.lessons.one');
    }

    protected static function getPluralModelLabel(): ?string
    {
        return __t('admin_nav.lessons.many');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            // Drag the rows to set the order students see in this course. Each
            // course keeps its own order, so a shared lesson can sit first in
            // one and last in another.
            ->reorderable('course_lesson.sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label(__t('admin_common.lesson'))
                    ->wrap()
                    ->searchable()
                    ->description(fn (Lesson $record): ?string => $this->alsoIn($record)),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Lesson::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Lesson::STATUS_PUBLISHED => 'success',
                        Lesson::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('duration_minutes')
                    ->label(__t('admin_courses.lessons_tab.length'))
                    ->formatStateUsing(fn (?int $state): string => Lesson::formatMinutes($state) ?? '—')
                    ->tooltip(__t('admin_courses.lessons_tab.length_tip')),

                TextColumn::make('questions_count')
                    ->label(__t('admin_courses.lessons_tab.questions'))
                    ->counts('questions')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'gray' : 'danger')
                    ->tooltip(fn (int $state): ?string => $state > 0
                        ? null
                        : __t('admin_courses.lessons_tab.no_quiz_tip')),
            ])
            ->headerActions([
                // Writing a lesson starts here, in the course it belongs to —
                // without going to Lessons, creating one, and choosing the
                // course back again. CourseLessons::create() gives it this
                // course as its home and the course's language; the stock
                // create would save it with no course at all, which the
                // lessons table refuses.
                CreateAction::make()
                    ->label(__t('admin_courses.lessons_tab.create'))
                    ->modalHeading(__t('admin_courses.lessons_tab.create_heading'))
                    ->modalDescription(__t('admin_courses.lessons_tab.create_description'))
                    ->modalSubmitActionLabel(__t('admin_courses.lessons_tab.create_submit'))
                    ->schema(fn (): array => LessonForm::insideCourse($this->getOwnerRecord()))
                    ->using(fn (array $data): Lesson => $this->getOwnerRecord()->lessons()->create($data)),

                AttachAction::make()
                    ->label(__t('admin_courses.lessons_tab.attach'))
                    ->modalHeading(__t('admin_courses.lessons_tab.attach_heading'))
                    ->modalDescription(__t('admin_courses.lessons_tab.attach_description'))
                    ->modalSubmitActionLabel(__t('admin_courses.lessons_tab.attach_submit'))
                    ->multiple()
                    // Filament leaves the list empty until something is typed,
                    // which reads as "there is nothing to add". The lessons of
                    // every other course are there to choose from, so show them.
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title'])
                    ->recordTitle(fn (Lesson $record): string => $record->course
                        ? __t('admin_courses.lessons_tab.from_course', ['lesson' => $record->title, 'course' => $record->course->title])
                        : $record->title)
                    ->recordSelectOptionsQuery(function (Builder $query): Builder {
                        $user = auth()->user();

                        // Same scoping as the Lessons list: a creator can only
                        // bring in lessons from courses in their own products.
                        if ($user?->isCreator()) {
                            $productIds = $user->products()->pluck('products.id');

                            $query->whereHas('course', fn (Builder $course) => $course->whereIn('product_id', $productIds));
                        }

                        return $query
                            // Already in this course: offering it again would
                            // only attach it twice.
                            ->whereDoesntHave('courses', fn (Builder $courses) => $courses->whereKey($this->getOwnerRecord()->getKey()))
                            // Only the three columns the list needs. Filament
                            // runs this as `distinct lessons.*`, and PostgreSQL
                            // cannot compare the json columns (doc_links,
                            // video_sources) that `*` drags in — it fails with
                            // "could not identify an equality operator for type
                            // json". SQLite does not care, so the suite cannot
                            // see this one.
                            ->select(['lessons.id', 'lessons.title', 'lessons.course_id'])
                            ->with('course')
                            // The relation is ordered by the pivot's sort_order,
                            // which this list does not select — and under
                            // `distinct` PostgreSQL insists every ordering
                            // column is selected. The picker reads better by
                            // name anyway.
                            ->reorder('lessons.title');
                    })
                    // The student URL finds a lesson by its slug inside the
                    // course, so two lessons with one slug cannot share a course.
                    ->before(function (array $data, AttachAction $action): void {
                        $clashes = Lesson::query()
                            ->whereIn('id', (array) ($data['recordId'] ?? []))
                            ->whereIn('slug', $this->getOwnerRecord()->lessons()->pluck('lessons.slug'))
                            ->pluck('title');

                        if ($clashes->isNotEmpty()) {
                            Notification::make()
                                ->title(__t('admin_courses.lessons_tab.slug_clash'))
                                ->body(__t('admin_courses.lessons_tab.slug_clash_body', ['lessons' => $clashes->implode(', ')]))
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Lesson $record): string => LessonResource::getUrl('edit', ['record' => $record])),

                DetachAction::make()
                    ->label(__t('admin_courses.lessons_tab.detach'))
                    ->modalHeading(__t('admin_courses.lessons_tab.detach_heading'))
                    ->modalDescription(__t('admin_courses.lessons_tab.detach_description'))
                    // Its last course: removing it would leave the lesson in
                    // no course at all. Delete it from Lessons instead.
                    ->visible(fn (Lesson $record): bool => $record->courses()->count() > 1),
            ])
            ->emptyStateHeading(__t('admin_courses.lessons_tab.empty'))
            ->emptyStateDescription(__t('admin_courses.lessons_tab.empty_description'));
    }

    /** "Also in: X, Y" for a lesson shared with other courses. */
    private function alsoIn(Lesson $record): ?string
    {
        $others = $record->courses()
            ->whereKeyNot($this->getOwnerRecord()->getKey())
            ->pluck('title');

        return $others->isNotEmpty() ? __t('admin_courses.lessons_tab.also_in', ['courses' => $others->implode(', ')]) : null;
    }
}
