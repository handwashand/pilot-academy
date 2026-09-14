<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One extra attempt at one quiz for one student, given by an admin.
 *
 * `course_id` set means the course's final quiz; `lesson_id` set means that
 * lesson's knowledge check. Each grant adds one attempt on top of the quiz's
 * Max attempts, for this student only.
 */
class AttemptGrant extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'granted_by',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
