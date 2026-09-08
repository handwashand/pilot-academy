<?php

namespace App\Models\Concerns;

/**
 * Running time, shown to students so they can tell whether they have time to
 * start. Duration is optional content: an admin who leaves it blank gets no
 * label at all rather than a misleading "0 min".
 */
trait HasDuration
{
    /** Minutes of content, or null when nobody has filled it in. */
    public function durationMinutes(): ?int
    {
        $minutes = (int) ($this->duration_minutes ?? 0);

        return $minutes > 0 ? $minutes : null;
    }

    /** "45 min", "1 hr", "1 hr 20 min" — or null when there is no duration. */
    public function durationLabel(): ?string
    {
        return static::formatMinutes($this->durationMinutes());
    }

    /** Format a raw minute count the same way everywhere. */
    public static function formatMinutes(?int $minutes): ?string
    {
        if (! $minutes || $minutes < 1) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return match (true) {
            $hours === 0 => "{$minutes} min",
            $rest === 0 => "{$hours} hr",
            default => "{$hours} hr {$rest} min",
        };
    }
}
