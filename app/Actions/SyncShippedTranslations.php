<?php

namespace App\Actions;

use App\Models\Language;
use App\Models\Translation;
use App\Services\Translator;

/**
 * Makes every shipped line findable on the Translations page.
 *
 * Adds a row per shipped key per language, with no value. A row without a
 * value changes nothing: the Translator skips it and students keep seeing the
 * shipped text until someone writes a correction. Existing rows are never
 * touched, so a correction survives every run and every deploy.
 */
class SyncShippedTranslations
{
    public function handle(): int
    {
        $translator = app(Translator::class);
        $keys = array_keys($translator->shipped('en') + $translator->shipped($translator->defaultCode()));
        $languageIds = Language::query()->pluck('id');

        if ($keys === [] || $languageIds->isEmpty()) {
            return 0;
        }

        $existing = Translation::query()
            ->whereIn('key', $keys)
            ->get(['key', 'language_id'])
            ->mapWithKeys(fn (Translation $row): array => [$row->key.'|'.$row->language_id => true]);

        $now = now();
        $rows = [];

        foreach ($languageIds as $languageId) {
            foreach ($keys as $key) {
                if (! $existing->has($key.'|'.$languageId)) {
                    $rows[] = [
                        'key' => $key,
                        'language_id' => $languageId,
                        'value' => null,
                        'module' => str($key)->before('.')->value(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Translation::query()->insertOrIgnore($chunk);
        }

        return count($rows);
    }
}
