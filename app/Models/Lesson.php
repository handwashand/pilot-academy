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
        'transcript',
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
        return static::youtubeIdFrom($this->youtube_url);
    }

    /**
     * The id of the single YouTube video a pasted link points at, or null.
     *
     * The one definition of "a playable YouTube link": the lesson page embeds
     * from it, the lesson form refuses what it rejects, and the dashboard
     * flags stored links it cannot read. A link that does not parse used to
     * save without complaint and leave the lesson with no video at all —
     * playlists, channels, Vimeo, and even `youtube.com/live/…` videos.
     *
     * Anchored, and the id must end at a boundary, so `…/ID" onload="…` or an
     * id with extra characters is refused rather than quietly truncated.
     */
    public static function youtubeIdFrom(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $id = '([A-Za-z0-9_-]{11})(?=[?&/#]|$)';

        $patterns = [
            // youtu.be/ID
            '~^(?:https?://)?(?:www\.)?youtu\.be/'.$id.'~',
            // youtube.com/watch?v=ID, including ?feature=share&v=ID
            '~^(?:https?://)?(?:www\.|m\.)?youtube(?:-nocookie)?\.com/watch\?(?:[^#\s]*&)?v='.$id.'~',
            // youtube.com/embed/ID · /shorts/ID · /live/ID · /v/ID
            '~^(?:https?://)?(?:www\.|m\.)?youtube(?:-nocookie)?\.com/(?:embed|shorts|live|v)/'.$id.'~',
            // A bare id, as older rows may store.
            '~^'.$id.'~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m) === 1) {
                return $m[1];
            }
        }

        return null;
    }
}
