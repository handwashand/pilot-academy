<?php

namespace App\Models;

use App\Models\Concerns\HasDuration;
use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    use HasDuration, HasPublishStatus;

    protected $fillable = [
        'course_id',
        'title',
        'slug',
        'summary',
        'image_path',
        'media_item_id',
        'youtube_url',
        'video_path',
        'content',
        'doc_links',
        'quiz_time_limit_minutes',
        'quiz_max_attempts',
        'duration_minutes',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'doc_links' => 'array',
    ];

    /**
     * Lessons are published as they are written — the course they belong to is
     * the gate that decides when students see any of it.
     */
    protected $attributes = [
        'status' => self::STATUS_PUBLISHED,
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Students only ever get published lessons; whoever manages the parent
     * course can preview a draft one.
     */
    public function isVisibleTo(?User $user): bool
    {
        return $this->isPublished() || (bool) $user?->canManageCourse($this->course);
    }

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
    }

    /** No knowledge check, so a student can never mark the lesson finished. */
    public function scopeWithoutQuestions(Builder $query): Builder
    {
        return $query->whereDoesntHave('questions');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    /** Does the quiz enforce a time limit and/or a limited number of attempts? */
    public function hasQuizLimits(): bool
    {
        return ! empty($this->quiz_time_limit_minutes) || ! empty($this->quiz_max_attempts);
    }

    /**
     * Cover image URL — from the media library if selected, otherwise the
     * legacy per-lesson uploaded file.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->media_item_id && $this->mediaItem) {
            return $this->mediaItem->url;
        }

        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    /**
     * Public URL of an uploaded video file, if one was uploaded.
     */
    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path
            ? Storage::disk('public')->url($this->video_path)
            : null;
    }

    /**
     * Extract the YouTube video id from a full / short / embed URL.
     */
    public function getYoutubeIdAttribute(): ?string
    {
        if (empty($this->youtube_url)) {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $this->youtube_url, $m)) {
            return $m[1];
        }

        // Already just an id?
        if (preg_match('~^[\w-]{11}$~', $this->youtube_url)) {
            return $this->youtube_url;
        }

        return null;
    }
}
