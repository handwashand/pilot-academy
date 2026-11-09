<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Which provider and model wrote a saved piece of generated content. */
class AiContentRevision extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'user_id',
        'provider',
        'model',
        'purpose',
        'ai_usage_id',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usage(): BelongsTo
    {
        return $this->belongsTo(AiUsage::class, 'ai_usage_id');
    }
}
