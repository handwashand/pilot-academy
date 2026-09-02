<?php

namespace Tests\Feature;

use App\Actions\RemindStudent;
use App\Filament\Widgets\StalledLearners;
use App\Mail\CourseReminder;
use App\Models\ActivityEvent;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RemindStudentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
        Mail::fake();
    }

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin', 'password' => bcrypt('password'), 'role' => User::ROLE_ADMIN,
        ]);
    }

    /** A learner who started a month ago and stopped — the widget's target. */
    private function quietLearner(string $email = 'quiet@partner.com'): User
    {
        $learner = User::firstOrCreate(['email' => $email], [
            'name' => 'Quiet Learner', 'password' => bcrypt('secret'), 'role' => User::ROLE_LEARNER,
        ]);

        $learner->completedLessons()->syncWithoutDetaching([
            Lesson::first()->id => ['completed_at' => now()->subMonth()],
        ]);

        return $learner;
    }

    private function remind(): RemindStudent
    {
        return app(RemindStudent::class);
    }

    // --- Sending -----------------------------------------------------------

    public function test_it_emails_the_student_and_logs_the_nudge(): void
    {
        $learner = $this->quietLearner();

        $this->assertTrue($this->remind()->handle($learner));

        Mail::assertSent(CourseReminder::class, fn (CourseReminder $mail): bool => $mail->hasTo($learner->email));

        // The log is what stops three admins chasing the same person.
        $this->assertDatabaseHas('activity_events', [
            'user_id' => $learner->id,
            'type' => ActivityEvent::TYPE_REMINDER_SENT,
        ]);
    }

    public function test_the_email_carries_a_working_personal_link(): void
    {
        $learner = $this->quietLearner();
        $this->remind()->handle($learner);

        $token = $learner->fresh()->login_token;
        $this->assertNotNull($token, 'A student invited before tokens existed must still get one.');

        // Following it signs them in and lands them on the home page, where
        // "Continue where you left off" takes over.
        $this->get(route('academy.enter', $token))->assertRedirect(route('academy.home'));
        $this->assertAuthenticatedAs($learner->fresh());
    }

    // --- The cooldown ------------------------------------------------------

    public function test_it_refuses_to_nudge_the_same_student_twice_in_a_week(): void
    {
        $learner = $this->quietLearner();

        $this->assertTrue($this->remind()->handle($learner));
        $this->assertFalse($this->remind()->handle($learner));

        Mail::assertSentCount(1);
        $this->assertSame(1, ActivityEvent::where('type', ActivityEvent::TYPE_REMINDER_SENT)->count());
    }

    public function test_it_nudges_again_once_the_cooldown_has_passed(): void
    {
        $learner = $this->quietLearner();
        $this->remind()->handle($learner);

        ActivityEvent::where('type', ActivityEvent::TYPE_REMINDER_SENT)
            ->update(['created_at' => now()->subDays(RemindStudent::COOLDOWN_DAYS + 1)]);

        $this->assertTrue($this->remind()->handle($learner));
        Mail::assertSentCount(2);
    }

    // --- Who is eligible ---------------------------------------------------

    public function test_staff_are_never_nudged(): void
    {
        $admin = $this->admin();
        $creator = User::create([
            'name' => 'Creator', 'email' => 'creator@pilot.local',
            'password' => bcrypt('x'), 'role' => User::ROLE_CREATOR,
        ]);

        $this->assertFalse($this->remind()->handle($admin));
        $this->assertFalse($this->remind()->handle($creator));
        Mail::assertNothingSent();
    }

    // --- Through the dashboard ---------------------------------------------

    public function test_an_admin_can_send_a_reminder_from_the_widget(): void
    {
        $learner = $this->quietLearner();

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->callAction(TestAction::make('remind')->table($learner))
            ->assertHasNoActionErrors();

        Mail::assertSent(CourseReminder::class);
    }

    public function test_the_button_disappears_once_someone_has_been_reminded(): void
    {
        $learner = $this->quietLearner();

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertActionVisible(TestAction::make('remind')->table($learner));

        $this->remind()->handle($learner);

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertActionHidden(TestAction::make('remind')->table($learner));
    }

    public function test_bulk_reminders_skip_anyone_already_chased(): void
    {
        $fresh = $this->quietLearner('fresh@partner.com');
        $chased = $this->quietLearner('chased@partner.com');

        $this->remind()->handle($chased);
        Mail::fake();   // reset, so the count below is only the bulk run

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->selectTableRecords([$fresh->id, $chased->id])
            ->callAction(TestAction::make('remind')->table()->bulk());

        // Only the one still eligible.
        Mail::assertSentCount(1);
        Mail::assertSent(CourseReminder::class, fn (CourseReminder $mail): bool => $mail->hasTo($fresh->email));
    }

    public function test_a_reminder_does_not_count_as_student_activity(): void
    {
        $learner = $this->quietLearner();
        $this->remind()->handle($learner);

        // Being chased is not progress: they stay on the list until they act.
        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertCanSeeTableRecords([$learner]);
    }

    public function test_finishing_after_a_nudge_takes_them_off_the_list(): void
    {
        $learner = $this->quietLearner();
        $this->remind()->handle($learner);

        $learner->completedLessons()->syncWithoutDetaching([
            Course::first()->publishedLessons()->skip(1)->first()->id => ['completed_at' => now()],
        ]);

        Livewire::actingAs($this->admin())
            ->test(StalledLearners::class)
            ->assertCanNotSeeTableRecords([$learner]);
    }
}
