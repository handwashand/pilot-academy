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
            ->icon(Heroicon::OutlinedSpeakerWave)
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
            ->icon(Heroicon::OutlinedSpeakerWave)
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
                    $queued += count(app(TranslateLessonVideo::class)->request($video['lesson'], $video['path'], $languages, $user, advance: false)['requested']);
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
            ->icon(Heroicon::OutlinedArrowPath)
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
            ->icon(Heroicon::OutlinedArrowPath)
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

    /** @return Collection<string, VideoTranslation> keyed by language */
    private static function rowsFor(Lesson $record, string $videoPath): Collection
    {
        $import = DescriptImport::query()
            ->where('lesson_id', $record->id)
            ->where('video_path', $videoPath)
            ->first();

        return $import
            ? $import->translations()->where('kind', VideoTranslation::KIND_TRANSCRIPT)->get()->keyBy('language')
            : collect();
    }
}
