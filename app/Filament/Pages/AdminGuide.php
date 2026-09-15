<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * The manager guide — docs/admin-guide.md, with a contents list and a search.
 *
 * The Markdown file is the single source of truth, exactly as the changelog is
 * for What's new: adding a `## ` heading to the file adds a section here and an
 * entry to the contents. A manual is read two ways — straight through once,
 * then jumped into — which is what the contents and the search are for.
 */
class AdminGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.guide.nav');
    }

    public function getTitle(): string
    {
        return __t('admin_nav.guide.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.docs');
    }

    protected string $view = 'filament.pages.admin-guide';

    public static function guidePath(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $localized = base_path("docs/admin-guide.{$locale}.md");

        return is_file($localized) ? $localized : base_path('docs/admin-guide.md');
    }

    /**
     * The guide, split at its `##` headings, cached on the file's modification
     * time so an edit shows on the next load.
     *
     * @return array<int, array{id: string, heading: string, html: string, text: string}>
     */
    public function sections(): array
    {
        $locale = app()->getLocale();
        $path = static::guidePath($locale);

        if (! is_file($path)) {
            return [];
        }

        return Cache::remember(
            'admin-guide.'.$locale.'.'.basename($path).'.'.filemtime($path),
            now()->addDay(),
            fn (): array => static::parse((string) file_get_contents($path)),
        );
    }

    /**
     * @return array<int, array{id: string, heading: string, html: string, text: string}>
     */
    public static function parse(string $markdown): array
    {
        $markdown = str_replace("\r\n", "\n", $markdown);
        $markdown = (string) preg_replace('/<!--.*?-->/s', '', $markdown);
        // The file's own `# ` title: the page heading already says it.
        $markdown = (string) preg_replace('/\A\s*#[ \t]+[^\n]*\n/', '', $markdown);

        // [preamble, heading, body, heading, body, …]
        $parts = preg_split('/^##[ \t]+(.+?)[ \t]*$/m', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        $sections = [];

        if (trim(static::withoutTrailingRule($parts[0] ?? '')) !== '') {
            $sections[] = static::section('introduction', '', $parts[0]);
        }

        for ($i = 1; $i < count($parts); $i += 2) {
            $heading = trim($parts[$i]);
            $sections[] = static::section(Str::slug($heading), $heading, $parts[$i + 1] ?? '');
        }

        return $sections;
    }

    /** @return array{id: string, heading: string, html: string, text: string} */
    private static function section(string $id, string $heading, string $body): array
    {
        // Raw HTML stripped and unsafe links refused: the file is ours, but a
        // guide needs neither, and being wrong about who can edit it is costly.
        $html = Str::markdown(static::withoutTrailingRule($body), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $text = html_entity_decode(strip_tags($heading.' '.$html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return [
            'id' => $id,
            'heading' => $heading,
            'html' => $html,
            // What the client-side search matches against.
            'text' => Str::lower(trim((string) preg_replace('/\s+/', ' ', $text))),
        ];
    }

    /** The `---` between sections in the file is structure, not content. */
    private static function withoutTrailingRule(string $body): string
    {
        return (string) preg_replace('/\n[ \t]*(-{3,}|\*{3,}|_{3,})[ \t]*\s*\z/', "\n", "\n".$body);
    }
}
