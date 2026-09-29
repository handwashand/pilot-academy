<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class CaseStudy extends Model
{
    use HasPublishStatus;

    public const DIFFICULTY_BEGINNER = 'beginner';
    public const DIFFICULTY_INTERMEDIATE = 'intermediate';
    public const DIFFICULTY_ADVANCED = 'advanced';

    public const DIFFICULTY_LABELS = [
        self::DIFFICULTY_BEGINNER => 'Beginner',
        self::DIFFICULTY_INTERMEDIATE => 'Intermediate',
        self::DIFFICULTY_ADVANCED => 'Advanced',
    ];

    protected $fillable = [
        'product_id',
        'cover_media_item_id',
        'diagram_media_item_id',
        'title',
        'slug',
        'short_problem',
        'industry',
        'features_used',
        'difficulty',
        'implementation_time',
        'status',
        'sort_order',
        'scenario_problem',
        'desired_outcome',
        'prerequisites',
        'pilot_features',
        'configuration_steps',
        'testing_verification',
        'expected_results',
        'troubleshooting',
        'adaptation',
        'related_lesson_ids',
        'related_links',
        'source_note',
        'performance_claim_note',
        'is_anonymized',
        'is_customer_approved',
        'published_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'difficulty' => self::DIFFICULTY_INTERMEDIATE,
        'is_anonymized' => true,
        'is_customer_approved' => false,
    ];

    protected $casts = [
        'features_used' => 'array',
        'related_lesson_ids' => 'array',
        'related_links' => 'array',
        'is_anonymized' => 'boolean',
        'is_customer_approved' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function difficultyLabels(): array
    {
        return self::DIFFICULTY_LABELS;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function coverMediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'cover_media_item_id');
    }

    public function diagramMediaItem(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'diagram_media_item_id');
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->isPublished() || (bool) $user?->canManageCaseStudy($this);
    }

    public function difficultyLabel(): string
    {
        return self::DIFFICULTY_LABELS[$this->difficulty] ?? ucfirst((string) $this->difficulty);
    }

    public function featureList(): array
    {
        return collect($this->features_used ?? [])
            ->filter(fn ($feature): bool => filled($feature))
            ->map(fn ($feature): string => trim((string) $feature))
            ->unique()
            ->values()
            ->all();
    }

    public function relatedLessons(): Collection
    {
        $ids = collect($this->related_lesson_ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Lesson::published()
            ->whereIn('id', $ids)
            ->with(['courses' => fn ($query) => $query->published()->orderBy('courses.sort_order')])
            ->get()
            ->sortBy(fn (Lesson $lesson): int => $ids->search((int) $lesson->id))
            ->values();
    }

    public function canBePublished(): bool
    {
        return filled($this->title)
            && filled($this->slug)
            && filled($this->short_problem)
            && filled($this->scenario_problem)
            && filled($this->desired_outcome)
            && filled($this->configuration_steps)
            && filled($this->testing_verification)
            && filled($this->source_note);
    }

    public function publish(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update(['status' => self::STATUS_DRAFT]);
    }

    public function scopeMatchingSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.mb_strtolower($term).'%';

        return $query->where(fn (Builder $search): Builder => $search
            ->whereRaw('LOWER(title) LIKE ?', [$like])
            ->orWhereRaw('LOWER(short_problem) LIKE ?', [$like])
            ->orWhereRaw('LOWER(industry) LIKE ?', [$like])
            ->orWhereRaw('LOWER(configuration_steps) LIKE ?', [$like]));
    }

    protected static function booted(): void
    {
        static::saving(function (CaseStudy $caseStudy): void {
            if ($caseStudy->status === self::STATUS_PUBLISHED && ! $caseStudy->published_at) {
                $caseStudy->published_at = now();
            }
        });
    }
}
