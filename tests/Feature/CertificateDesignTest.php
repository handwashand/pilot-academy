<?php

namespace Tests\Feature;

use App\Actions\IssueCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The course certificate as refined on 2026-11-24: issued by Pilot Telematics
 * with the company's original PILOT logo, a precise achievement line (lessons
 * and score, never study time). No signatory: the owner left it out on
 * 2026-11-25.
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
}
