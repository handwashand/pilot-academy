<?php

namespace App\Models;

use App\Actions\NotifyContentOwners;
use App\Models\Concerns\HasContentTranslations;
use App\Models\Concerns\HasDuration;
use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    use HasContentTranslations, HasDuration, HasPublishStatus;

    protected array $translatable = ['title', 'summary', 'content', 'transcript'];

    protected $fillable = [
        'course_id',
        'title',
        'slug',
        'summary',
        'image_path',
        'media_item_id',
        'youtube_url',
        'video_path',
        'video_sources',
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
        'video_sources' => 'array',
    ];

    /**
     * Lessons are published as they are written — the course they belong to is
     * the gate that decides when students see any of it.
     */
    protected $attributes = [
        'status' => self::STATUS_PUBLISHED,
    ];

    /**
     * The home course: the one that owns the lesson and decides who may edit
     * it. The lesson can be in other courses too — see courses().
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Every course this lesson is in, its home course included. */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)
            ->using(CourseLesson::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /** Tell the owners of every course this lesson is in to look again. */
    public static function notifyOwnersOf(mixed $lessonId): void
    {
        if (! $lessonId) {
            return;
        }

        CourseLesson::query()->where('lesson_id', $lessonId)->pluck('course_id')
            ->each(fn ($courseId) => NotifyContentOwners::afterRequest($courseId));
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
        $videoPath = $this->video_path;

        return $videoPath
            ? Storage::disk('public')->url($videoPath)
            : null;
    }

    public function getVideoPathAttribute(?string $value): ?string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        foreach ($this->videoEntries() as $entry) {
            if (($entry['type'] ?? 'upload') === 'upload' && filled($entry['video_path'] ?? null)) {
                return $entry['video_path'];
            }
        }

        return null;
    }

    public function getYoutubeUrlAttribute(?string $value): ?string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        foreach ($this->videoEntries() as $entry) {
            if (($entry['type'] ?? 'youtube') === 'youtube' && filled($entry['youtube_url'] ?? null)) {
                return $entry['youtube_url'];
            }
        }

        return null;
    }

    public function videoEntries(): array
    {
        $entries = $this->video_sources ?? [];

        if (! is_array($entries) || $entries === []) {
            $entries = [];

            if (filled($this->getRawOriginal('youtube_url') ?? $this->attributes['youtube_url'] ?? null)) {
                $entries[] = ['type' => 'youtube', 'youtube_url' => $this->getRawOriginal('youtube_url') ?? $this->attributes['youtube_url'] ?? null];
            }

            if (filled($this->getRawOriginal('video_path') ?? $this->attributes['video_path'] ?? null)) {
                $entries[] = ['type' => 'upload', 'video_path' => $this->getRawOriginal('video_path') ?? $this->attributes['video_path'] ?? null];
            }
        }

        return array_values(array_filter($entries, fn (mixed $entry): bool => is_array($entry) && (filled($entry['youtube_url'] ?? null) || filled($entry['video_path'] ?? null))));
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

    /**
     * Knowledge-check attempts this student has left, or null for unlimited.
     * Timed-out attempts count as used; one still in progress does not.
     */
    public function quizAttemptsLeftFor(User $user): ?int
    {
        $allowed = $this->quizAttemptsAllowedFor($user);

        if ($allowed === null) {
            return null;
        }

        $used = QuizAttempt::where('user_id', $user->id)
            ->where('lesson_id', $this->id)
            ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED, QuizAttempt::STATUS_EXPIRED])
            ->count();

        return max(0, $allowed - $used);
    }

    /** Max attempts for this student, including extra attempts an admin granted. */
    public function quizAttemptsAllowedFor(User $user): ?int
    {
        if (! $this->quiz_max_attempts) {
            return null;
        }

        return $this->quiz_max_attempts + AttemptGrant::where('user_id', $user->id)
            ->where('lesson_id', $this->id)
            ->count();
    }

    /** Tell the owners if a change leaves the course broken — see Course::booted(). */
    protected static function booted(): void
    {
        static::saved(function (Lesson $lesson): void {
            // A lesson is always in its home course.
            if ($lesson->course_id && ! $lesson->courses()->whereKey($lesson->course_id)->exists()) {
                $lesson->courses()->attach($lesson->course_id, ['sort_order' => (int) $lesson->sort_order]);
            }

            NotifyContentOwners::afterRequest($lesson->course_id);
            static::notifyOwnersOf($lesson->id);

            if ($lesson->wasChanged('course_id')) {
                NotifyContentOwners::afterRequest($lesson->getPrevious()['course_id'] ?? null);
            }
        });

        // Before, not after: the course links go with the lesson.
        static::deleting(fn (Lesson $lesson) => static::notifyOwnersOf($lesson->id));
    }
}
