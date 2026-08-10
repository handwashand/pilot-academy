<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonFinalQuizLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
        Course::first()->update(['final_quiz_enabled' => true]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'Sidebar Student',
            'email' => 'sidebar@example.com',
            'password' => bcrypt('x'),
            'role' => 'learner',
        ]);
    }

    public function test_sidebar_shows_remaining_lessons_when_not_finished(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();

        $this->actingAs($this->student())
            ->get(route('academy.lesson', [$course, $lesson]))
            ->assertStatus(200)
            ->assertSee('lessons left');
    }

    public function test_sidebar_shows_start_link_when_all_lessons_done(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $student = $this->student();

        foreach ($course->publishedLessons as $l) {
            $student->completedLessons()->attach($l->id, ['completed_at' => now()]);
        }

        $this->actingAs($student)
            ->get(route('academy.lesson', [$course, $lesson]))
            ->assertStatus(200)
            ->assertSee('Final quiz — start')
            ->assertSee(route('academy.final.show', $course), false);
    }

    public function test_sidebar_prompts_anonymous_to_log_in(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();

        $this->get(route('academy.lesson', [$course, $lesson]))
            ->assertStatus(200)
            ->assertSee('log in to take it');
    }
}
