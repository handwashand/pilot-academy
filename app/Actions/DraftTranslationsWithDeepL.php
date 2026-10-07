<?php

namespace App\Actions;

use App\Models\Language;
use App\Services\DeepL\DeepLClient;
use App\Services\DeepL\DeepLException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Drafts the translations a record is still missing, and nothing more.
 *
 * It only returns text. The Translate dialog puts it into its form for an
 * editor to read, change and save — nothing is stored here, and a box that
 * already holds text is never asked for again, so nothing is paid for twice or
 * written over.
 */
class DraftTranslationsWithDeepL
{
    /** The only field the Translate dialog edits as HTML. */
    private const HTML_FIELDS = ['content'];

    public function __construct(private DeepLClient $deepl) {}

    public static function isHtml(string $field): bool
    {
        return in_array($field, self::HTML_FIELDS, true);
    }

    /**
     * @param  Collection<int, Language>  $targets
     * @param  array<string, array<string, mixed>>  $current  The dialog's boxes, by language code then field.
     *                                                         A failure stops the run — a quota or key problem would
     *                                                         fail every language — but drafts already made are kept.
     * @return array{drafts: array<string, array<string, string>>, error: ?DeepLException}
     */
    public function handle(Model $record, Collection $targets, array $current): array
    {
        $drafts = [];
        $source = $record->contentLanguageCode();

        try {
            foreach ($targets as $language) {
                $missing = collect($record->translatableFields())
                    ->filter(fn (string $field): bool => filled($record->getAttribute($field)) && blank($current[$language->code][$field] ?? null));

                if ($missing->isEmpty()) {
                    continue;
                }

                foreach ([false, true] as $html) {
                    $texts = $missing
                        ->filter(fn (string $field): bool => self::isHtml($field) === $html)
                        ->mapWithKeys(fn (string $field): array => [$field => (string) $record->getAttribute($field)])
                        ->all();

                    if ($texts === [] || ! $this->deepl->supports($source, $language->code, $html)) {
                        continue;
                    }

                    $drafts[$language->code] = [
                        ...($drafts[$language->code] ?? []),
                        ...$this->deepl->translate($texts, $source, $language->code, $html),
                    ];
                }
            }
        } catch (DeepLException $exception) {
            return ['drafts' => $drafts, 'error' => $exception];
        }

        return ['drafts' => $drafts, 'error' => null];
    }
}
