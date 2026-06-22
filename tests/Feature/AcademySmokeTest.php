<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademySmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
    }

    public function test_home_lists_the_course(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Pilot Quick Start');
    }

    public function test_course_audience_badge_is_shown(): void
    {
        $course = Course::first();
        $course->update(['audience' => 'technical']);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('For Technical');
    }

    public function test_lesson_page_shows_video_and_quiz(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();

        $this->get(route('academy.lesson', [$course, $lesson]))
            ->assertStatus(200)
            ->assertSee('youtube.com/embed', false)
            ->assertSee('Knowledge check');
    }

    public function test_correct_quiz_answers_complete_the_lesson(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->with('questions.options')->first();

        $answers = [];
        foreach ($lesson->questions as $q) {
            $answers[$q->id] = $q->options->firstWhere('is_correct', true)->id;
        }

        $this->post(route('academy.quiz', [$course, $lesson]), ['answers' => $answers])
            ->assertRedirect(route('academy.lesson', [$course, $lesson]))
            ->assertSessionHas('quiz_passed', true);
    }

    public function test_uploaded_video_takes_priority_over_youtube(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $lesson->update([
            'video_path' => 'lesson-videos/sample.mp4',
            'youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
        ]);

        $this->get(route('academy.lesson', [$course, $lesson]))
            ->assertStatus(200)
            ->assertSee('<video', false)
            ->assertSee('lesson-videos/sample.mp4', false)
            ->assertDontSee('youtube.com/embed', false);
    }

    public function test_admin_can_open_lesson_edit_form_with_quiz_repeater(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $lesson = Lesson::first();

        $this->actingAs($admin)
            ->get("/admin/lessons/{$lesson->id}/edit")
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/courses')
            ->assertStatus(200);
    }

    public function test_admin_can_manage_users_and_companies(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/companies')->assertStatus(200);
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $student = User::create([
            'name' => 'Partner User',
            'email' => 'student@example.com',
            'password' => bcrypt('secret'),
            'is_admin' => false,
        ]);

        $this->actingAs($student)->get('/admin')->assertStatus(403);
    }
}
