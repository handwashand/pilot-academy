<?php

namespace App\Filament\Actions;

use App\Actions\DraftTranslations;
use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * "Translate quiz" — a lesson's knowledge check, or a course's final-quiz
 * bank, into one language, in one window. Same two-step shape as
 * TranslateCourseAction (choose language/engine, then review) and the same
 * underlying mechanics (HasContentTranslations::setTranslation() per field,
 * App\Actions\DraftTranslations::handleMany() for AI drafts) — one level
 * deeper than a course and its lessons: each Question is its own record
 * (translatable = ['prompt']), and each of its Option rows is another
 * (translatable = ['text']). The generic plumbing does not care that these
 * are nested under a question rather than scalar fields on one record, so a
 * flat array<string, Model> keyed "question_{id}" / "option_{id}" is built
 * once (questionRecords()) and fed through the same save/draft/existing-
 * count shape TranslateCourseAction already uses — only reviewSections()
 * groups the flat list back up, an option's box nested under its question's
 * Section, so twenty short boxes do not read as twenty unrelated rows.
 *
 * Two entry points because a Question has two independent homes
 * (App\Models\Question's own docblock): a lesson's own knowledge check
 * (forLesson()) and a course's final-quiz bank (forCourse($course)). Each
 * resolves its own "current record" the way its screen actually provides
 * one — forLesson()'s own closures take Filament's injected Lesson $record
 * the way TranslateContentAction's do; forCourse() has no such record (a
 * RelationManager header action has no single bound record) and instead
 * closes over the $course it is handed, the same way that relation
 * manager's own addAllLessonQuestions action already captures
 * $this->getOwnerRecord() — then both call the same context-free static
 * helpers below with whatever they resolved.
 */
class TranslateQuestionsAction
{
    private const BY_HAND = 'manual';

    public static function forLesson(): Action
    {
        return Action::make('translateQuestions')
            ->label(fn (): string => __t('admin_common.translate.questions.button'))
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_common.translate.questions.heading'))
            ->modalWidth('6xl')
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.submit'))
            ->visible(fn (Lesson $record): bool => $record->questions->isNotEmpty() && static::languages($record->contentLanguageCode())->isNotEmpty())
            ->steps(fn (Lesson $record): array => static::steps($record->contentLanguageCode(), static::questions($record->questions)))
            ->action(fn (Lesson $record, array $data) => static::save(static::questions($record->questions), $data));
    }

    public static function forCourse(Course $course): Action
    {
        return Action::make('translateFinalQuestions')
            ->label(fn (): string => __t('admin_common.translate.questions.button'))
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->modalHeading(fn (): string => __t('admin_common.translate.questions.heading'))
            ->modalWidth('6xl')
            ->modalSubmitActionLabel(fn (): string => __t('admin_common.translate.submit'))
            ->visible(fn (): bool => $course->finalQuestions->isNotEmpty() && static::languages($course->contentLanguageCode())->isNotEmpty())
            ->steps(fn (): array => static::steps($course->contentLanguageCode(), static::questions($course->finalQuestions)))
            ->action(fn (array $data) => static::save(static::questions($course->finalQuestions), $data));
    }

    /**
     * Each question's options, loaded once rather than per question — a
     * lesson or a course's final-quiz bank is a handful of questions, not
     * hundreds, but there is no reason to query options one question at a
     * time when they are all needed together here.
     *
     * @param  EloquentCollection<int, Question>  $questionList
     * @return EloquentCollection<int, Question>
     */
    private static function questions(EloquentCollection $questionList): EloquentCollection
    {
        return $questionList->loadMissing('options');
    }

