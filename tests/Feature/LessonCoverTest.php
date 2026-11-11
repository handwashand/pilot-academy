<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A lesson card without its own cover is drawn from its course's theme with
 * the lesson's number; a cover the creator added is shown untouched.
 */
class LessonCoverTest extends TestCase
{
    use RefreshDatabase;

    private function publishedCourse(array $overrides = []): Course
    {
        return Course::create([
            'title' => 'Fuel telemetry',
            'slug' => 'fuel-telemetry',
            'level' => 'advanced',
            'status' => Course::STATUS_PUBLISHED,
            ...$overrides,
        ]);
    }

    private function lessonIn(Course $course, string $slug, array $overrides = []): Lesson
    {
        return $course->lessons()->create([
            'title' => ucfirst($slug),
            'slug' => $slug,
            'status' => Lesson::STATUS_PUBLISHED,
            ...$overrides,
        ]);
    }

    public function test_an_unknown_or_empty_theme_falls_back_to_dots_on_pilot_blue(): void
    {
        $default = Course::coverThemeFor(null, null);

        $this->assertStringEndsWith('img/patterns/dots.svg', $default['pattern']);
        $this->assertSame(Course::COVER_COLORS['pilot'], [$default['from'], $default['to']]);
        $this->assertSame($default, Course::coverThemeFor('../../etc/passwd', 'neon'));
    }

    public function test_every_pattern_has_its_svg_file_and_a_name_in_every_language(): void
    {
        foreach (Course::COVER_PATTERNS as $pattern) {
            $this->assertFileExists(public_path("img/patterns/{$pattern}.svg"));
        }

        foreach (['en', 'ru', 'es', 'fr', 'pt', 'ar'] as $code) {
            $labels = require lang_path("{$code}/labels.php");
            $this->assertSame(Course::COVER_PATTERNS, array_keys($labels['cover_pattern']), $code);
            $this->assertSame(array_keys(Course::COVER_COLORS), array_keys($labels['cover_color']), $code);
        }
    }

    public function test_the_course_form_saves_a_theme_and_refuses_an_unknown_one(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateCourse::class)
            ->fillForm(['title' => 'Themed', 'slug' => 'themed', 'level' => 'beginner', 'cover_pattern' => 'route', 'cover_color' => 'teal'])
            ->call('create')
            ->assertHasNoFormErrors();

        $course = Course::where('slug', 'themed')->firstOrFail();
        $this->assertSame(['route', 'teal'], [$course->cover_pattern, $course->cover_color]);

        Livewire::actingAs($admin)
            ->test(CreateCourse::class)
            ->fillForm(['title' => 'Bad', 'slug' => 'bad', 'level' => 'beginner', 'cover_pattern' => 'neon'])
            ->call('create')
            ->assertHasFormErrors(['cover_pattern']);
    }

    public function test_a_lesson_without_a_cover_gets_the_course_theme_and_its_number(): void
    {
        $course = $this->publishedCourse(['cover_pattern' => 'circuit', 'cover_color' => 'violet']);
        $this->lessonIn($course, 'first');
        $this->lessonIn($course, 'second');

        $this->get(route('academy.course', $course))
            ->assertOk()
            ->assertSee('img/patterns/circuit.svg', false)
            ->assertSee(Course::COVER_COLORS['violet'][0], false)
            ->assertSee('Lesson 1')
            ->assertSee('Lesson 2');
    }

    public function test_a_cover_the_creator_added_is_kept_with_nothing_drawn_over_it(): void
    {
        $course = $this->publishedCourse();
        $this->lessonIn($course, 'covered', ['image_path' => 'lesson-images/own-cover.png']);

        $this->get(route('academy.course', $course))
            ->assertOk()
            ->assertSee('lesson-images/own-cover.png', false)
            ->assertDontSee('Lesson 1');
    }
}
