<?php

namespace App\Models;

use App\Services\Translator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_active',
        'is_default',
        'direction',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Language $language): void {
            if ($language->is_default) {
                Language::query()
                    ->when($language->exists, fn ($query) => $query->whereKeyNot($language->getKey()))
                    ->update(['is_default' => false]);
            }
        });

        static::saved(fn (): bool => app(Translator::class)->clearLanguageCaches());
        static::deleted(fn (): bool => app(Translator::class)->clearLanguageCaches());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
