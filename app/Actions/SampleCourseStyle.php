<?php

namespace App\Actions;

use App\Models\AiUsage;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * A short sample of published courses, for the course generator to follow.
 * Two courses at a time, and the pair moves on with each draft, so the
 * generator keeps seeing how courses are actually written. A creator only
 * supplies courses in a product they own.
 */
class SampleCourseStyle
{
    public const SAMPLE = 2;

    public function for(User $user): string
    {
        $ids = $this->courseIds($user);

        if ($ids === []) {
            return '';
        }

        $slot = AiUsage::query()->where('purpose', AiUsage::PURPOSE_COURSE)->count();
        $start = $slot % count($ids);
        $take = min(self::SAMPLE, count($ids));
        $picked = [];

        for ($i = 0; $i < $take; $i++) {
            $picked[] = $ids[($start + $i) % count($ids)];
        }

        $courses = Course::query()
            ->whereKey($picked)
            ->with([
                'lessons' => fn ($query) => $query->published()->with(['questions.options']),
            ])
            ->get()
            ->sortBy(fn (Course $course): int|false => array_search($course->id, $picked, true))
            ->values();

        return mb_substr($this->render($courses), 0, 3500);
    }

    /**
     * @return array<int, int>
     */
    private function courseIds(User $user): array
    {
        $query = Course::query()
            ->published()
            ->whereHas('lessons', fn ($lessons) => $lessons->published());

        if (! $user->isAdmin()) {
            $query->whereIn('product_id', $user->products()->pluck('products.id'));
        }

        return $query->orderBy('id')->pluck('id')->all();
    }

    /**
     * @param  Collection<int, Course>  $courses
     */
    private function render(Collection $courses): string
    {
        $blocks = [];

        foreach ($courses as $index => $course) {
            $lessons = $course->lessons->take(3);

            if ($lessons->isEmpty()) {
                continue;
            }

            $lines = [
                'Example '.($index + 1),
                'Title: '.$this->plain($course->title, 120),
                'Level: '.($course->level ?: 'beginner'),
            ];

            if (is_string($course->audience) && $course->audience !== '') {
                $lines[] = 'Audience: '.$course->audience;
            }

            $description = $this->plain($course->description, 400);

            if ($description !== '') {
                $lines[] = 'Description: '.$description;
            }

            $lines[] = 'Lessons:';

            foreach ($lessons as $number => $lesson) {
                $summary = $this->plain($lesson->summary, 160);
                $lines[] = '- '.$this->plain($lesson->title, 120).($summary !== '' ? ' — '.$summary : '');

                if ($number !== 0) {
                    continue;
                }

                $content = $this->plain($lesson->content, 500);

                if ($content !== '') {
                    $lines[] = '  Opening: '.$content;
                }

                $question = $lesson->questions->first();

                if ($question === null) {
                    continue;
                }

                $lines[] = '  Question: '.$this->plain($question->prompt, 180);

                foreach ($question->options->take(4) as $option) {
                    $lines[] = '  - '.$this->plain($option->text, 80).($option->is_correct ? ' (correct)' : '');
                }
            }

            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }

    private function plain(mixed $value, int $limit): string
    {
        if (! is_string($value)) {
            return '';
        }

        $withoutScripts = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $value) ?? $value;
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($withoutScripts)) ?? '');

        if ($text === '' || mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit - 1).'…';
    }
}
