<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The draft → published → archived lifecycle shared by courses and lessons.
 *
 * Nothing reaches students until someone publishes it deliberately, and
 * unpublishing only ever changes this flag — no content is removed.
 */
trait HasPublishStatus
{
    /** Being written — never shown to students. New records start here. */
    public const STATUS_DRAFT = 'draft';

    /** Live on the student site. */
    public const STATUS_PUBLISHED = 'published';

    /** Retired — kept for its history, hidden from students. */
    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    /** The only records students may ever see. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * May this visitor open the pages for this record? Students only ever get
     * published ones; admins can preview drafts before they go live (the same
     * allowance they already have for the final quiz).
     */
    public function isVisibleTo(?User $user): bool
    {
        return $this->isPublished() || (bool) $user?->is_admin;
    }

    /** Overridden where publishing has prerequisites (see Course). */
    public function canBePublished(): bool
    {
        return true;
    }

    public function publish(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHED]);
    }

    /** Back to draft — the record and all of its content stay untouched. */
    public function unpublish(): void
    {
        $this->update(['status' => self::STATUS_DRAFT]);
    }
}
