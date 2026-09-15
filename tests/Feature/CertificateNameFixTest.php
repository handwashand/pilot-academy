<?php

namespace Tests\Feature;

use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A misspelt name on an issued certificate used to need a developer: the
 * certificate stores the name it was printed with, and Regenerate PDF reprints
 * that same stored name.
 */
class CertificateNameFixTest extends TestCase
{
    use RefreshDatabase;

    private function certificate(): Certificate
    {
        $learner = User::create([
            'name' => 'Ana Pereira',
            'certificate_name' => 'Ana Perira',
            'email' => 'ana@partner.com',
            'password' => 'secret123',
            'role' => User::ROLE_LEARNER,
        ]);

        $course = Course::create([
            'title' => 'Understanding GARM',
            'slug' => 'understanding-garm',
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return Certificate::create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'number' => 'PA-TEST-0001',
            'name' => 'Ana Perira',
            'score_percent' => 90,
            'issued_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_an_admin_can_correct_the_name_and_the_pdf_is_reprinted(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->test(ListCertificates::class)
            ->callAction(TestAction::make('editName')->table($certificate), [
                'name' => ' Ana Pereira ',
                'update_profile' => true,
            ])
            ->assertHasNoFormErrors();

        $certificate->refresh();
        $this->assertSame('Ana Pereira', $certificate->name);
        $this->assertSame('PA-TEST-0001', $certificate->number, 'The number must not change.');
        $this->assertNotNull($certificate->pdf_path);
        Storage::disk('public')->assertExists($certificate->pdf_path);

        // Future certificates would otherwise repeat the misspelling.
        $this->assertSame('Ana Pereira', $certificate->user->fresh()->certificate_name);

        // The public verification page reads the corrected name.
        $this->get(route('certificates.verify', $certificate->number))->assertSee('Ana Pereira');
    }

    public function test_the_student_profile_can_be_left_alone(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->test(ListCertificates::class)
            ->callAction(TestAction::make('editName')->table($certificate), [
                'name' => 'Ana Pereira',
                'update_profile' => false,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('Ana Pereira', $certificate->fresh()->name);
        $this->assertSame('Ana Perira', $certificate->user->fresh()->certificate_name);
    }

    public function test_a_blank_name_is_refused(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->test(ListCertificates::class)
            ->callAction(TestAction::make('editName')->table($certificate), ['name' => ''])
            ->assertHasFormErrors(['name' => 'required']);

        $this->assertSame('Ana Perira', $certificate->fresh()->name);
    }
}
