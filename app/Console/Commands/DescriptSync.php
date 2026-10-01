<?php

namespace App\Console\Commands;

use App\Actions\TranslateLessonVideo;
use App\Models\VideoTranslation;
use Illuminate\Console\Command;

/**
 * Moves every Descript translation still in progress along by a step.
 *
 * The academy runs no queue worker, so Descript jobs advance when someone
 * opens the lesson and checks — or when this runs. Safe to run as often as
 * you like: a done translation is never sent again, and a running one is only
 * asked how it is getting on.
 *
 *     php artisan descript:sync
 */
class DescriptSync extends Command
{
    protected $signature = 'descript:sync';

    protected $description = 'Check on Descript video translations in progress and save any that have finished';

    public function handle(TranslateLessonVideo $translator): int
    {
        if (! $translator->enabled()) {
            $this->line('Descript is switched off or has no token; nothing to do.');

            return self::SUCCESS;
        }

        $looked = $translator->advanceAll();

        $counts = VideoTranslation::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $this->info(sprintf(
            'Checked %d in progress. Now: %d done, %d still working, %d failed.',
            $looked,
            (int) ($counts[VideoTranslation::STATUS_DONE] ?? 0),
            collect(VideoTranslation::IN_FLIGHT)->sum(fn (string $status): int => (int) ($counts[$status] ?? 0)),
            (int) ($counts[VideoTranslation::STATUS_FAILED] ?? 0),
        ));

        return self::SUCCESS;
    }
}
