<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'level',
        'audience',
        'thumbnail',
        'duration_minutes',
        'is_published',
        'sort_order',
        'final_quiz_enabled',
        'pass_percent',
        'questions_per_attempt',
        'final_quiz_max_attempts',
        'certificate_template',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'final_quiz_enabled' => 'boolean',
    ];

    /** Who the course is for (used for the audience badge). */
    public const AUDIENCES = [
        'all' => 'Everyone',
        'sales' => 'Sales',
        'technical' => 'Technical',
        'support' => 'Support',
    ];

    public function getAudienceLabelAttribute(): ?string
    {
        return $this->audience ? (self::AUDIENCES[$this->audience] ?? $this->audience) : null;
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('is_published', true);
    }

    /** Questions that make up this course's final quiz bank. */
    public function finalQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'course_final_questions')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('course_final_questions.sort_order');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Has the user completed every published lesson in this course?
     * This unlocks the final quiz. Anonymous visitors are never "completed".
     */
    public function isCompletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $publishedIds = $this->publishedLessons()->pluck('lessons.id')->all();

        if (empty($publishedIds)) {
            return false;
        }

        $completedIds = $user->completedLessons()->pluck('lessons.id')->all();

        return empty(array_diff($publishedIds, $completedIds));
    }
}