    /**
     * The two-step wizard body, shared by both entry points once each has
     * resolved its own "written in" language and its own list of questions.
     *
     * @param  EloquentCollection<int, Question>  $questionList
     * @return array<int, Step>
     */
    private static function steps(string $ownerLanguage, EloquentCollection $questionList): array
    {
        $records = static::questionRecords($questionList);

        return [
            Step::make(__t('admin_common.translate.course.step_choose'))
                ->schema([
                    Select::make('language')
                        ->label(fn (): string => __t('admin_common.translate.course.language'))
                        ->options(static::languages($ownerLanguage)->mapWithKeys(fn (Language $language): array => [$language->code => $language->native_name])->all())
                        ->required()
                        ->live()
                        ->helperText(fn (Get $get): ?string => ($count = static::existingCount($records, $get('language'))) > 0
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

                    Checkbox::make('overwrite')
                        ->label(fn (): string => __t('admin_common.translate.draft.overwrite'))
                        ->visible(fn (Get $get): bool => static::isEngine($get('engine')) && static::existingCount($records, $get('language')) > 0),
                ])
                ->afterValidation(function (Get $get, Set $set) use ($records, $ownerLanguage): void {
                    static::fill($records, $ownerLanguage, (string) $get('language'), $get('engine'), $set, (bool) $get('overwrite'));
                }),

            Step::make(__t('admin_common.translate.course.step_review'))
                ->schema(fn (): array => static::reviewSections($questionList, $records)),
        ];
    }

    /**
     * @param  EloquentCollection<int, Question>  $questionList
     * @param  array<string, mixed>  $data
     */
    private static function save(EloquentCollection $questionList, array $data): void
    {
        $language = (string) ($data['language'] ?? '');

        if ($language === '' || ! Language::active()->where('code', $language)->exists()) {
            return;
        }

        foreach (static::questionRecords($questionList) as $recordName => $model) {
            foreach ($model->translatableFields() as $field) {
                $model->setTranslation($field, $language, $data[$recordName][$field] ?? null);
            }
        }

        Notification::make()->title(__t('admin_common.translate.questions.saved'))->success()->send();
    }

    /** Every active language except the one this quiz is written in. */
    private static function languages(string $ownCode): Collection
    {
        return app(Translator::class)->activeLanguages()
            ->reject(fn (Language $language): bool => $language->code === $ownCode)
            ->values();
    }

    /**
     * Every question, and every one of its options, by the name their boxes
     * use in the form — question_{id} then its own option_{id} rows, in
     * display order.
     *
     * @param  EloquentCollection<int, Question>  $questionList
     * @return array<string, Model>
     */
    private static function questionRecords(EloquentCollection $questionList): array
    {
        $records = [];

        foreach ($questionList as $question) {
            $records["question_{$question->id}"] = $question;

            foreach ($question->options as $option) {
                $records["option_{$option->id}"] = $option;
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

    /** @param  array<string, Model>  $records */
    private static function existingCount(array $records, mixed $code): int
    {
        if (! is_string($code) || $code === '') {
            return 0;
        }

        $count = 0;

        foreach ($records as $model) {
            $count += $model->contentTranslations()
                ->whereHas('language', fn ($query) => $query->where('code', $code))
                ->whereNotNull('value')
                ->count();
        }

        return $count;
    }

    /** @param  array<string, Model>  $records */
    private static function fill(array $records, string $sourceCode, string $code, mixed $choice, Set $set, bool $overwrite = false): void
    {
        $language = static::languages($sourceCode)->firstWhere('code', $code);

        if (! $language) {
            return;
        }

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
     * One collapsible Section per question, its own prompt box first and
     * each of its options' boxes nested right after it — grouped for
     * reading, though every box still saves through the same flat $records
     * map regardless of this nesting.
     *
     * @param  EloquentCollection<int, Question>  $questionList
     * @param  array<string, Model>  $records
     * @return array<int, Section>
     */
    private static function reviewSections(EloquentCollection $questionList, array $records): array
    {
        $sections = [];

        foreach ($questionList as $question) {
            $name = "question_{$question->id}";
            $model = $records[$name];

            $schema = collect($model->translatableFields())
                ->map(fn (string $field) => TranslateContentAction::input($model, "{$name}.{$field}", $field))
                ->all();

            foreach ($question->options as $option) {
                $optionName = "option_{$option->id}";
                $optionModel = $records[$optionName];

                $schema = [
                    ...$schema,
                    ...collect($optionModel->translatableFields())
                        ->map(fn (string $field) => TranslateContentAction::input($optionModel, "{$optionName}.{$field}", $field))
                        ->all(),
                ];
            }

            $sections[] = Section::make(Str::limit($question->prompt, 70))
                ->collapsible()
                ->collapsed()
                ->schema($schema);
        }

        return $sections;
    }
}
