<?php

namespace App\Models;

use App\Models\Concerns\HasContentTranslations;
use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A video on the Tutorials page that belongs to no course — a short how-to an
 * admin adds directly. Lesson videos reach the same page through their course;
 * these stand on their own.
 */
class Tutorial extends Model
{
    use HasContentTranslations, HasPublishStatus;

    public const TYPE_YOUTUBE = 'youtube';

    public const TYPE_UPLOAD = 'upload';

    /** @var array<int, string> */
    protected array $translatable = ['title', 'summary'];

    protected $fillable = [
        'product_id',
        'title',
        'language',
        'slug',
        'summary',
        'type',
        'youtube_url',
        'video_path',
        'duration_minutes',
        'sort_order',
        'status',
        'published_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'type' => self::TYPE_YOUTUBE,
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** What the page has to render: a link, a file, or nothing yet. */
    public function videoType(): ?string
    {
        if ($this->type === self::TYPE_UPLOAD && filled($this->video_path)) {
            return self::TYPE_UPLOAD;
        }

        return filled($this->youtube_url) ? self::TYPE_YOUTUBE : null;
    }

    public function hasVideo(): bool
    {
        return $this->videoType() !== null;
    }

    /** The YouTube id, read the same way a lesson reads one. */
    public function youtubeId(): ?string
    {
        return Lesson::youtubeIdFrom($this->youtube_url);
    }

    public function videoUrl(): ?string
    {
        return filled($this->video_path)
            ? Storage::disk('public')->url($this->video_path)
            : null;
    }

    /** A tutorial with no video is an empty page, so it cannot go live. */
    public function canBePublished(): bool
    {
        return filled($this->title)
            && filled($this->slug)
            && $this->hasVideo()
            && ($this->videoType() !== self::TYPE_YOUTUBE || $this->youtubeId() !== null);
    }

    public function publish(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->isPublished() || (bool) $user?->canManageTutorial($this);
    }
}
