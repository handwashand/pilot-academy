<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalQuizAdminBypassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);

        $course = Course::first();
        $course->update(['final_quiz_enabled' => true, 'pass_percent' => 80]);
        $course->finalQuestions()->sync(
            Question::whereIn('lesson_id', $course->lessons()->pluck('id'))->pluck('id')->all()
        );
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin', 'email' => 'admin@pilot.local',
            'password' => bcrypt('x'), 'role' => 'admin',
        ]);
    }

    private function student(): User
    {
        return User::create([
            'name' => 'Regular Student', 'email' => 'student@example.com',
            'password' => bcrypt('x'), 'role' => 'learner',
        ]);
    }

    public function test_admin_opens_final_quiz_without_finishing_lessons(): void
    {
        $course = Course::first();

        $this->actingAs($this->admin())
            ->get(route('academy.final.show', $course))
            ->assertStatus(200)
            ->assertSee('Full name for your certificate')
            ->assertSee('Staff preview')
            ->assertDontSee('Complete all lessons');
    }

    public function test_admin_can_pass_and_get_a_certificate_without_lessons(): void
    {
        Mail::fake();
        Storage::fake('public');

        $course = Course::first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('academy.final.start', $course), [
            'certificate_name' => 'Pilot Admin',
        ])->assertRedirect(route('academy.final.show', $course));

        $attempt = QuizAttempt::where('user_id', $admin->id)->where('course_id', $course->id)->first();
        $answers = [];
        foreach (Question::with('options')->whereIn('id', $attempt->question_ids)->get() as $q) {
            $answers[$q->id] = $q->correctOptionIds();
        }

        $this->actingAs($admin)->post(route('academy.final.submit', $course), ['answers' => $answers])
            ->assertRedirect(route('academy.final.show', $course));

        $this->assertDatabaseHas('certificates', [
            'user_id' => $admin->id,
            'course_id' => $course->id,
            'score_percent' => 100,
        ]);
    }

    public function test_regular_student_is_still_locked_until_lessons_done(): void
    {
        $course = Course::first();

        $this->actingAs($this->student())
            ->get(route('academy.final.show', $course))
            ->assertStatus(200)
            ->assertSee('Complete all lessons')
            ->assertDontSee('Full name for your certificate');
    }
}
