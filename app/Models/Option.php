<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $fillable = [
        'question_id',
        'text',
        'is_correct',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Unticking the only correct answer breaks a question, so options count
     * too — see Course::booted(). Many options save in one form; the check
     * itself still runs once per course, after the request.
     */
    protected static function booted(): void
    {
        $check = function (Option $option): void {
            // Every course the lesson is in — it can be shared.
            Lesson::notifyOwnersOf(Question::whereKey($option->question_id)->value('lesson_id'));
        };

        static::saved($check);
        static::deleted($check);
    }
}
