<?php

namespace App\Models;

use App\Models\Concerns\HasContentTranslations;
use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A live session partners can join, and its recording afterwards. Upcoming and
 * past are the same record: the start time decides which list it appears in.
 */
class Webinar extends Model
{
    use HasContentTranslations, HasPublishStatus;

    /** @var array<int, string> */
    protected array $translatable = ['title', 'summary', 'description'];

    protected $fillable = [
        'product_id',
        'cover_media_item_id',
        'title',
        'language',
        'slug',
        'summary',
        'description',
        'presenter',
        'starts_at',
        'duration_minutes',
        'join_url',
        'recording_url',
        'status',
        'published_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function coverMediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'cover_media_item_id');
    }

    /** Still to come: the session has not started yet. */
    public function isUpcoming(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    public function hasRecording(): bool
    {
        return filled($this->recording_url);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereNotNull('starts_at')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at');
    }

    /** Finished sessions, newest first — and anything with no date yet. */
    public function scopePast(Builder $query): Builder
    {
        return $query->where(fn (Builder $done): Builder => $done
            ->whereNull('starts_at')
            ->orWhere('starts_at', '<', now()))
            ->orderByDesc('starts_at');
    }

    /**
     * A session nobody can join and nobody can watch helps no one, so one of
     * the two links is required before it goes live.
     */
    public function canBePublished(): bool
    {
        return filled($this->title)
            && filled($this->slug)
            && $this->starts_at !== null
            && (filled($this->join_url) || filled($this->recording_url));
    }

    public function publish(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /** The start time in the reader's language, always with the zone named. */
    public function whenLabel(): string
    {
        if ($this->starts_at === null) {
            return __t('academy.webinars.no_date');
        }

        return $this->starts_at->locale(app()->getLocale())->isoFormat('LLL').' UTC';
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->isPublished() || (bool) $user?->canManageWebinar($this);
    }
}
