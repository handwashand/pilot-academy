<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's verdict on a course they finished: a thumb, and optionally a
 * sentence. Staff-only — it is never shown to other students.
 */
class CourseFeedback extends Model
{
    /** Laravel would pluralise this to "course_feedbacks". */
    protected $table = 'course_feedback';

    protected $fillable = [
        'user_id',
        'course_id',
        'is_positive',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'is_positive' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
