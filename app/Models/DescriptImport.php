<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One uploaded lesson video, sent to Descript once. Every language is made
 * from this one Descript project, so the video is never uploaded twice.
 */
class DescriptImport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IMPORTING = 'importing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'lesson_id',
        'video_path',
        'file_size',
        'source_language',
        'status',
        'project_id',
        'job_id',
        'media_seconds_used',
        'error',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'media_seconds_used' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(VideoTranslation::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }
}
