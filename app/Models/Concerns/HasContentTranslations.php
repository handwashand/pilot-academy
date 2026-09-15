<?php

namespace App\Models\Concerns;

use App\Models\ContentTranslation;
use App\Models\Language;
use App\Services\Translator;
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
