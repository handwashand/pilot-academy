<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasPublishStatus;

    protected $fillable = [
        'product_id',
        'title',
        'slug',
        'description',
        'level',
        'audience',
        'thumbnail',
        'duration_minutes',
        'status',
        'sort_order',
        'final_quiz_enabled',
        'pass_percent',
        'questions_per_attempt',
        'final_quiz_max_attempts',
        'certificate_template',
    ];

    protected $casts = [
        'final_quiz_enabled' => 'boolean',
    ];

    /** A course is only ever visible once someone publishes it deliberately. */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
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

    /** A course with no published lesson would open empty, so it cannot go live. */
    public function canBePublished(): bool
    {
        return $this->publishedLessons()->exists();
    }

    /**
     * Live, but a student opening it finds nothing. Publishing guards against
     * this, so it means every lesson was unpublished afterwards.
     */
    public function scopePublishedButEmpty(Builder $query): Builder
    {
        return $query->published()->whereDoesntHave('publishedLessons');
    }

    /**
     * The final quiz is switched on with nothing to ask. Students who finish
     * every lesson reach a dead button, and nothing tells the admin.
     */
    public function scopeFinalQuizWithoutQuestions(Builder $query): Builder
    {
        return $query->where('final_quiz_enabled', true)->whereDoesntHave('finalQuestions');
    }

    /**
     * May this visitor open the course pages? Students only ever get published
     * courses; whoever manages the course — an admin, or the creator who owns
     * its product — can preview it before it goes live.
     */
    public function isVisibleTo(?User $user): bool
    {
        return $this->isPublished() || (bool) $user?->canManageCourse($this);
    }

    /** The product this course teaches (GARM, PTM, …). */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function publishedLessons(): HasMany
    {
        return $this->lessons()->published();
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

    /**
     * May this user open the final quiz? Students must finish every published
     * lesson; admins can always open it (to preview or test the certificate).
     */
    public function finalQuizUnlockedFor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->canManageCourse($this) || $this->isCompletedBy($user);
    }
}
