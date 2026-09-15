<?php

namespace App\Models;

use App\Services\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $fillable = [
        'key',
        'language_id',
        'value',
        'module',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (Translation $translation): void {
            $translation->module = str($translation->key)->before('.')->value() ?: $translation->key;
        });

        static::updated(function (Translation $translation): void {
            if (! $translation->wasChanged('value')) {
                return;
            }

            TranslationRevision::create([
                'translation_id' => $translation->id,
                'old_value' => $translation->getOriginal('value'),
                'new_value' => $translation->value,
                'changed_by' => $translation->updated_by,
            ]);
        });

        static::saved(fn (Translation $translation): bool => app(Translator::class)->clearBundleCache($translation->language?->code));
        static::deleted(fn (Translation $translation): bool => app(Translator::class)->clearBundleCache($translation->language?->code));
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
