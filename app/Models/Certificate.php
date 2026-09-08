<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'quiz_attempt_id',
        'number',
        'name',
        'score_percent',
        'issued_at',
        'revoked_at',
        'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }

    public function statusLabel(): string
    {
        return $this->isValid() ? 'Valid' : 'Revoked';
    }

    /** Public verification URL for this certificate. */
    public function verifyUrl(): string
    {
        return route('certificates.verify', $this->number);
    }
}
