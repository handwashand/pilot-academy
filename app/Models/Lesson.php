<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
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
        'quiz_time_limit_minutes',
        'quiz_max_attempts',
        'duration_minutes',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function mediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class);
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
