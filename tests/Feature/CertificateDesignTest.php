<?php

namespace Tests\Feature;

use App\Actions\IssueCertificate;
use App\Filament\Pages\SettingsCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The course certificate as refined on 2026-11-24: issued by Pilot Telematics
 * with the company's original PILOT logo, a precise achievement line (lessons
 * and score, never study time), and a signatory set under Settings →
 * Certificate.
 */
class CertificateDesignTest extends TestCase
{
    use RefreshDatabase;

    private function certificate(): Certificate
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@pilot.local', 'password' => 'password', 'role' => User::ROLE_LEARNER]);
        $course = Course::create(['title' => 'Mastering Sensors', 'slug' => 'mastering-sensors', 'level' => 'beginner', 'duration_minutes' => 150]);

        foreach (['One', 'Two', 'Three'] as $i => $title) {
            $course->lessons()->create(['title' => $title, 'slug' => 'lesson-'.$i, 'status' => Lesson::STATUS_PUBLISHED]);
        }

        return Certificate::create(['user_id' => $user->id, 'course_id' => $course->id, 'number' => 'PA-1-2026-ABC123', 'name' => 'Ana Mensah', 'score_percent' => 92, 'issued_at' => now()]);
    }

    /** Render the certificate and return the HTML dompdf was given, plus the view data. */
    private function render(Certificate $certificate): array
    {
        $seen = [];
        View::creator('certificates.pdf', function ($view) use (&$seen) {
            $seen = $view->getData();
        });
        app(IssueCertificate::class)->renderPdf($certificate);

        return [view('certificates.pdf', $seen)->render(), $seen];
    }

    public function test_it_names_the_company_and_the_achievement_without_study_time(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [$html, $data] = $this->render($this->certificate());

        $this->assertStringContainsString(e(__t('mail.certificate_pdf.issuer')), $html);
        $this->assertStringContainsString('Issued by Pilot Telematics (PTY) LTD', $html);
        $this->assertStringContainsString('<strong>Mastering Sensors</strong>', $html);
        $this->assertStringContainsString(__tc('academy.common.lessons', 3), $html);
        $this->assertStringContainsString('92%', $html);
        // The course is 2 h 30 min long; the certificate must not say so.
        $this->assertStringNotContainsString((string) Course::formatMinutes(150), $html);
        $this->assertNull($data['signatory'], 'With nobody set, no signature block.');
        $this->assertStringNotContainsString('class="sign ', $html);
    }

    public function test_it_carries_the_original_pilot_logo_unchanged(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [, $data] = $this->render($this->certificate());

        $this->assertSame(
            'data:image/png;base64,'.base64_encode((string) file_get_contents(public_path('img/pilot-telematics-logo-blue.png'))),
            $data['logo'],
        );
    }

    public function test_an_admin_sets_the_signatory_and_it_is_printed(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(SettingsCertificate::class)
            ->fillForm([
                'signatory_name' => 'Jane Doe',
                'signatory_title' => 'Head of Training, Pilot Telematics',
                'signature' => UploadedFile::fake()->image('signature.png', 600, 200),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $signatory = Certificate::signatory();
        $this->assertSame('Jane Doe', $signatory['name']);
        $this->assertSame('Head of Training, Pilot Telematics', $signatory['title']);
        $this->assertNotNull($signatory['signature']);

        [$html, $data] = $this->render($this->certificate());

        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('Head of Training, Pilot Telematics', $html);
        $this->assertStringStartsWith('data:image/png;base64,', $data['signatory']['signature']);
    }

    public function test_clearing_the_name_removes_the_signature_block(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('local')->put(Certificate::SIGNATORY_FILE, json_encode(['name' => '', 'title' => 'Head of Training']));

        $this->assertNull(Certificate::signatory());
    }
}
