<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public const TYPE_SINGLE = 'single';

    public const TYPE_MULTIPLE = 'multiple';

    public const TYPE_LABELS = [
        self::TYPE_SINGLE => 'Single choice',
        self::TYPE_MULTIPLE => 'Multiple select',
    ];

    protected $fillable = [
        'lesson_id',
        'prompt',
        'type',
        'sort_order',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Nothing ticked as correct. isAnsweredCorrectly() can then never return
     * true, so the student is stuck on that lesson however they answer.
     */
    public function scopeWithoutCorrectAnswer(Builder $query): Builder
    {
        return $query->whereDoesntHave('options', fn (Builder $option) => $option->where('is_correct', true));
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('sort_order');
    }

    /** Courses that use this question in their final quiz bank. */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_final_questions')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /** Ids of the correct options for this question. */
    public function correctOptionIds(): array
    {
        return $this->options->where('is_correct', true)->pluck('id')->all();
    }

    /**
     * Is this question answered correctly by the given chosen option id(s)?
     * Single choice = the one correct option; multiple select = full-set match.
     */
    public function isAnsweredCorrectly(array $chosenOptionIds): bool
    {
        $correct = array_map('intval', $this->correctOptionIds());
        sort($correct);

        $chosen = array_values(array_unique(array_map('intval', $chosenOptionIds)));
        sort($chosen);

        return ! empty($correct) && $chosen === $correct;
    }
}
