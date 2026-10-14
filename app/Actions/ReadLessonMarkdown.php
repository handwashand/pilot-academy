<?php

namespace App\Actions;

use Illuminate\Support\Str;

/**
 * A lesson's text from a Markdown file, for the Upload button on the lesson
 * form's text box.
 *
 * The file's first heading (`# Title`) is the lesson's title and is taken out
 * of the text, because the lesson page prints the title itself. Everything
 * else becomes the formatted lesson text. Raw HTML in the file is stripped and
 * unsafe links are refused, the same as the guides — the text ends up on a
 * page partners read.
 */
class ReadLessonMarkdown
{
    /** A lesson, not a book: enough for a very long one, small enough to refuse a wrong file. */
    public const MAX_KILOBYTES = 512;

    /**
     * @return array{title: ?string, html: string}|null Null when the file is not readable text.
     */
    public function handle(string $markdown): ?array
    {
        // Word processors and Windows Notepad put a byte-order mark first.
        $markdown = preg_replace('/^\xEF\xBB\xBF/', '', $markdown);

        if (! mb_check_encoding($markdown, 'UTF-8') || str_contains($markdown, "\0")) {
            return null;
        }

        $markdown = trim(str_replace(["\r\n", "\r"], "\n", $markdown));
        $title = null;

        if (preg_match('/\A#[ \t]+(.+?)[ \t#]*(\n|\z)/u', $markdown, $heading)) {
            $title = Str::limit(trim($heading[1]), 255, '');
            $markdown = ltrim(Str::after($markdown, $heading[0]));
        }

        return [
            'title' => $title,
            'html' => trim(Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])),
        ];
    }
}
