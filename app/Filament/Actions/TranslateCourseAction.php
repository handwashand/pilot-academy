<?php

namespace App\Filament\Actions;

use App\Actions\DraftTranslations;
use App\Models\Course;
use App\Models\Language;
use App\Models\Question;
use App\Services\Translator;
use Closure;
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
use Illuminate\Support\Str;

/**
 * "Translate" on a course: the one button for its text.
 *
 * Step 1 asks what (this course's own title and description, or the whole
 * course — every lesson, each lesson's quiz and the final quiz), how (by hand,
 * or drafted by an engine switched on for this person), and into which
 * language. Step 2 is the review: drafts in the boxes that were empty, nothing
 * stored until Save translations. Text already written is never sent to an
 * engine or replaced, unless "Replace" is ticked.
 *
 * "All languages at once" needs an engine, and skips the review: five
 * languages of a whole course would not fit in one window (or one request).
 * The engine fills only empty boxes and they are saved; opening Translate
 * again for one language is where they are read and corrected.
 *
 * Only lessons and questions written in the course's language are included —
 * a draft needs one source language — and only lessons this person may edit.
 */
class TranslateCourseAction
{
    private const BY_HAND = 'manual';

    private const ALL = '*all';

    private const SCOPE_COURSE = 'course';

    private const SCOPE_WHOLE = 'whole';

    public static function make(): Action
    {
        return Action::make('translateWholeCourse')
            ->label(fn (): string => __t('admin_common.translate.button'))
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_common.translate.course.heading'))
            ->modalWidth('6xl')
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.submit'))
            ->visible(fn (Course $record): bool => static::languages($record)->isNotEmpty())
            ->steps(fn (Course $record): array => [
                Step::make(__t('admin_common.translate.course.step_choose'))
                    ->schema([
                        Select::make('scope')
                            ->label(fn (): string => __t('admin_common.translate.course.scope'))
                            ->options(fn (): array => [
                                self::SCOPE_COURSE => __t('admin_common.translate.course.scope_course'),
                                self::SCOPE_WHOLE => __t('admin_common.translate.course.scope_whole'),
                            ])
                            ->default(self::SCOPE_COURSE)
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live(),

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

                        Select::make('language')
                            ->label(fn (): string => __t('admin_common.translate.course.language'))
                            // All at once only with an engine: nobody types five languages in one window.
                            ->options(fn (Get $get): array => [
                                ...(static::isEngine($get('engine')) ? [self::ALL => __t('admin_common.translate.course.all_languages')] : []),
                                ...static::languages($record)->mapWithKeys(fn (Language $language): array => [$language->code => $language->native_name])->all(),
                            ])
                            ->required()
                            ->live()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                    if ($value === self::ALL && ! static::isEngine($get('engine'))) {
                                        $fail(__t('admin_common.translate.course.all_needs_engine'));
                                    }
                                },
                            ])
                            ->helperText(fn (Get $get): ?string => match (true) {
                                $get('language') === self::ALL && static::isEngine($get('engine')) => __t('admin_common.translate.course.all_help', ['provider' => TranslateContentAction::ENGINES[$get('engine')]['label']]),
                                ($count = static::existingCount($record, (string) $get('scope'), $get('language'))) > 0 => __t('admin_common.translate.course.existing', ['count' => $count]),
                                default => null,
                            }),

                        // Keeping what is there is the default; replacing it is a choice.
                        Checkbox::make('overwrite')
                            ->label(fn (): string => __t('admin_common.translate.draft.overwrite'))
                            ->visible(fn (Get $get): bool => static::isEngine($get('engine'))
                                && ($get('language') === self::ALL || static::existingCount($record, (string) $get('scope'), $get('language')) > 0)),
                    ])
                    ->afterValidation(function (Get $get, Set $set) use ($record): void {
                        if ($get('language') !== self::ALL) {
                            static::fill($record, (string) $get('scope'), (string) $get('language'), $get('engine'), $set, (bool) $get('overwrite'));
                        }
                    }),

