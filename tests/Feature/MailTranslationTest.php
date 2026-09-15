<?php

namespace Tests\Feature;

use App\Actions\IssueCertificate;
use App\Mail\CertificateIssued;
use App\Mail\CourseReminder;
use App\Mail\MailCheckMessage;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\Translator;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Emails and certificates go out in the recipient's language — not the
 * language of whoever pressed the button. Someone whose language is switched
 * off gets the default.
 */
class MailTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
    }

    private function person(?string $locale, string $role = User::ROLE_LEARNER): User
    {
        $user = User::create([
            'name' => 'Ana Pilot',
            'email' => 'ana-'.($locale ?? 'none').'-'.$role.'@pilot.local',
            'password' => 'password',
            'role' => $role,
        ]);

        $user->forceFill(['locale' => $locale])->save();

        return $user;
    }

    private function certificateFor(User $student): Certificate
    {
        $course = Course::create(['title' => 'Pilot basics', 'slug' => 'pilot-basics', 'level' => 'beginner']);
        $course->setTranslation('title', 'fr', 'Les bases du pilote');

        return Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'number' => 'PA-1-2026-ABCDEF',
            'name' => $student->name,
            'score_percent' => 92,
            'issued_at' => now(),
        ]);
    }

    /** The message the array mailer actually built, subject and body rendered. */
    private function lastEmail(): Email
    {
        return Mail::getSymfonyTransport()->messages()->last()->getOriginalMessage();
    }

    public function test_a_reminder_is_written_in_the_students_language(): void
    {
        $student = $this->person('ru');

        Mail::to($student->email)->send(new CourseReminder($student));

        $email = $this->lastEmail();
        $this->assertSame('Продолжите с того места, где остановились - Pilot Academy', $email->getSubject());
        $this->assertStringContainsString('Вы ещё с нами', $email->getHtmlBody());
        $this->assertStringContainsString('Все права защищены.', $email->getHtmlBody());
        $this->assertSame('en', App::getLocale(), 'The request keeps its own language.');
    }

    public function test_a_certificate_email_is_in_the_students_language_with_the_translated_course_title(): void
    {
        $student = $this->person('fr');
        $certificate = $this->certificateFor($student);

        Mail::to($student->email)->send(new CertificateIssued($certificate));

        $email = $this->lastEmail();
        $this->assertSame('Votre certificat « Les bases du pilote »', $email->getSubject());
        $this->assertStringContainsString('Félicitations', $email->getHtmlBody());
        $this->assertStringContainsString('Vérifier le certificat', $email->getHtmlBody());
    }

    public function test_the_certificate_pdf_is_printed_in_the_students_language_whoever_regenerates_it(): void
    {
        Storage::fake('public');
        $student = $this->person('fr');
        $certificate = $this->certificateFor($student);

        // An English admin presses Regenerate PDF.
        $this->actingAs($this->person('en', User::ROLE_ADMIN));

        $printedIn = null;
        Pdf::shouldReceive('loadView')->once()->andReturnUsing(function () use (&$printedIn) {
            $printedIn = App::getLocale();

            $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
            $pdf->shouldReceive('setPaper')->andReturnSelf();
            $pdf->shouldReceive('output')->andReturn('%PDF-1.4');

            return $pdf;
        });

        app(IssueCertificate::class)->renderPdf($certificate);

        $this->assertSame('fr', $printedIn);
        $this->assertSame('en', App::getLocale());
    }

    public function test_the_certificate_wording_date_and_course_title_follow_the_language(): void
    {
        $certificate = $this->certificateFor($this->person('fr'));
        $certificate->forceFill(['issued_at' => '2026-09-15 10:00:00'])->save();

        $html = app(Translator::class)->inLocale('fr', fn (): string => view('certificates.pdf', [
            'certificate' => $certificate->fresh(['course']),
            'qr' => '',
            'background' => null,
            'logo' => null,
        ])->render());

        $this->assertStringContainsString('Certificat de réussite', $html);
        $this->assertStringContainsString('Les bases du pilote', $html);
        $this->assertStringContainsString('15 septembre 2026', $html);
    }

    public function test_someone_whose_language_is_switched_off_gets_the_default(): void
    {
        $student = $this->person('de');

        Mail::to($student->email)->send(new CourseReminder($student));

        $this->assertSame('Pick up where you left off - Pilot Academy', $this->lastEmail()->getSubject());
    }

    public function test_the_test_email_is_in_the_language_of_the_admin_who_sent_it(): void
    {
        $admin = $this->person('ru', User::ROLE_ADMIN);

        Mail::to($admin->email)->send(new MailCheckMessage($admin));

        $email = $this->lastEmail();
        $this->assertSame('Pilot Academy: тестовое письмо', $email->getSubject());
        $this->assertStringContainsString('Это тестовое письмо', $email->getHtmlBody());
    }
}
