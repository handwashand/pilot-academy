<?php

namespace App\Filament\Actions;

use App\Actions\DraftTranslations;
use App\Models\Course;
use App\Models\Language;
use App\Services\Translator;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * "Translate whole course" on a course: its own text and every lesson's, into
 * one language, in one window.
 *
 * Step 1 picks the language and how — by hand, or drafted by an engine that is
 * switched on for this person (the same ones and the same rights as the
 * Translate menu). Step 2 is the review: the course and each lesson, drafts in
 * the boxes that were empty, nothing stored until Save translations. Text that
 * is already written is never sent to an engine or replaced by a draft.
 *
 * Only lessons written in the course's language are listed — a draft needs one
 * source language — and only those this person may edit.
 */
class TranslateCourseAction
{
    private const BY_HAND = 'manual';

    public static function make(): Action
    {
        return Action::make('translateWholeCourse')
            ->label(fn (): string => __t('admin_common.translate.course.button'))
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_common.translate.course.heading'))
            ->modalWidth('6xl')
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.submit'))
            ->visible(fn (Course $record): bool => static::languages($record)->isNotEmpty())
            ->steps(fn (Course $record): array => [
                Step::make(__t('admin_common.translate.course.step_choose'))
                    ->schema([
                        Select::make('language')
                            ->label(fn (): string => __t('admin_common.translate.course.language'))
                            ->options(static::languages($record)->mapWithKeys(fn (Language $language): array => [$language->code => $language->native_name])->all())
                            ->required()
                            ->live()
                            // Already translated? Say so: the next step is for editing it.
                            ->helperText(fn (Get $get): ?string => ($count = static::existingCount($record, $get('language'))) > 0
                                ? __t('admin_common.translate.course.existing', ['count' => $count])
                                : null),

                        Select::make('engine')
                            ->label(fn (): string => __t('admin_common.translate.course.engine'))
                            ->options(static::engineOptions())
                            ->default(self::BY_HAND)
                            ->required()
                            ->live()
                            ->visible(fn (): bool => count(static::engineOptions()) > 1)
                            ->helperText(fn (Get $get): ?string => static::isEngine($get('engine'))
                                ? __t('admin_common.translate.course.engine_help', ['provider' => TranslateContentAction::ENGINES[$get('engine')]['label']])
                                : null),

                        // Keeping what is there is the default; replacing it is a choice.
                        Checkbox::make('overwrite')
                            ->label(fn (): string => __t('admin_common.translate.draft.overwrite'))
                            ->visible(fn (Get $get): bool => static::isEngine($get('engine')) && static::existingCount($record, $get('language')) > 0),
                    ])
                    ->afterValidation(function (Get $get, Set $set) use ($record): void {
                        static::fill($record, (string) $get('language'), $get('engine'), $set, (bool) $get('overwrite'));
                    }),

                Step::make(__t('admin_common.translate.course.step_review'))
                    ->schema(fn (): array => static::reviewSections($record)),
            ])
            ->action(function (Course $record, array $data): void {
                $language = (string) ($data['language'] ?? '');

                if (! static::languages($record)->contains('code', $language)) {
                    return;
                }

                foreach (static::records($record) as $name => $model) {
                    foreach ($model->translatableFields() as $field) {
                        $model->setTranslation($field, $language, $data[$name][$field] ?? null);
                    }
                }

                Notification::make()->title(__t('admin_common.translate.course.saved'))->success()->send();
            });
    }

    /** Every active language except the one the course is written in. */
    private static function languages(Course $course): Collection
    {
        return app(Translator::class)->activeLanguages()
            ->reject(fn (Language $language): bool => $language->code === $course->contentLanguageCode())
            ->values();
    }

    /**
     * The course and the lessons written in its language that this person may
     * edit, by the name their boxes use in the form.
     *
     * @return array<string, Model>
     */
    private static function records(Course $course): array
    {
        $records = ['course' => $course];

        foreach ($course->lessons()->with('course')->get() as $lesson) {
            if ($lesson->contentLanguageCode() === $course->contentLanguageCode() && auth()->user()?->canManageCourse($lesson->course)) {
                $records["lesson_{$lesson->id}"] = $lesson;
            }
        }

        return $records;
    }

    /** @return array<string, string> */
    private static function engineOptions(): array
    {
        $options = [self::BY_HAND => __t('admin_common.translate.course.by_hand')];

        foreach (TranslateContentAction::ENGINES as $name => $config) {
            if (TranslateContentAction::engine($name) !== null) {
                $options[$name] = $config['label'];
            }
        }

        return $options;
    }

    private static function isEngine(mixed $choice): bool
    {
        return is_string($choice) && isset(TranslateContentAction::ENGINES[$choice]);
    }

    /**
     * Put the saved translations for the chosen language into the review boxes,
     * then draft the empty ones when an engine was chosen. The choice comes from
     * the browser, so the engine is looked up again here.
     */
    /** How many boxes of the course and its lessons already hold text in this language. */
    private static function existingCount(Course $course, mixed $code): int
    {
        if (! is_string($code) || $code === '') {
            return 0;
        }

        $count = 0;

        foreach (static::records($course) as $model) {
            $count += $model->contentTranslations()
                ->whereHas('language', fn ($query) => $query->where('code', $code))
                ->whereNotNull('value')
                ->count();
        }

        return $count;
    }

    private static function fill(Course $course, string $code, mixed $choice, Set $set, bool $overwrite = false): void
    {
        $language = static::languages($course)->firstWhere('code', $code);

        if (! $language) {
            return;
        }

        $records = static::records($course);
        $values = [];

        foreach ($records as $name => $model) {
            foreach ($model->translatableFields() as $field) {
                $values[$name][$field] = null;
            }

            foreach ($model->contentTranslations()->whereHas('language', fn ($query) => $query->where('code', $code))->get() as $translation) {
                $values[$name][$translation->field] = $translation->value;
            }
        }

        if (static::isEngine($choice) && ($engine = TranslateContentAction::engine($choice))) {
            // Asked to replace: draft every box. Still only in the form until Save translations.
            $result = app(DraftTranslations::class)->handleMany($records, $language, $overwrite ? [] : $values, $engine);

            foreach ($result['drafts'] as $name => $fields) {
                foreach ($fields as $field => $text) {
                    $values[$name][$field] = $text;
                }
            }

            TranslateContentAction::reportDraft($result, ['provider' => TranslateContentAction::ENGINES[$choice]['label']]);
        }

        foreach ($values as $name => $fields) {
            foreach ($fields as $field => $text) {
                $set("{$name}.{$field}", $text);
            }
        }
    }

    /** @return array<int, Section> */
    private static function reviewSections(Course $course): array
    {
        $sections = [];

        foreach (static::records($course) as $name => $model) {
            $sections[] = Section::make($name === 'course' ? __t('admin_common.translate.course.course') : (string) $model->getAttribute('title'))
                ->collapsible()
                ->collapsed($name !== 'course')
                ->schema(
                    collect($model->translatableFields())
                        ->map(fn (string $field) => TranslateContentAction::input($model, "{$name}.{$field}", $field))
                        ->all(),
                );
        }

        return $sections;
    }
}
