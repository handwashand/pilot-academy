<?php

namespace Tests\Feature;

use App\Actions\IssueCertificate;
use App\Filament\Pages\SettingsCertificate;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Which certificate a course prints: its own uploaded background, else the
 * standard one from Settings → Certificate, else the built-in layout.
 */
class StandardCertificateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
    }

    private function certificate(?string $courseTemplate = null): Certificate
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@pilot.local', 'password' => 'password', 'role' => User::ROLE_LEARNER]);
        $course = Course::create(['title' => 'Pilot basics', 'slug' => 'pilot-basics', 'level' => 'beginner', 'certificate_template' => $courseTemplate]);

        return Certificate::create(['user_id' => $user->id, 'course_id' => $course->id, 'number' => 'PA-1', 'name' => 'Ana', 'score_percent' => 90, 'issued_at' => now()]);
    }

    /** Render the PDF and return the background the view was given. */
    private function backgroundFor(Certificate $certificate): ?string
    {
        $seen = 'not rendered';
        View::creator('certificates.pdf', function ($view) use (&$seen) {
            $seen = $view->getData()['background'] ?? null;
        });
        app(IssueCertificate::class)->renderPdf($certificate);

        return $seen;
    }

    public function test_with_nothing_uploaded_the_built_in_layout_is_used(): void
    {
        Storage::fake('public');

        $this->assertNull($this->backgroundFor($this->certificate()));
    }

    public function test_the_standard_background_is_used_by_a_course_without_its_own(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(Certificate::STANDARD_DIR.'/std.png', 'x');

        $this->assertSame(Storage::disk('public')->path(Certificate::STANDARD_DIR.'/std.png'), $this->backgroundFor($this->certificate()));
    }

    public function test_a_courses_own_background_wins_over_the_standard(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(Certificate::STANDARD_DIR.'/std.png', 'x');
        Storage::disk('public')->put('certificate-templates/own.png', 'x');

        $this->assertSame(Storage::disk('public')->path('certificate-templates/own.png'), $this->backgroundFor($this->certificate('certificate-templates/own.png')));
    }

    public function test_an_admin_uploads_a_new_standard_and_the_old_one_is_removed(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $page = Livewire::test(SettingsCertificate::class);

        // Added after the page loaded, so the field is empty: replacing a
        // filled FileUpload in a test trips Filament's own validation (see the
        // 2026-10-08 work log). The save must still clear it.
        Storage::disk('public')->put(Certificate::STANDARD_DIR.'/old.png', 'x');

        $page->set('data.background', UploadedFile::fake()->image('new.png', 1200, 850))
            ->call('save')
            ->assertHasNoErrors();

        $files = Storage::disk('public')->files(Certificate::STANDARD_DIR);
        $this->assertCount(1, $files);
        $this->assertNotSame(Certificate::STANDARD_DIR.'/old.png', $files[0]);
        $this->assertSame($files[0], Certificate::standardTemplate());
    }

    public function test_an_admin_opens_the_page(): void
    {
        $this->actingAs($this->admin())->get(SettingsCertificate::getUrl())->assertOk()->assertSee(__t('admin_settings.certificate.background'));
    }

    // One user per test: AuthenticateSession pins the session to the first.
    public function test_a_creator_cannot_open_the_page(): void
    {
        $creator = User::create(['name' => 'C', 'email' => 'c@pilot.local', 'password' => 'password', 'role' => User::ROLE_CREATOR]);
        $this->actingAs($creator)->get(SettingsCertificate::getUrl())->assertForbidden();
    }
}
