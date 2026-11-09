<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tokens a provider reported for one request. The prompt and the answer are
 * not kept.
 */
class AiUsage extends Model
{
    public const PURPOSE_COURSE = 'course_generation';

    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'purpose',
        'prompt_tokens',
        'completion_tokens',
        'estimated_cost_usd',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost_usd' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
