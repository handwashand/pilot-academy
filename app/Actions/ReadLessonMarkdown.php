<?php

namespace App\Actions;

use Illuminate\Support\Str;

/**
 * A lesson from a Markdown file, for the Import button on the lesson form's
 * text box. The layout is the one in public/templates/lesson-template.md:
 *
 *   # Title                  → the lesson's title (taken out of the text)
 *   > One-line summary       → the summary, if it comes straight after the title
 *   Lesson text …            → the formatted lesson text
 *   ## Quiz                  → everything after this is the knowledge check:
 *   1. Question?
 *   - [x] Right answer
 *   - [ ] Wrong answer
 *
 * Only the title and the text are required; a file without a summary or a
 * quiz still fills what it has. Raw HTML in the file is stripped and unsafe
 * links are refused, the same as the guides — the text ends up on a page
 * partners read.
 */
class ReadLessonMarkdown
{
    /** A lesson, not a book: enough for a very long one, small enough to refuse a wrong file. */
    public const MAX_KILOBYTES = 512;

    /**
     * Null when the file is not readable text.
     *
     * @return array{title: ?string, summary: ?string, html: string, questions: array<int, array{prompt: string, options: array<int, array{text: string, is_correct: bool}>}>, errors: array<int, string>}|null
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
        $summary = null;

        if (preg_match('/\A#[ \t]+(.+?)[ \t#]*(\n|\z)/u', $markdown, $heading)) {
            $title = Str::limit(trim($heading[1]), 255, '');
            $markdown = ltrim(Str::after($markdown, $heading[0]));
        }

        // A quote straight after the title is the summary.
        if (preg_match('/\A((?:>[^\n]*(?:\n|\z))+)/u', $markdown, $quote)) {
            $summary = trim(preg_replace('/^>[ \t]?/m', '', $quote[1]));
            $summary = $summary === '' ? null : Str::limit(preg_replace('/\s+/u', ' ', $summary), 500, '');
            $markdown = ltrim(Str::after($markdown, $quote[0]));
        }

        $quiz = '';

        if (preg_match('/^##[ \t]+(quiz|knowledge check)[ \t#]*$/imu', $markdown, $marker, PREG_OFFSET_CAPTURE)) {
            $quiz = substr($markdown, $marker[0][1] + strlen($marker[0][0]));
            $markdown = rtrim(substr($markdown, 0, $marker[0][1]));
        }

        [$questions, $errors] = $this->questions($quiz);

        return [
            'title' => $title,
            'summary' => $summary,
            'html' => trim(Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])),
            'questions' => $questions,
            'errors' => $errors,
        ];
    }

    /**
     * "1. Question?" lines, each followed by "- [x] answer" / "- [ ] answer".
     * A lesson's knowledge check takes one right answer per question, so a
     * question with none or several is reported rather than imported — one
     * nobody could pass would leave students stuck on the lesson.
     *
     * @return array{0: array<int, array{prompt: string, options: array<int, array{text: string, is_correct: bool}>}>, 1: array<int, string>}
     */
    private function questions(string $quiz): array
    {
        $questions = [];
        $current = null;

        foreach (explode("\n", $quiz) as $line) {
            $line = trim($line);

            if (preg_match('/^\d+[.)][ \t]+(.+)$/u', $line, $match)) {
                if ($current !== null) {
                    $questions[] = $current;
                }

                $current = ['prompt' => Str::limit(trim($match[1]), 1000, ''), 'options' => []];
            } elseif ($current !== null && preg_match('/^[-*][ \t]+\[([ xX])\][ \t]+(.+)$/u', $line, $match)) {
                $current['options'][] = ['text' => Str::limit(trim($match[2]), 255, ''), 'is_correct' => strtolower($match[1]) === 'x'];
            }
        }

        if ($current !== null) {
            $questions[] = $current;
        }

        $errors = [];

        foreach ($questions as $i => $question) {
            $number = $i + 1;

            if (count($question['options']) < 2) {
                $errors[] = __t('admin_lessons.form.import_error_options', ['number' => $number]);
            } elseif (count(array_filter(array_column($question['options'], 'is_correct'))) !== 1) {
                $errors[] = __t('admin_lessons.form.import_error_correct', ['number' => $number]);
            }
        }

        return [$questions, $errors];
    }
}
