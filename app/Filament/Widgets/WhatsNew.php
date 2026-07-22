<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class WhatsNew extends Widget
{
    protected static ?int $sort = 2;

    // Cheap (reads one small file) — render inline instead of deferring.
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.whats-new';

    /**
     * The newest changelog entries, as ['title' => ..., 'summary' => ...].
     * Reads the same docs/CHANGELOG.md the Changelog page renders.
     */
    public function getEntries(int $limit = 3): array
    {
        $path = base_path('docs/CHANGELOG.md');

        if (! is_file($path)) {
            return [];
        }

        $entries = [];
        $current = null;

        foreach (preg_split('/\R/', file_get_contents($path)) as $line) {
            if (str_starts_with($line, '### ')) {
                if ($current) {
                    $entries[] = $current;
                }

                $current = ['title' => trim(substr($line, 4)), 'summary' => ''];

                continue;
            }

            if ($current === null) {
                continue;
            }

            $line = trim($line);

            // Blank lines, other headings, rules and the closing note end nothing
            // useful — skip them and keep collecting the entry's prose.
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '---') || str_starts_with($line, '*')) {
                continue;
            }

            $current['summary'] .= ($current['summary'] ? ' ' : '').ltrim($line, '- ');
        }

        if ($current) {
            $entries[] = $current;
        }

        return collect($entries)
            ->take($limit)
            ->map(fn (array $entry): array => [
                'title' => $entry['title'],
                'summary' => Str::limit(str_replace(['**', '`'], '', $entry['summary']), 180),
            ])
            ->all();
    }
}
