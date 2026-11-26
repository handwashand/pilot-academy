<?php

namespace App\Filament\Actions;

use App\Actions\TranslateLessonVideo;
use App\Models\Course;
use App\Models\DescriptImport;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoTranslation;
use App\Services\Translator;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "Translate video" and "Check progress" on a lesson's edit page.
 *
 * A language already done or under way is listed with its status and cannot be
 * ticked — the point of the whole feature is that nothing is paid for twice. A
 * failed one can be ticked, to try again.
 *
 * Only uploaded videos are offered; Descript imports a file, and a YouTube link
 * is not one. See docs/descript-integration.md.
 */
class TranslateVideoWithDescriptAction
{
    public static function make(): Action
    {
        return Action::make('translateVideoWithDescript')
            ->label(fn (): string => __t('admin_descript.action.button'))
            ->tooltip(fn (): string => __t('admin_descript.action.button'))
            ->icon(Heroicon::OutlinedSpeakerWave)
            ->iconButton()
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_descript.action.heading'))
            ->modalDescription(fn (): string => __t('admin_descript.action.description'))
            ->modalSubmitActionLabel(fn (): string => __t('admin_descript.action.submit'))
            ->authorize(fn (): bool => (bool) auth()->user()?->hasPermission(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->visible(fn (Lesson $record): bool => app(TranslateLessonVideo::class)->enabled()
                && TranslateLessonVideo::uploadedVideos($record) !== []
                && TranslateLessonVideo::targetLanguages($record) !== [])
            ->fillForm(fn (Lesson $record): array => [
                'video' => array_key_first(TranslateLessonVideo::uploadedVideos($record)),
                'languages' => [],
                'confirmed' => false,
            ])
            ->schema(fn (Lesson $record): array => [
                Select::make('video')
                    ->label(fn (): string => __t('admin_descript.action.video'))
                    ->options(TranslateLessonVideo::uploadedVideos($record))
                    ->required()
                    ->live(),

                CheckboxList::make('languages')
                    ->label(fn (): string => __t('admin_descript.action.languages'))
                    ->helperText(fn (): string => __t('admin_descript.action.languages_help'))
                    ->options(fn (Get $get): array => static::languageOptions($record, (string) $get('video')))
                    ->disableOptionWhen(fn (string $value, Get $get): bool => static::isTaken($record, (string) $get('video'), $value))
                    ->required()
                    ->columns(2),

                Checkbox::make('confirmed')
                    ->label(fn (): string => __t('admin_descript.action.confirmation'))
                    ->accepted()
                    ->required()
                    ->validationMessages([
                        'accepted' => fn (): string => __t('admin_descript.action.confirmation_required'),
                    ])
                    ->columnSpanFull(),
            ])
            ->action(function (Lesson $record, array $data): void {
                $outcome = app(TranslateLessonVideo::class)->request(
                    $record,
                    (string) $data['video'],
                    (array) ($data['languages'] ?? []),
                    auth()->user(),
                    kind: VideoTranslation::KIND_DUB,
                );

                if ($outcome['requested'] === []) {
                    Notification::make()
                        ->title(__t('admin_descript.action.nothing_new'))
                        ->body(__t('admin_descript.action.nothing_new_body'))
                        ->warning()
                        ->send();

                    return;
                }

                $names = TranslateLessonVideo::targetLanguages($record);

                Notification::make()
                    ->title(__t('admin_descript.action.requested'))
                    ->body(__t('admin_descript.action.requested_body', [
                        'languages' => collect($outcome['requested'])->map(fn (string $code): string => $names[$code] ?? $code)->implode(', '),
                    ]))
                    ->success()
                    ->send();
            });
    }

    /**
     * "Translate lesson videos" on a course: every uploaded video in its lessons,
     * for the languages ticked. Same right, same acknowledgement, same promise —
     * a video or language already done or under way is skipped and costs nothing.
     *
     * Requests are only recorded here; Check progress (and descript:sync) start
     * them a few at a time, so a big course cannot run out a single web request.
     */
    public static function course(): Action
    {
        return Action::make('translateCourseVideosWithDescript')
            ->label(fn (): string => __t('admin_descript.course.button'))
            ->tooltip(fn (): string => __t('admin_descript.course.button'))
            ->icon(Heroicon::OutlinedSpeakerWave)
            ->iconButton()
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_descript.course.heading'))
            ->modalDescription(fn (Course $record): string => static::courseDescription($record))
            ->modalSubmitActionLabel(fn (): string => __t('admin_descript.course.submit'))
            ->authorize(fn (): bool => (bool) auth()->user()?->hasPermission(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->visible(fn (Course $record): bool => app(TranslateLessonVideo::class)->enabled()
                && TranslateLessonVideo::courseVideos($record, auth()->user()) !== []
                && static::courseLanguages($record) !== [])
            ->fillForm(['languages' => [], 'confirmed' => false])
            ->schema(fn (Course $record): array => [
                CheckboxList::make('languages')
                    ->label(fn (): string => __t('admin_descript.action.languages'))
                    ->options(static::courseLanguages($record))
                    ->required()
                    ->columns(2),

                Checkbox::make('confirmed')
                    ->label(fn (): string => __t('admin_descript.action.confirmation'))
                    ->accepted()
                    ->required()
                    ->validationMessages([
                        'accepted' => fn (): string => __t('admin_descript.action.confirmation_required'),
                    ])
                    ->columnSpanFull(),
            ])
            ->action(function (Course $record, array $data): void {
                $user = auth()->user();
                $languages = (array) ($data['languages'] ?? []);
                $queued = 0;

                foreach (TranslateLessonVideo::courseVideos($record, $user) as $video) {
                    $queued += count(app(TranslateLessonVideo::class)->request($video['lesson'], $video['path'], $languages, $user, advance: false, kind: VideoTranslation::KIND_DUB)['requested']);
                }

                if ($queued === 0) {
                    Notification::make()
                        ->title(__t('admin_descript.action.nothing_new'))
                        ->body(__t('admin_descript.action.nothing_new_body'))
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__t('admin_descript.course.queued'))
                    ->body(__t('admin_descript.course.queued_body', ['count' => $queued]))
                    ->success()
                    ->send();
            });
    }

    /** Moves a course's translations along for a while, then says where they got to. */
    public static function checkCourse(): Action
    {
        return Action::make('checkDescriptCourseProgress')
            ->label(fn (): string => __t('admin_descript.action.check'))
            ->tooltip(fn (): string => __t('admin_descript.action.check'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->iconButton()
            ->color('gray')
            ->authorize(fn (): bool => (bool) auth()->user()?->hasPermission(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->visible(fn (Course $record): bool => app(TranslateLessonVideo::class)->enabled()
                && static::courseRows($record)->inFlight()->exists())
            ->action(function (Course $record): void {
                $translator = app(TranslateLessonVideo::class);
                $deadline = microtime(true) + 20; // stay well inside one web request

                foreach ($record->lessons()->get() as $lesson) {
                    if (microtime(true) > $deadline) {
                        break;
                    }

                    $translator->advanceLesson($lesson);
                }

                $rows = static::courseRows($record)->get();

                Notification::make()
                    ->title(__t('admin_descript.action.checked'))
                    ->body(__t('admin_descript.action.checked_body', [
                        'done' => $rows->where('status', VideoTranslation::STATUS_DONE)->count(),
                        'running' => $rows->filter(fn (VideoTranslation $row): bool => $row->isInFlight())->count(),
                        'failed' => $rows->where('status', VideoTranslation::STATUS_FAILED)->count(),
                    ]))
                    ->info()
                    ->send();
            });
    }

    /**
     * Cancel translations still under way for this lesson, by language. They
     * become "failed — tick to try again", so Translate video can start them
     * afresh later. Nothing is recalled that Descript is already doing.
     */
    public static function cancel(): Action
    {
        return static::cancelAction(
            'cancelDescriptTranslations',
            fn (Lesson $record) => VideoTranslation::query()->where('lesson_id', $record->id),
        );
    }

    /**
     * Make a finished dub again — after a wording fix, or a voice nobody liked.
     * The only way to spend credits twice on one language, so it has its own
     * button and the same acknowledgement as Translate video.
     */
    public static function remake(): Action
    {
        return Action::make('remakeDescriptTranslation')
            ->label(fn (): string => __t('admin_descript.remake.button'))
            ->tooltip(fn (): string => __t('admin_descript.remake.button'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->iconButton()
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_descript.remake.heading'))
            ->modalDescription(fn (): string => __t('admin_descript.remake.description'))
            ->modalSubmitActionLabel(fn (): string => __t('admin_descript.remake.submit'))
            ->authorize(fn (): bool => (bool) auth()->user()?->hasPermission(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->visible(fn (Lesson $record): bool => app(TranslateLessonVideo::class)->enabled()
                && static::finishedVideos($record) !== [])
            ->fillForm(fn (Lesson $record): array => [
                'video' => array_key_first(static::finishedVideos($record)),
                'languages' => [],
                'confirmed' => false,
            ])
            ->schema(fn (Lesson $record): array => [
                Select::make('video')
                    ->label(fn (): string => __t('admin_descript.action.video'))
                    ->options(static::finishedVideos($record))
                    ->required()
                    ->live(),
                CheckboxList::make('languages')
                    ->label(fn (): string => __t('admin_descript.action.languages'))
                    ->options(fn (Get $get): array => static::finishedLanguages($record, (string) $get('video')))
                    ->required(),
                Checkbox::make('confirmed')
                    ->label(fn (): string => __t('admin_descript.action.confirmation'))
                    ->accepted()
                    ->validationMessages(['accepted' => __t('admin_descript.action.confirmation_required')]),
            ])
            ->action(function (Lesson $record, array $data): void {
                $video = (string) $data['video'];
                $languages = array_values(array_intersect((array) ($data['languages'] ?? []), array_keys(static::finishedLanguages($record, $video))));
                $outcome = app(TranslateLessonVideo::class)->remake($record, $video, $languages, auth()->user());
                $names = TranslateLessonVideo::targetLanguages($record);

                Notification::make()
                    ->title(__t('admin_descript.remake.done'))
                    ->body(__t('admin_descript.action.requested_body', [
                        'languages' => collect($outcome['requested'])->map(fn (string $code): string => $names[$code] ?? $code)->implode(', '),
                    ]))
                    ->success()
                    ->send();
            });
    }

    /** @return array<string, string> path => name, for uploaded videos with at least one finished dub */
    private static function finishedVideos(Lesson $record): array
    {
        return collect(TranslateLessonVideo::uploadedVideos($record))
            ->filter(fn (string $name, string $path): bool => static::finishedLanguages($record, $path) !== [])
            ->all();
    }

    /** @return array<string, string> code => native name, for this video's finished dubs */
    private static function finishedLanguages(Lesson $record, string $videoPath): array
    {
        $names = TranslateLessonVideo::targetLanguages($record);

        return static::rowsFor($record, $videoPath)
            ->filter(fn (VideoTranslation $row): bool => $row->isDone())
            ->mapWithKeys(fn (VideoTranslation $row, string $code): array => [$code => $names[$code] ?? $code])
            ->all();
    }

    /** The same for every lesson video in a course: one language at a time, or several. */
    public static function cancelCourse(): Action
    {
        return static::cancelAction(
            'cancelDescriptCourseTranslations',
            fn (Course $record) => static::courseRows($record),
        );
    }

    /** @param  callable(Lesson|Course): Builder<VideoTranslation>  $rows */
    private static function cancelAction(string $name, callable $rows): Action
    {
        return Action::make($name)
            ->label(fn (): string => __t('admin_descript.cancel.button'))
            ->tooltip(fn (): string => __t('admin_descript.cancel.button'))
            ->icon(Heroicon::OutlinedXCircle)
            ->iconButton()
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_descript.cancel.heading'))
            ->modalDescription(fn (): string => __t('admin_descript.cancel.description'))
            ->modalSubmitActionLabel(fn (): string => __t('admin_descript.cancel.submit'))
            ->authorize(fn (): bool => (bool) auth()->user()?->hasPermission(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->visible(fn (Lesson|Course $record): bool => $rows($record)->inFlight()->exists())
            ->fillForm(['languages' => []])
            ->schema(fn (Lesson|Course $record): array => [
                CheckboxList::make('languages')
                    ->label(fn (): string => __t('admin_descript.action.languages'))
                    ->options(static::runningLanguages($rows($record)))
                    ->required(),
            ])
            ->action(function (Lesson|Course $record, array $data) use ($rows): void {
                $languages = array_values(array_intersect((array) ($data['languages'] ?? []), array_keys(static::runningLanguages($rows($record)))));
                $cancelled = app(TranslateLessonVideo::class)->cancel($rows($record)->whereIn('language', $languages), auth()->user());

                Notification::make()
                    ->title(__t('admin_descript.cancel.done'))
                    ->body(__tc('admin_descript.cancel.done_body', $cancelled))
                    ->success()
                    ->send();
            });
    }

    /**
     * Languages with something still under way, with how many and where they got to.
     *
     * @param  Builder<VideoTranslation>  $rows
     * @return array<string, string> code => "Español — 7 waiting"
     */
    private static function runningLanguages($rows): array
    {
        $names = app(Translator::class)->activeLanguages()->pluck('native_name', 'code');

        return (clone $rows)->inFlight()->get()
            ->groupBy('language')
            ->map(fn (Collection $group, string $code): string => __t('admin_descript.cancel.option', [
                'language' => $names[$code] ?? $code,
                'count' => $group->count(),
            ]))
            ->all();
    }

    /** Every active language except the one the course is written in. */
    private static function courseLanguages(Course $course): array
    {
        return app(Translator::class)->activeLanguages()
            ->reject(fn (Language $language): bool => $language->code === $course->contentLanguageCode())
            ->mapWithKeys(fn (Language $language): array => [$language->code => $language->native_name])
            ->all();
    }

    private static function courseDescription(Course $course): string
    {
        $videos = TranslateLessonVideo::courseVideos($course, auth()->user());

        return __t('admin_descript.course.description', [
            'videos' => count($videos),
            'lessons' => collect($videos)->pluck('lesson.id')->unique()->count(),
        ]);
    }

    /** Descript's rows for the videos of this course's lessons. */
    private static function courseRows(Course $course)
    {
        return VideoTranslation::query()->whereIn('lesson_id', $course->lessons()->pluck('lessons.id'));
    }

    /** Shown only while something is still under way for this lesson. */
    public static function check(): Action
    {
        return Action::make('checkDescriptProgress')
            ->label(fn (): string => __t('admin_descript.action.check'))
            ->tooltip(fn (): string => __t('admin_descript.action.check'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->iconButton()
            ->color('gray')
            ->authorize(fn (): bool => (bool) auth()->user()?->hasPermission(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->visible(fn (Lesson $record): bool => app(TranslateLessonVideo::class)->enabled()
                && VideoTranslation::query()->where('lesson_id', $record->id)->inFlight()->exists())
            ->action(function (Lesson $record): void {
                app(TranslateLessonVideo::class)->advanceLesson($record);

                $rows = VideoTranslation::query()->where('lesson_id', $record->id)->get();

                Notification::make()
                    ->title(__t('admin_descript.action.checked'))
                    ->body(__t('admin_descript.action.checked_body', [
                        'done' => $rows->where('status', VideoTranslation::STATUS_DONE)->count(),
                        'running' => $rows->filter(fn (VideoTranslation $row): bool => $row->isInFlight())->count(),
                        'failed' => $rows->where('status', VideoTranslation::STATUS_FAILED)->count(),
                    ]))
                    ->info()
                    ->send();
            });
    }

    /** @return array<string, string> code => "Français — done" */
    private static function languageOptions(Lesson $record, string $videoPath): array
    {
        $rows = static::rowsFor($record, $videoPath);

        return collect(TranslateLessonVideo::targetLanguages($record))
            ->mapWithKeys(function (string $name, string $code) use ($rows): array {
                $row = $rows->get($code);

                return [$code => $row
                    ? __t('admin_descript.action.option', ['language' => $name, 'status' => __t('admin_descript.status.'.$row->status)])
                    : $name];
            })
            ->all();
    }

    /** Done or under way: not to be sent again. A failed one may be. */
    private static function isTaken(Lesson $record, string $videoPath, string $code): bool
    {
        $row = static::rowsFor($record, $videoPath)->get($code);

        return $row !== null && ($row->isDone() || $row->isInFlight());
    }

    /**
     * The dubbed versions of this video, keyed by language. A dub carries its
     * own subtitles, so it is the one thing this button makes.
     *
     * @return Collection<string, VideoTranslation>
     */
    private static function rowsFor(Lesson $record, string $videoPath): Collection
    {
        $import = DescriptImport::query()
            ->where('lesson_id', $record->id)
            ->where('video_path', $videoPath)
            ->first();

        return $import
            ? $import->translations()->where('kind', VideoTranslation::KIND_DUB)->get()->keyBy('language')
            : collect();
    }
}
