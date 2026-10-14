<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Question;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/**
 * A course's final quiz from one Excel file, instead of question by question.
 *
 * One row is one question: the question, up to six answers (A–F), then the
 * letters of the right ones. The first row is the heading row and is skipped.
 * Everything is read as plain text — a formula, a date or a number in a cell is
 * only ever the words a student will read.
 *
 * All or nothing: one bad row and no question is saved, so a half-loaded quiz
 * never reaches the bank. Questions already in the bank are left out, so the
 * same file can be uploaded twice without doubling it.
 */
class ImportFinalQuizQuestions
{
    public const MAX_QUESTIONS = 300;

    private const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

    private const MAX_PROMPT = 2000;

    /** options.text is a 255-character column. */
    private const MAX_ANSWER = 255;

    /**
     * @return array{added: int, skipped: int, errors: array<int, string>}
     */
    public function handle(Course $course, string $path): array
    {
        try {
            $rows = $this->read($path);
        } catch (OpenSpoutException) {
            return $this->failed([__t('admin_courses.final_import.unreadable')]);
        }

        if ($rows === []) {
            return $this->failed([__t('admin_courses.final_import.empty')]);
        }

        if (count($rows) > self::MAX_QUESTIONS) {
            return $this->failed([__t('admin_courses.final_import.too_many', ['max' => self::MAX_QUESTIONS])]);
        }

        $errors = [];
        $questions = [];
        $known = $course->finalQuestions()->pluck('prompt')
            ->mapWithKeys(fn (string $prompt): array => [$this->fingerprint($prompt) => true])
            ->all();
        $skipped = 0;

        foreach ($rows as $number => $cells) {
            $question = $this->question($cells);

            if (is_string($question)) {
                $errors[] = __t('admin_courses.final_import.row', ['row' => $number, 'problem' => $question]);

                continue;
            }

            $fingerprint = $this->fingerprint($question['prompt']);

            if (isset($known[$fingerprint])) {
                $skipped++;

                continue;
            }

            $known[$fingerprint] = true;
            $questions[] = $question;
        }

        if ($errors !== []) {
            return $this->failed($errors);
        }

        DB::transaction(function () use ($course, $questions): void {
            $order = (int) $course->finalQuestions()->max('course_final_questions.sort_order');

            foreach ($questions as $data) {
                // Course-only, and written in the course's language — the one
                // Translate quiz on this tab treats as the original.
                $question = Question::create([
                    'lesson_id' => null,
                    'prompt' => $data['prompt'],
                    'type' => $data['type'],
                    'language' => $course->contentLanguageCode(),
                ]);

                foreach ($data['options'] as $position => $option) {
                    $question->options()->create([...$option, 'sort_order' => $position + 1]);
                }

                $course->finalQuestions()->attach($question->id, ['sort_order' => ++$order]);
            }
        });

        return ['added' => count($questions), 'skipped' => $skipped, 'errors' => []];
    }

    /**
     * The example file to fill in: the heading row and two questions, one of
     * each kind, in the language of whoever downloads it.
     *
     * @return string Path of a temporary .xlsx file; the caller sends and deletes it.
     */
    public function template(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'quiz').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([
            __t('admin_common.question'),
            ...array_map(fn (string $letter): string => __t('admin_courses.final_import.answer_column', ['letter' => $letter]), self::LETTERS),
            __t('admin_courses.final_import.correct_column'),
        ]));
        $writer->addRow(Row::fromValues([
            __t('admin_courses.final_import.example_single'),
            __t('admin_courses.final_import.example_yes'),
            __t('admin_courses.final_import.example_no'),
            '', '', '', '',
            'A',
        ]));
        $writer->addRow(Row::fromValues([
            __t('admin_courses.final_import.example_multiple'),
            __t('admin_courses.final_import.example_first'),
            __t('admin_courses.final_import.example_second'),
            __t('admin_courses.final_import.example_third'),
            '', '', '',
            'A, C',
        ]));
        $writer->close();

        return $path;
    }

    /**
     * The first sheet's rows after the heading row, keyed by their row number
     * in Excel, each a list of trimmed strings. Blank rows are left out.
     *
     * @return array<int, array<int, string>>
     */
    private function read(string $path): array
    {
        $rows = [];
        $reader = new Reader;
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $number => $row) {
                    if ($number === 1) {
                        continue;
                    }

                    $cells = array_map($this->text(...), array_values($row->toArray()));

                    if (implode('', $cells) !== '') {
                        $rows[$number] = $cells;
                    }

                    // No point reading a huge sheet to the end only to refuse it.
                    if (count($rows) > self::MAX_QUESTIONS) {
                        break;
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    private function text(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * One row as a question, or the reason it cannot be one.
     *
     * @param  array<int, string>  $cells
     * @return array{prompt: string, type: string, options: array<int, array{text: string, is_correct: bool}>}|string
     */
    private function question(array $cells): array|string
    {
        $prompt = $cells[0] ?? '';

        if ($prompt === '') {
            return __t('admin_courses.final_import.no_question');
        }

        if (Str::length($prompt) > self::MAX_PROMPT) {
            return __t('admin_courses.final_import.question_too_long', ['max' => self::MAX_PROMPT]);
        }

        $answers = [];

        foreach (self::LETTERS as $index => $letter) {
            $answer = $cells[$index + 1] ?? '';

            if (Str::length($answer) > self::MAX_ANSWER) {
                return __t('admin_courses.final_import.answer_too_long', ['letter' => $letter, 'max' => self::MAX_ANSWER]);
            }

            if ($answer !== '') {
                $answers[$letter] = $answer;
            }
        }

        if (count($answers) < 2) {
            return __t('admin_courses.final_import.few_answers');
        }

        $correct = preg_split('/[\s,;]+/', Str::upper($cells[count(self::LETTERS) + 1] ?? ''), -1, PREG_SPLIT_NO_EMPTY);

        if ($correct === []) {
            return __t('admin_courses.final_import.no_correct');
        }

        foreach ($correct as $letter) {
            if (! isset($answers[$letter])) {
                return __t('admin_courses.final_import.bad_correct', ['letter' => Str::limit($letter, 10)]);
            }
        }

        return [
            'prompt' => $prompt,
            'type' => count(array_unique($correct)) > 1 ? Question::TYPE_MULTIPLE : Question::TYPE_SINGLE,
            'options' => collect($answers)
                ->map(fn (string $text, string $letter): array => ['text' => $text, 'is_correct' => in_array($letter, $correct, true)])
                ->values()
                ->all(),
        ];
    }

    private function fingerprint(string $prompt): string
    {
        return Str::lower(Str::squish($prompt));
    }

    /**
     * @param  array<int, string>  $errors
     * @return array{added: int, skipped: int, errors: array<int, string>}
     */
    private function failed(array $errors): array
    {
        return ['added' => 0, 'skipped' => 0, 'errors' => $errors];
    }
}
