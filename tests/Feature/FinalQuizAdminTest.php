<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalQuizAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function courseWithFinal(): Course
    {
        $course = Course::first();
        $course->update(['final_quiz_enabled' => true, 'pass_percent' => 80]);
        $ids = Question::whereIn('lesson_id', $course->lessons()->pluck('id'))->pluck('id');
        $course->finalQuestions()->sync($ids->all());

        return $course->fresh();
    }

    public function test_course_edit_shows_final_quiz_and_bank_relation_manager(): void
    {
        $course = $this->courseWithFinal();

        $this->actingAs($this->admin())
            ->get("/admin/courses/{$course->id}/edit")
            ->assertStatus(200)
            ->assertSee('Final quiz')
            ->assertSee('Final questions');
    }

    public function test_certificate_resource_lists_issued_certificates(): void
    {
        $course = $this->courseWithFinal();
        $company = Company::create(['name' => 'Acme Partner']);
        $student = User::create([
            'name' => 'Cert Holder', 'email' => 'holder@example.com',
            'password' => bcrypt('x'), 'role' => 'learner', 'company_id' => $company->id,
        ]);

        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'number' => 'PA-ADMIN-1',
            'name' => 'Cert Holder',
            'score_percent' => 91,
            'issued_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/certificates')
            ->assertStatus(200)
            ->assertSee($certificate->number)
            ->assertSee('Acme Partner');
    }

    public function test_user_edit_shows_certificates_relation_manager(): void
    {
        $course = $this->courseWithFinal();
        $student = User::create([
            'name' => 'RM Student', 'email' => 'rm@example.com',
            'password' => bcrypt('x'), 'role' => 'learner',
        ]);
        Certificate::create([
            'user_id' => $student->id, 'course_id' => $course->id,
            'number' => 'PA-RM-1', 'name' => 'RM Student', 'score_percent' => 85, 'issued_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/users/{$student->id}/edit")
            ->assertStatus(200)
            ->assertSee('Certificates');
    }

    public function test_dashboard_and_companies_render_with_certificate_widgets(): void
    {
        $this->courseWithFinal();
        Company::create(['name' => 'Coverage Co']);

        $this->actingAs($this->admin())->get('/admin')->assertStatus(200);
        $this->actingAs($this->admin())->get('/admin/companies')
            ->assertStatus(200)
            ->assertSee('Certified');
    }
}
