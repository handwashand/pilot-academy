<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The one row of course-generation defaults. Tokens stay on AiProvider. */
class AiGenerationSetting extends Model
{
    protected $fillable = [
        'default_provider',
        'fallback_enabled',
        'daily_generation_limit',
        'monthly_budget_usd',
    ];

    protected function casts(): array
    {
        return [
            'fallback_enabled' => 'boolean',
            'monthly_budget_usd' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'fallback_enabled' => false,
        ]);
    }
}
