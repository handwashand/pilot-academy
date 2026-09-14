<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The student profile: what a person may change about themselves, and the
 * password section that has to work for invite-link accounts nobody gave a
 * password to.
 */
class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private function learner(array $attributes = []): User
    {
        return User::create([
            'name' => 'Partner Student',
            'email' => 'student@partner.com',
            'password' => 'known-password',
            'role' => User::ROLE_LEARNER,
            ...$attributes,
        ]);
    }

    // --- The page ----------------------------------------------------------

    public function test_a_guest_is_sent_to_log_in(): void
    {
        $this->get(route('academy.profile'))->assertRedirect(route('login'));
    }

    public function test_the_partner_company_is_shown_but_cannot_be_edited(): void
    {
        $company = Company::create(['name' => 'Acme Logistics']);
        $learner = $this->learner(['company_id' => $company->id]);

        $this->actingAs($learner)
            ->get(route('academy.profile'))
            ->assertStatus(200)
            ->assertSee('Set by your administrator')
            ->assertSee('Acme Logistics')
            ->assertDontSee('name="company_id"', false);

        // Even a hand-crafted request cannot move them to another company.
        $this->actingAs($learner)->put(route('academy.profile.update'), [
            'name' => 'Partner Student',
            'email' => 'student@partner.com',
            'company_id' => Company::create(['name' => 'Other'])->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $learner->refresh();
        $this->assertSame($company->id, $learner->company_id);
        $this->assertTrue($learner->isLearner());
    }

    // --- Details -----------------------------------------------------------

    public function test_a_student_can_change_their_name_email_and_certificate_name(): void
    {
        $learner = $this->learner();

        $this->actingAs($learner)
            ->put(route('academy.profile.update'), [
                'name' => '  Ana Pereira ',
                'email' => 'ana@partner.com',
                'certificate_name' => ' Ana Maria Pereira ',
            ])
            ->assertRedirect(route('academy.profile'))
            ->assertSessionHasNoErrors();

        $learner->refresh();
        $this->assertSame('Ana Pereira', $learner->name);
        $this->assertSame('ana@partner.com', $learner->email);
        $this->assertSame('Ana Maria Pereira', $learner->certificate_name);

        $this->actingAs($learner)->get(route('academy.profile'))->assertSee('Your details are saved.');
    }

    public function test_an_empty_certificate_name_means_use_my_name(): void
    {
        $learner = $this->learner(['certificate_name' => 'Old Name']);

        $this->actingAs($learner)->put(route('academy.profile.update'), [
            'name' => 'Partner Student',
            'email' => 'student@partner.com',
            'certificate_name' => '',
        ]);

        $this->assertNull($learner->fresh()->certificate_name);
    }

    public function test_an_email_already_in_use_is_refused(): void
    {
        User::create(['name' => 'Someone', 'email' => 'taken@partner.com', 'password' => 'secret123', 'role' => User::ROLE_LEARNER]);
        $learner = $this->learner();

        $this->actingAs($learner)
            ->put(route('academy.profile.update'), ['name' => 'Partner Student', 'email' => 'taken@partner.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame('student@partner.com', $learner->fresh()->email);
    }

    public function test_changing_the_certificate_name_leaves_issued_certificates_alone(): void
    {
        $learner = $this->learner();
        $course = Course::create(['title' => 'GARM', 'slug' => 'garm', 'level' => 'beginner', 'status' => Course::STATUS_PUBLISHED]);
        $certificate = Certificate::create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'number' => 'PA-TEST-0001',
            'name' => 'Partner Student',
            'score_percent' => 90,
            'issued_at' => now(),
        ]);

        $this->actingAs($learner)->put(route('academy.profile.update'), [
            'name' => 'Partner Student',
            'email' => 'student@partner.com',
            'certificate_name' => 'A Different Name',
        ]);

        $this->assertSame('Partner Student', $certificate->fresh()->name);
    }

    // --- Passwords ---------------------------------------------------------

    public function test_a_student_who_joined_by_link_can_set_a_first_password(): void
    {
        $this->post(route('join'), ['name' => 'Link Student', 'email' => 'link@partner.com']);

        $learner = User::where('email', 'link@partner.com')->firstOrFail();
        $this->assertFalse($learner->hasOwnPassword(), 'An invite-link account has no password anyone knows.');

        $this->get(route('academy.profile'))
            ->assertSee('Set a password')
            ->assertDontSee('name="current_password"', false);

        $this->put(route('academy.profile.password'), [
            'password' => 'my-new-password',
            'password_confirmation' => 'my-new-password',
        ])->assertSessionHasNoErrors();

        $learner->refresh();
        $this->assertTrue(Hash::check('my-new-password', $learner->password));
        $this->assertTrue($learner->hasOwnPassword());

        $this->get(route('academy.profile'))
            ->assertSee('Change password')
            ->assertSee('name="current_password"', false);
    }

    public function test_changing_an_existing_password_needs_the_current_one(): void
    {
        $learner = $this->learner();
        $this->assertTrue($learner->hasOwnPassword());

        $this->actingAs($learner)
            ->put(route('academy.profile.password'), [
                'current_password' => 'wrong-guess',
                'password' => 'my-new-password',
                'password_confirmation' => 'my-new-password',
            ])
            ->assertSessionHasErrorsIn('password', 'current_password');

        $this->assertTrue(Hash::check('known-password', $learner->fresh()->password));

        $this->actingAs($learner)
            ->put(route('academy.profile.password'), [
                'current_password' => 'known-password',
                'password' => 'my-new-password',
                'password_confirmation' => 'my-new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('my-new-password', $learner->fresh()->password));
    }

    public function test_a_short_or_unconfirmed_password_is_refused(): void
    {
        $learner = $this->learner();

        $this->actingAs($learner)
            ->put(route('academy.profile.password'), [
                'current_password' => 'known-password',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrorsIn('password', 'password');
    }

    public function test_a_registered_student_has_their_own_password(): void
    {
        $this->post(route('register'), [
            'name' => 'Registered Student',
            'email' => 'registered@partner.com',
            'password' => 'registered-password',
            'password_confirmation' => 'registered-password',
        ]);

        $this->assertTrue(User::where('email', 'registered@partner.com')->firstOrFail()->hasOwnPassword());
    }
}
