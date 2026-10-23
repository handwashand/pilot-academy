<?php

namespace App\Models\Concerns;

use App\Models\ContentTranslation;
use App\Models\Language;
use App\Services\Translator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait HasContentTranslations
{
    public function contentTranslations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    public function translated(string $field, ?string $code = null): mixed
    {
        $original = $this->getAttribute($field);

        if (! in_array($field, $this->translatable ?? [], true)) {
            return $original;
        }

        $language = app(Translator::class)->activeLanguage($code);

        // Someone reading in the language it was written in gets the original.
        if (! $language || $language->code === $this->contentLanguageCode()) {
            return $original;
        }

        // Loaded up front by the student pages, so a page of titles is not a
        // query per title; otherwise asked for directly.
        $value = $this->relationLoaded('contentTranslations')
            ? $this->contentTranslations->first(
                fn (ContentTranslation $translation): bool => (int) $translation->language_id === (int) $language->id && $translation->field === $field,
            )?->value
            : $this->contentTranslations()
                ->where('language_id', $language->id)
                ->where('field', $field)
                ->value('value');

        return filled($value) ? $value : $original;
    }

    /**
     * The language the record is written in — a French trainer's course is
     * French. Unset means the default language.
     */
    public function contentLanguageCode(): string
    {
        return $this->getAttribute('language') ?: app(Translator::class)->defaultCode();
    }

    /** The fields students see that can be written in other languages. */
    public function translatableFields(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * What to call a field in the Translate box. Overridden where a model
     * already names its own fields elsewhere — see CaseStudy.
     */
    public function translatableFieldLabel(string $field): string
    {
        return __t("admin_common.translate.fields.{$field}");
    }

    public function setTranslation(string $field, string $code, ?string $value): void
    {
        if (! in_array($field, $this->translatable ?? [], true)) {
            return;
        }

        $language = app(Translator::class)->activeLanguage($code);

        // The original is the record itself, not a translation of it.
        if (! $language || $language->code === $this->contentLanguageCode()) {
            return;
        }

        // Whatever was loaded is out of date after this.
        $this->unsetRelation('contentTranslations');

        $query = $this->contentTranslations()
            ->where('language_id', $language->id)
            ->where('field', $field);

        if (blank($value)) {
            $query->delete();

            return;
        }

        $query->updateOrCreate(
            ['language_id' => $language->id, 'field' => $field],
            ['value' => $value, 'updated_by' => Auth::id()],
        );
    }

    /**
     * The language a person reading this now sees it in, when that is not the
     * one it was written in: the admin panel shows and edits that translation
     * (an admin reviewing in Spanish sees Spanish). Null when they read the
     * original.
     */
    public function readingLanguage(): ?Language
    {
        $language = app(Translator::class)->activeLanguage();

        return $language && $language->code !== $this->contentLanguageCode() ? $language : null;
    }

    /**
     * The translatable fields as the reader sees them: the translation where
     * there is one, the original where there is not — what a student reading
     * in that language gets.
     *
     * @return array<string, mixed>
     */
    public function readingValues(): array
    {
        $values = [];

        foreach ($this->translatableFields() as $field) {
            $values[$field] = $this->translated($field);
        }

        return $values;
    }

    /**
     * A form row holding a record's id (a repeater item) as the reader sees
     * it: readingValues() over the stored fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function readingRow(array $data): array
    {
        $record = isset($data['id']) ? static::query()->with('contentTranslations')->find($data['id']) : null;

        return $record?->readingLanguage() ? [...$data, ...$record->readingValues()] : $data;
    }

    /** Whether a form's record is shown in a translation rather than as written. */
    public static function showsTranslation(mixed $record): bool
    {
        return $record instanceof Model
            && method_exists($record, 'readingLanguage')
            && $record->readingLanguage() !== null;
    }

    /**
     * Saves the translatable fields of a form filled in the reader's language
     * as that translation, and hands back the rest for the record itself — so
     * a reviewer reading in Spanish never writes over the English. A field
     * with no translation that still reads as the original is left alone:
     * saving the form must not copy the English in as "Spanish".
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveReadingValues(array $data): array
    {
        $language = $this->readingLanguage();

        if (! $language) {
            return $data;
        }

        $plain = fn (mixed $value): string => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value))));

        foreach ($this->translatableFields() as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            unset($data[$field]);

            $hasTranslation = $this->contentTranslations()
                ->where('language_id', $language->id)
                ->where('field', $field)
                ->exists();

            if (! $hasTranslation && $plain($value) === $plain($this->getAttribute($field))) {
                continue;
            }

            $this->setTranslation($field, $language->code, is_string($value) ? $value : null);
        }

        return $data;
    }

    /**
     * Matches a field as written or in any of its translations, so an admin
     * reading the panel in Spanish finds a course by its Spanish title.
     * LOWER() on both sides: PostgreSQL's LIKE is case-sensitive.
     */
    public function scopeWhereTranslatedLike(Builder $query, string $field, string $search): Builder
    {
        $term = '%'.mb_strtolower($search).'%';

        return $query->where(fn (Builder $query): Builder => $query
            ->whereRaw('LOWER('.$query->getModel()->qualifyColumn($field).') LIKE ?', [$term])
            ->orWhereHas('contentTranslations', fn (Builder $translations): Builder => $translations
                ->where('field', $field)
                ->whereRaw('LOWER(value) LIKE ?', [$term])));
    }

    public function translationCoverage(): array
    {
        $fields = $this->translatable ?? [];
        $total = count($fields);

        return Language::active()
            ->where('code', '!=', $this->contentLanguageCode())
            ->orderBy('position')
            ->get()
            ->mapWithKeys(function (Language $language) use ($fields, $total): array {
                $filled = $this->contentTranslations()
                    ->where('language_id', $language->id)
                    ->whereIn('field', $fields)
                    ->whereNotNull('value')
                    ->count();

                return [$language->code => ['filled' => $filled, 'total' => $total]];
            })
            ->all();
    }
}
