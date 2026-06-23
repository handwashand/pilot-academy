<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PASSED = 'passed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_PASSED => 'Passed',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_EXPIRED => 'Time expired',
    ];

    protected $fillable = [
        'user_id',
        'lesson_id',
        'status',
        'started_at',
        'submitted_at',
        'score',
        'total',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