                Step::make(__t('admin_common.translate.course.step_review'))
                    ->visible(fn (Get $get): bool => $get('language') !== self::ALL)
                    ->schema(fn (Get $get): array => static::reviewSections($record, (string) $get('scope'))),
            ])
            ->action(function (Course $record, array $data): void {
                $scope = (string) ($data['scope'] ?? self::SCOPE_COURSE);
                $language = (string) ($data['language'] ?? '');

                if ($language === self::ALL) {
                    static::draftAndSaveAll($record, $scope, $data['engine'] ?? null, (bool) ($data['overwrite'] ?? false));

                    return;
                }

                if (! static::languages($record)->contains('code', $language)) {
                    return;
                }

                foreach (static::plan($record, $scope)['records'] as $name => $model) {
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
     * What gets translated, by the name its boxes use in the form, and how the
     * review groups them: the course; then each lesson followed by its quiz
     * questions (each question with its answer options); then the final
     * questions that are not already a lesson's.
     *
     * @return array{records: array<string, Model>, sections: array<int, array{title: string, names: array<int, string>}>}
     */
    private static function plan(Course $course, string $scope): array
    {
        $records = ['course' => $course];
        $sections = [['title' => __t('admin_common.translate.course.course'), 'names' => ['course']]];

        if ($scope !== self::SCOPE_WHOLE) {
            return ['records' => $records, 'sections' => $sections];
        }

        $source = $course->contentLanguageCode();
        $seen = [];

        $addQuestion = function (Question $question, string $title) use (&$records, &$sections, &$seen, $source): void {
            if (isset($seen[$question->id]) || $question->contentLanguageCode() !== $source) {
                return;
            }

            $seen[$question->id] = true;
            $names = ["question_{$question->id}"];
            $records["question_{$question->id}"] = $question;

            foreach ($question->options as $option) {
                $records["option_{$option->id}"] = $option;
                $names[] = "option_{$option->id}";
            }

            $sections[] = ['title' => __t($title, ['question' => Str::limit((string) $question->prompt, 60)]), 'names' => $names];
        };

        foreach ($course->lessons()->with(['course', 'questions.options'])->get() as $lesson) {
            if ($lesson->contentLanguageCode() !== $source || ! auth()->user()?->canManageLesson($lesson)) {
                continue;
            }

            $records["lesson_{$lesson->id}"] = $lesson;
            $sections[] = ['title' => (string) $lesson->title, 'names' => ["lesson_{$lesson->id}"]];

            foreach ($lesson->questions as $question) {
                $addQuestion($question, 'admin_common.translate.course.quiz_question');
            }
        }

        foreach ($course->finalQuestions()->with('options')->get() as $question) {
            $addQuestion($question, 'admin_common.translate.course.final_question');
        }

        return ['records' => $records, 'sections' => $sections];
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

    /** How many boxes of what is being translated already hold text in this language. */
    private static function existingCount(Course $course, string $scope, mixed $code): int
    {
        if (! is_string($code) || $code === '' || $code === self::ALL) {
            return 0;
        }

        $count = 0;

        foreach (static::plan($course, $scope)['records'] as $model) {
            $count += $model->contentTranslations()
                ->whereHas('language', fn ($query) => $query->where('code', $code))
                ->whereNotNull('value')
                ->count();
        }

        return $count;
    }

    /**
     * The saved translations of every record in one language, by record name
     * then field; null where there is none.
     *
     * @param  array<string, Model>  $records
     * @return array<string, array<string, ?string>>
     */
    private static function current(array $records, string $code): array
    {
        $values = [];

        foreach ($records as $name => $model) {
            foreach ($model->translatableFields() as $field) {
                $values[$name][$field] = null;
            }

            foreach ($model->contentTranslations()->whereHas('language', fn ($query) => $query->where('code', $code))->get() as $translation) {
                $values[$name][$translation->field] = $translation->value;
            }
        }

        return $values;
    }

    /**
     * Put the saved translations for the chosen language into the review boxes,
     * then draft the empty ones when an engine was chosen. The choice comes from
     * the browser, so the engine is looked up again here.
     */
    private static function fill(Course $course, string $scope, string $code, mixed $choice, Set $set, bool $overwrite = false): void
    {
        $language = static::languages($course)->firstWhere('code', $code);

        if (! $language) {
            return;
        }

        $records = static::plan($course, $scope)['records'];
        $values = static::current($records, $code);

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

    /**
     * Every language, drafted and saved. Only empty boxes are drafted (unless
     * asked to replace), so a person's own wording is never overwritten. A
     * failure — quota, key, outage — stops the run; what was saved stays.
     */
    private static function draftAndSaveAll(Course $course, string $scope, mixed $choice, bool $overwrite): void
    {
        $engine = static::isEngine($choice) ? TranslateContentAction::engine($choice) : null;

        if (! $engine) {
            return;
        }

        $provider = ['provider' => TranslateContentAction::ENGINES[$choice]['label']];
        $records = static::plan($course, $scope)['records'];
        $saved = 0;
        $languages = 0;

        foreach (static::languages($course) as $language) {
            $result = app(DraftTranslations::class)->handleMany($records, $language, $overwrite ? [] : static::current($records, $language->code), $engine);

            foreach ($result['drafts'] as $name => $fields) {
                foreach ($fields as $field => $text) {
                    $records[$name]->setTranslation($field, $language->code, $text);
                    $saved++;
                }
            }

            if ($result['error']) {
                TranslateContentAction::reportDraft($result, $provider);

                return;
            }

            $languages++;
        }

        Notification::make()
            ->title(__t('admin_common.translate.course.saved_all', ['count' => $languages]))
            ->body(__t('admin_common.translate.draft.drafted_body', ['count' => $saved]))
            ->success()
            ->send();
    }

    /** @return array<int, Section> */
    private static function reviewSections(Course $course, string $scope): array
    {
        $plan = static::plan($course, $scope);

        return array_map(fn (array $section, int $i): Section => Section::make($section['title'])
            ->collapsible()
            ->collapsed($i > 0)
            ->schema(collect($section['names'])
                ->flatMap(fn (string $name): array => collect($plan['records'][$name]->translatableFields())
                    ->map(fn (string $field) => TranslateContentAction::input($plan['records'][$name], "{$name}.{$field}", $field))
                    ->all())
                ->all()), $plan['sections'], array_keys($plan['sections']));
    }
}
