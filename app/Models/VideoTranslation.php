<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One lesson video in one language, as Descript translated it.
 *
 * The row is the cache. Once it is done, its transcript and subtitle file are
 * the app's own copy and Descript is never asked for that language again. A
 * failed one is retried only when an editor asks.
 */
class VideoTranslation extends Model
{
    public const KIND_TRANSCRIPT = 'transcript';

    public const KIND_DUB = 'dub';

    public const STATUS_PENDING = 'pending';

    public const STATUS_TRANSLATING = 'translating';

    public const STATUS_EXPORTING = 'exporting';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /** Still working: a page can show progress, and nothing new is started. */
    public const IN_FLIGHT = [self::STATUS_PENDING, self::STATUS_TRANSLATING, self::STATUS_EXPORTING];

    protected $fillable = [
        'descript_import_id',
        'lesson_id',
        'language',
        'kind',
        'status',
        'job_id',
        'composition_id',
        'compositions_before',
        'agent_response',
        'transcript',
        'subtitle_path',
        'dub_path',
        'ai_credits_used',
        'error',
        'requested_by',
        'completed_at',
    ];

    protected $casts = [
        'compositions_before' => 'array',
        'ai_credits_used' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function descriptImport(): BelongsTo
    {
        return $this->belongsTo(DescriptImport::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isInFlight(): bool
    {
        return in_array($this->status, self::IN_FLIGHT, true);
    }

    public function scopeInFlight(Builder $query): Builder
    {
        return $query->whereIn('status', self::IN_FLIGHT);
    }
}
