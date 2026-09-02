<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How far a student got into a lesson video. Kept off the lesson_user pivot on
 * purpose — see the migration for why that would corrupt completion counts.
 */
class VideoPosition extends Model
{
    protected $fillable = [
        'user_id',
        'lesson_id',
        'seconds',
    ];

    protected function casts(): array
    {
        return [
            'seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
