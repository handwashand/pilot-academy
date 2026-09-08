<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * What's new — docs/CHANGELOG.md, parsed at request time.
 *
 * The Markdown file is the single source of truth. There is no second copy to
 * update and nothing that can drift: adding a `## <Month> <Year>` heading to
 * the file adds a section to this page.
 */
class Changelog extends Page
{
    /**
     * The categories a `###` heading inside a release can map to, in the order
     * their pills appear. `other` is the fallback for a heading that names
     * something of its own — those sections keep their own heading text.
     *
     * One entry per category: the pills, the per-release counts and the item
     * dots all read from here, so a fifth category is one new entry.
     *
     * The dot classes are written out in full because Tailwind scans for
     * literal class names; the panel theme lists app/Filament for that reason.
     *
     * @var array<string, array{label: string, dot: string}>
     */
    public const TYPES = [
        'added' => ['label' => 'Added', 'dot' => 'bg-emerald-500 dark:bg-emerald-400'],
        'changed' => ['label' => 'Changed', 'dot' => 'bg-sky-500 dark:bg-sky-400'],
        'fixed' => ['label' => 'Fixed', 'dot' => 'bg-violet-500 dark:bg-violet-400'],
        'limitations' => ['label' => 'Known limitations', 'dot' => 'bg-amber-500 dark:bg-amber-400'],
        'other' => ['label' => 'Other', 'dot' => 'bg-gray-400 dark:bg-gray-500'],
    ];

    /**
     * Heading text (lowercased) to category. Anything absent falls back to
     * `other`, which is why an entry written as "### Nudge students who have
     * gone quiet" still renders — it simply keeps its own heading.
     *
     * @var array<string, string>
     */
    private const HEADING_TYPES = [
        'added' => 'added',
        'new' => 'added',
        'changed' => 'changed',
        'improved' => 'changed',
        'fixed' => 'fixed',
        'known limitations' => 'limitations',
        'limitations' => 'limitations',
        'known issues' => 'limitations',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    // The class and route stay "changelog" so existing links keep working;
    // only what people read is changed.
    protected static ?string $navigationLabel = "What's new";

    protected static ?string $title = "What's new";

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.changelog';

    /** Where the page looks for the changelog. */
    public static function changelogPath(): string
    {
        return base_path('docs/CHANGELOG.md');
    }

    /** The same path as a person should read it in an empty state. */
    public static function changelogLabel(): string
    {
        return 'docs/CHANGELOG.md';
    }

    /**
     * The parsed changelog, newest release first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function releases(): array
    {
        return static::releasesFrom(static::changelogPath());
    }

    /**
     * Read and parse a changelog file, cached on its modification time: an edit
     * shows immediately, while repeated views neither re-read nor re-parse it.
     * A missing file is not an error — it yields no releases and the page
     * renders an empty state naming the file.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function releasesFrom(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        return Cache::remember(
            'changelog.'.md5($path).'.'.filemtime($path),
            now()->addDay(),
            fn (): array => static::parse((string) file_get_contents($path)),
        );
    }

    /**
     * Split changelog Markdown into releases and their categorised items.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function parse(string $markdown): array
    {
        // Authoring notes live in HTML comments and are not for readers.
        $markdown = (string) preg_replace('/<!--.*?-->/s', '', $markdown);
        $markdown = str_replace("\r\n", "\n", $markdown);

        $releases = [];

        foreach (static::splitOnHeading($markdown, 2) as [$title, $body]) {
            $sections = [];

            foreach (static::splitOnHeading($body, 3) as [$heading, $sectionBody]) {
                $items = static::itemsIn($sectionBody);

                if ($items === []) {
                    continue;
                }

                $type = static::HEADING_TYPES[Str::lower(trim($heading))] ?? 'other';

                $sections[] = [
                    'type' => $type,
                    // A recognised category shows its canonical label; anything
                    // else keeps the words whoever shipped the change wrote.
                    'label' => $type === 'other' ? $heading : static::TYPES[$type]['label'],
                    'items' => $items,
                ];
            }

            if ($sections !== []) {
                $releases[] = ['title' => $title, 'sections' => $sections];
            }
        }

        return $releases;
    }

    /**
     * Cut Markdown into [heading, body] pairs at ATX headings of one level.
     * Anything before the first heading is dropped: at level 2 that is the
     * file's own title, which the panel's page chrome already shows.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private static function splitOnHeading(string $markdown, int $level): array
    {
        $marker = str_repeat('#', $level);
        $inFence = false;
        $parts = [];
        $current = null;

        foreach (explode("\n", $markdown) as $line) {
            // A fenced block can hold a line starting with #, which is code.
            if (preg_match('/^\s{0,3}(```|~~~)/', $line)) {
                $inFence = ! $inFence;
            }

            if (! $inFence && preg_match('/^'.$marker.'[ \t]+(.*\S)[ \t]*$/', $line, $m)) {
                if ($current !== null) {
                    $parts[] = $current;
                }

                $current = [$m[1], ''];

                continue;
            }

            if ($current !== null) {
                $current[1] .= $line."\n";
            }
        }

        if ($current !== null) {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * The items in one section. Each `-` or `*` bullet is an item and its
     * continuation lines belong to it. A section with no bullets — most of the
     * older entries here are written as prose — still has to render, so it
     * falls back to its own Markdown as a single block rather than vanishing.
     *
     * @return array<int, array{html: string, text: string}>
     */
    private static function itemsIn(string $body): array
    {
        // A trailing rule separates releases in the file. That is structure,
        // not content, so it must never render as a horizontal rule.
        $body = (string) preg_replace('/\n[ \t]*(-{3,}|\*{3,}|_{3,})[ \t]*\n*$/', "\n", $body);

        $blocks = [];
        $lead = '';
        $current = null;
        $inFence = false;

        foreach (explode("\n", $body) as $line) {
            if (preg_match('/^\s{0,3}(```|~~~)/', $line)) {
                $inFence = ! $inFence;
            }

            if (! $inFence && preg_match('/^[-*][ \t]+(.*)$/', $line, $m)) {
                if ($current !== null) {
                    $blocks[] = $current;
                }

                $current = $m[1]."\n";

                continue;
            }

            if ($current === null) {
                $lead .= $line."\n";

                continue;
            }

            // Continuation lines: strip one level of the bullet's indent so the
            // text renders as prose rather than a nested list or a code block.
            $current .= preg_replace('/^[ \t]{1,4}/', '', $line)."\n";
        }

        if ($current !== null) {
            $blocks[] = $current;
        }

        if ($blocks === []) {
            // No bullets at all: the whole section is one item.
            $blocks = [$lead];
        } elseif (trim($lead) !== '') {
            // Text introducing the bullets is content too — keep it, first.
            array_unshift($blocks, $lead);
        }

        $items = [];

        foreach ($blocks as $block) {
            if (trim($block) === '') {
                continue;
            }

            $html = static::renderMarkdown($block);

            $items[] = [
                'html' => $html,
                // Lowercased plain text is what the client-side search matches
                // against, so a query never has to know about the markup.
                'text' => static::plainText($html),
            ];
        }

        return $items;
    }

    /**
     * Markdown to HTML, with raw HTML input stripped and unsafe links refused.
     *
     * The file lives in this repository and is written by whoever ships the
     * change, so it is not untrusted input. But nothing in a changelog needs
     * raw HTML or a javascript: link, and the cost of being wrong about who
     * can edit the file is high.
     */
    private static function renderMarkdown(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /** Rendered HTML reduced to its words, lowercased, for searching. */
    private static function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::lower(trim((string) preg_replace('/\s+/', ' ', $text)));
    }
}
