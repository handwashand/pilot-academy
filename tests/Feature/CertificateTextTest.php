<?php

namespace Tests\Feature;

use App\Actions\FillCertificateText;
use App\Actions\IssueCertificate;
use App\Filament\Pages\SettingsCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Translation;
use App\Models\User;
use App\Services\Translator;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Settings → Certificate: admins type the certificate's words, per language,
 * with placeholders; an empty box keeps the standard text. A signature is
 * printed only where a line uses :signature. No score seal (owner, 2026-11-25).
 */
class CertificateTextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
        Storage::fake('public');
        Storage::fake('local');
    }

    private function admin(): User
    {
        app()->setLocale('en');

        return User::firstOrCreate(['email' => 'admin@pilot.local'], ['name' => 'Pilot Admin', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
    }

    private function certificate(string $locale = 'en'): Certificate
    {
        $user = User::create(['name' => 'Ana', 'email' => "ana-{$locale}@pilot.local", 'password' => 'password', 'role' => User::ROLE_LEARNER, 'locale' => $locale]);
        $course = Course::create(['title' => 'Mastering Sensors', 'slug' => 'mastering-sensors-'.$locale, 'level' => 'beginner']);

        foreach (['One', 'Two', 'Three'] as $i => $title) {
            $course->lessons()->create(['title' => $title, 'slug' => "lesson-{$locale}-{$i}", 'status' => Lesson::STATUS_PUBLISHED]);
        }

        return Certificate::create(['user_id' => $user->id, 'course_id' => $course->id, 'number' => 'PA-1-2026-ABC123', 'name' => 'Ana Mensah', 'score_percent' => 92, 'issued_at' => now()]);
    }

    /** The HTML dompdf was given for a certificate. */
    private function pdfHtml(Certificate $certificate): string
    {
        $seen = [];
        View::creator('certificates.pdf', function ($view) use (&$seen) {
            $seen = $view->getData();
        });
        app(IssueCertificate::class)->renderPdf($certificate);

        return view('certificates.pdf', $seen)->render();
    }

    private function saveText(string $code, string $line, ?string $value): void
    {
        $language = Language::where('code', $code)->firstOrFail();
        $value === null
            ? Translation::where(['key' => "mail.certificate_pdf.{$line}", 'language_id' => $language->id])->delete()
            : Translation::updateOrCreate(['key' => "mail.certificate_pdf.{$line}", 'language_id' => $language->id], ['value' => $value]);
        app(Translator::class)->clearBundleCache($code);
    }

    public function test_the_standard_text_reads_as_before_without_the_score_seal(): void
    {
        $html = $this->pdfHtml($this->certificate());

        $this->assertStringContainsString('has successfully completed the course <strong>Mastering Sensors</strong> (3 lessons) and passed the final assessment with a score of 92%.', $html);
        $this->assertStringContainsString('Certificate of Completion', $html);
        $this->assertStringNotContainsString('seal-', $html);
        // The verification link and QR are still printed.
        $this->assertStringContainsString(route('certificates.verify', 'PA-1-2026-ABC123'), $html);
    }

    public function test_an_admin_types_the_text_and_placeholders_are_filled_and_escaped(): void
    {
        Livewire::actingAs($this->admin())
            ->test(SettingsCertificate::class)
            ->fillForm(['text' => ['en' => [
                'title' => 'Certificate of Achievement',
                'achievement_course' => '<b>Well done</b> :learner on :course — :score, :lessons. Check: :verify_link',
            ]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $html = $this->pdfHtml($this->certificate());

        $this->assertStringContainsString('Certificate of Achievement', $html);
        $this->assertStringContainsString('&lt;b&gt;Well done&lt;/b&gt; Ana Mensah on <strong>Mastering Sensors</strong> — 92%, 3 lessons.', $html);
        $this->assertStringContainsString('Check: '.route('certificates.verify', 'PA-1-2026-ABC123'), $html);
        $this->assertStringNotContainsString('<b>Well done</b>', $html);
    }

    public function test_each_language_keeps_its_own_text_and_an_empty_box_brings_the_standard_back(): void
    {
        $this->saveText('en', 'title', 'Certificate of Achievement');

        // A French student still gets the French standard title.
        $this->assertStringContainsString('Certificat de réussite', $this->pdfHtml($this->certificate('fr')));

        Livewire::actingAs($this->admin())
            ->test(SettingsCertificate::class)
            ->assertSchemaStateSet(['text.en.title' => 'Certificate of Achievement'])
            ->fillForm(['text' => ['en' => ['title' => '']]])
            ->call('save');

        $this->assertSame(0, Translation::where('key', 'mail.certificate_pdf.title')->count());
    }

    public function test_a_score_written_with_its_own_percent_sign_prints_it_once(): void
    {
        $this->saveText('en', 'achievement_course', 'scored :score% on :course');

        $this->assertStringContainsString('scored 92% on', $this->pdfHtml($this->certificate()));
    }

    public function test_the_signature_is_only_printed_where_a_line_uses_it(): void
    {
        Livewire::actingAs($this->admin())
            ->test(SettingsCertificate::class)
            ->fillForm([
                'signature_name' => 'Jane Okoro',
                'signature_title' => 'Head of Training',
                'signature_image' => [UploadedFile::fake()->image('signature.png', 300, 100)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull(FillCertificateText::signatureImage());

        $html = $this->pdfHtml($this->certificate());
        $this->assertStringNotContainsString('Jane Okoro', $html);
        $this->assertStringNotContainsString('signature-image', $html);

        $this->saveText('en', 'issued_by', 'Signed :signature, :signature_title');

        $html = $this->pdfHtml(Certificate::first());
        $this->assertStringContainsString('Jane Okoro', $html);
        $this->assertStringContainsString('Head of Training', $html);
        $this->assertStringContainsString('class="signature-image" src="data:image/png;base64,', $html);
    }

    public function test_the_page_previews_the_certificate_and_reset_restores_the_standard_text(): void
    {
        $this->saveText('en', 'title', 'Certificate of Achievement');

        Livewire::actingAs($this->admin())
            ->test(SettingsCertificate::class)
            ->assertOk()
            ->assertSee(__t('admin_settings.certificate.sample_name'))
            ->assertSee('Certificate of Achievement')
            ->assertSee(':signature_title')
            ->callAction(TestAction::make('resetText')->schemaComponent());

        $this->assertSame(0, Translation::where('key', 'like', 'mail.certificate_pdf.%')->count());
    }

    public function test_only_admins_can_open_it(): void
    {
        $this->actingAs(User::create(['name' => 'C', 'email' => 'c@pilot.local', 'password' => 'password', 'role' => User::ROLE_CREATOR]))
            ->get(SettingsCertificate::getUrl())
            ->assertForbidden();
    }
}
