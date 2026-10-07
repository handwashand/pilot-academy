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

    /** A dub only: Descript is generating the translated voice. */
    public const STATUS_DUBBING = 'dubbing';

    public const STATUS_EXPORTING = 'exporting';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    /** Still working: a page can show progress, and nothing new is started. */
    public const IN_FLIGHT = [self::STATUS_PENDING, self::STATUS_TRANSLATING, self::STATUS_DUBBING, self::STATUS_EXPORTING];

    /**
     * Browsers play captions only as WebVTT; Descript exports SRT. The two
     * differ in a header line and in the comma before the milliseconds.
     */
    public static function toVtt(string $subtitles): string
    {
        $subtitles = trim(str_replace(["\r\n", "\r"], "\n", ltrim($subtitles, "\xEF\xBB\xBF")));

        // Descript's published subtitles are already WebVTT; older stored files are SRT.
        if (str_starts_with($subtitles, 'WEBVTT')) {
            return $subtitles."\n";
        }

        return "WEBVTT\n\n".preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $subtitles)."\n";
    }

    /** The words of a caption track, without timings, as one running text. */
    public static function textFromVtt(string $vtt): string
    {
        $cues = [];

        foreach (preg_split('/\n{2,}/', str_replace(["\r\n", "\r"], "\n", trim($vtt))) ?: [] as $block) {
            if (! str_contains($block, '-->')) {
                continue; // the WEBVTT header, a NOTE, a STYLE block
            }

            $lines = array_filter(
                explode("\n", $block),
                fn (string $line): bool => $line !== '' && ! str_contains($line, '-->') && ! preg_match('/^\d+$/', $line),
            );

            $cues[] = trim(preg_replace('/\s+/u', ' ', implode(' ', $lines)));
        }

        return trim(implode(' ', array_filter($cues)));
    }

    /** Finished subtitle files for one uploaded video of a lesson. */
    public function scopeCaptionsFor(Builder $query, Lesson $lesson, string $videoPath): Builder
    {
        return $query
            ->where('lesson_id', $lesson->id)
            ->where('status', self::STATUS_DONE)
            ->whereNotNull('subtitle_path')
            ->whereHas('descriptImport', fn (Builder $import) => $import->where('video_path', $videoPath));
    }

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

    /** Wanted as a voice: the translated composition is dubbed and its file kept. */
    public function isDub(): bool
    {
        return $this->kind === self::KIND_DUB;
    }

    /** Finished dubbed files for one uploaded video of a lesson. */
    public function scopeDubsFor(Builder $query, Lesson $lesson, string $videoPath): Builder
    {
        return $query
            ->where('lesson_id', $lesson->id)
            ->where('kind', self::KIND_DUB)
            ->where('status', self::STATUS_DONE)
            ->whereNotNull('dub_path')
            ->whereHas('descriptImport', fn (Builder $import) => $import->where('video_path', $videoPath));
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
