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
 * The Certificates list: Preview is the one visible button, the rest sit in
 * the row menu, and Delete is a per-account right on top of being an admin.
 */
class CertificateActionsTest extends TestCase
{
    use RefreshDatabase;

    private function certificate(): Certificate
    {
        $learner = User::create(['name' => 'Ana', 'email' => 'ana@partner.com', 'password' => 'secret123', 'role' => User::ROLE_LEARNER]);
        $course = Course::create(['title' => 'GARM', 'slug' => 'garm', 'level' => 'beginner', 'status' => Course::STATUS_PUBLISHED]);

        Storage::disk('public')->put('certificates/PA-TEST-0001.pdf', '%PDF-1.4');

        return Certificate::create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'number' => 'PA-TEST-0001',
            'name' => 'Ana',
            'score_percent' => 90,
            'issued_at' => now(),
            'pdf_path' => 'certificates/PA-TEST-0001.pdf',
        ]);
    }

    private function admin(bool $canDelete = false): User
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN]);

        if ($canDelete) {
            $admin->permissions()->create(['permission' => User::PERMISSION_CERTIFICATES_DELETE]);
        }

        return $admin;
    }

    public function test_preview_opens_the_pdf_in_a_new_tab(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->test(ListCertificates::class)
            ->assertActionVisible(TestAction::make('preview')->table($certificate))
            ->assertActionHasUrl(TestAction::make('preview')->table($certificate), Storage::disk('public')->url($certificate->pdf_path))
            ->assertActionShouldOpenUrlInNewTab(TestAction::make('preview')->table($certificate));
    }

    public function test_an_admin_without_the_right_cannot_delete(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->test(ListCertificates::class)
            ->assertActionHidden(TestAction::make('delete')->table($certificate));

        $this->assertModelExists($certificate);
    }

    public function test_an_admin_with_the_right_deletes_the_certificate_and_its_pdf(): void
    {
        Storage::fake('public');
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin(canDelete: true))
            ->test(ListCertificates::class)
            ->callAction(TestAction::make('delete')->table($certificate));

        $this->assertModelMissing($certificate);
        Storage::disk('public')->assertMissing('certificates/PA-TEST-0001.pdf');
        // The public check page now reports that no such certificate exists.
        $this->get('/certificates/PA-TEST-0001')->assertOk()->assertViewHas('certificate', null);
    }

    public function test_the_right_does_nothing_for_a_creator(): void
    {
        $creator = User::create(['name' => 'C', 'email' => 'c@pilot.local', 'password' => 'x', 'role' => User::ROLE_CREATOR]);
        $creator->permissions()->create(['permission' => User::PERMISSION_CERTIFICATES_DELETE]);

        $this->assertFalse($creator->canDeleteCertificates());
    }
}
