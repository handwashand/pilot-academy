<?php

namespace App\Actions;

use App\Mail\CourseReminder;
use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Email a student who has gone quiet, and log that we did.
 *
 * The log matters as much as the email: without it nobody can tell who has
 * already been chased, and the same student gets the same nudge from three
 * different admins in a week.
 */
class RemindStudent
{
    /** Nudging again inside this many days is treated as too soon. */
    public const COOLDOWN_DAYS = 7;

    /** Returns false when the student was skipped rather than emailed. */
    public function handle(User $student): bool
    {
        if (! $this->canRemind($student)) {
            return false;
        }

        // Mint the access token here, deliberately, rather than leaving it to
        // happen as a side effect of rendering the email body — that only runs
        // if and when the mail is actually rendered.
        $student->ensureLoginToken();

        Mail::to($student->email)->send(new CourseReminder($student));

        ActivityEvent::record($student, ActivityEvent::TYPE_REMINDER_SENT, 'Reminder to continue');

        return true;
    }

    /** Learners only, only with an email, and not twice inside the cooldown. */
    public function canRemind(User $student): bool
    {
        if (! $student->isLearner() || blank($student->email)) {
            return false;
        }

        $last = $this->lastRemindedAt($student);

        return $last === null || $last->lte(now()->subDays(self::COOLDOWN_DAYS));
    }

    public function lastRemindedAt(User $student): ?Carbon
    {
        $at = $student->activities()
            ->where('type', ActivityEvent::TYPE_REMINDER_SENT)
            ->max('created_at');

        return $at ? Carbon::parse($at) : null;
    }
}
