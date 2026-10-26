<?php

namespace Tests\Feature;

use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonColorTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_color_tag_options_are_red_yellow_and_blue(): void
    {
        $this->assertSame(
            ['red', 'yellow', 'blue'],
            array_keys(Lesson::colorTagLabels()),
        );
        $this->assertSame(
            ['Red', 'Yellow', 'Blue'],
            array_values(Lesson::colorTagLabels()),
        );
    }

    public function test_existing_green_color_tags_are_migrated_to_yellow(): void
    {
        $lesson = Lesson::create([
            'title' => 'Color tag migration',
            'slug' => 'color-tag-migration',
            'content' => '<p>Body.</p>',
            'color_tag' => 'green',
        ]);

        $migration = require database_path('migrations/2026_10_27_000002_replace_green_lesson_color_tags_with_yellow.php');
        $migration->up();

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'color_tag' => 'yellow',
        ]);
    }
}
