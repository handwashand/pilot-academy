<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\User;
use App\Services\Translator;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Tells a course's owners, in the panel's notification bell, when a change
 * leaves it broken for students.
 *
 * Owners are the creators assigned to the course's product; with none, the
 * admins. The person who made the change is not told — the edit page they are
 * on already says so. Each problem is notified once while the alert is unread,
 * and an alert whose problem has since been fixed is marked read, so the bell
 * never lists something already mended.
 *
 * The alert is stored as text, so it is written in the owner's language — not
 * the language of whoever made the change.
 */
class NotifyContentOwners
{
    public function __construct(private FindContentProblems $find) {}

    /**
     * Check a course once, after the request that changed it.
     *
     * Not straight away: a lesson form saves the lesson before its questions and
     * options, so a check at that moment sees a brand-new lesson with no
     * questions and would report a problem that is not there. Named, so the
     * dozens of saves one form makes collapse into a single check.
     */
    public static function afterRequest(mixed $courseId): void
    {
        if (! $courseId) {
            return;
        }

        defer(fn () => app(static::class)->handle((int) $courseId), 'notify-content-owners-'.$courseId);
    }

    public function handle(int $courseId): void
    {
        $course = Course::find($courseId);

        if (! $course) {
            return;
        }

        $problems = $this->find->forCourse($course)->keyBy('key');
        $actorId = auth()->id();

        foreach ($this->ownersOf($course) as $owner) {
            $alerts = $this->unreadAlertsFor($owner, $course->id);

            // Fixed since the alert went out: take it off the bell.
            $alerts
                ->reject(fn (DatabaseNotification $alert): bool => $problems->has($alert->data['viewData']['content_problem_key'] ?? ''))
                ->each->markAsRead();

            if ($owner->getKey() === $actorId) {
                continue;
            }

            $alreadyTold = $alerts->map(fn (DatabaseNotification $alert) => $alert->data['viewData']['content_problem_key'] ?? null)->filter();

            if ($problems->keys()->diff($alreadyTold)->isEmpty()) {
                continue;
            }

            $this->inLanguageOf($owner, function () use ($course, $owner, $alreadyTold): void {
                foreach ($this->find->forCourse($course) as $problem) {
                    if ($alreadyTold->contains($problem['key'])) {
                        continue;
                    }

                    Notification::make()
                        ->title($problem['what'])
                        ->body($problem['name'].'. '.$problem['fix'])
                        ->icon('heroicon-o-exclamation-triangle')
                        ->iconColor($problem['severity'])
                        ->viewData(['content_problem_key' => $problem['key'], 'content_course_id' => $course->id])
                        ->actions([
                            Action::make('fix')->label(__t('admin_pages.content_health.fix'))->url($problem['url'])->markAsRead(),
                        ])
                        ->sendToDatabase($owner);
                }
            });
        }
    }

    /** @return Collection<int, User> */
    public function ownersOf(Course $course): Collection
    {
        $creators = $course->product_id
            ? User::query()
                ->where('role', User::ROLE_CREATOR)
                ->whereHas('products', fn ($products) => $products->whereKey($course->product_id))
                ->get()
            : collect();

        return $creators->isNotEmpty()
            ? $creators
            : User::query()->where('role', User::ROLE_ADMIN)->get();
    }

    /** Run $callback with the app in the user's language, then put the request's back. */
    private function inLanguageOf(User $user, Closure $callback): void
    {
        $translator = app(Translator::class);

        $translator->inLocale($translator->localeFor($user), $callback);
    }

    /**
     * Read in PHP rather than with a JSON query: the notifications data column
     * is text, and PostgreSQL refuses JSON operators on text.
     */
    private function unreadAlertsFor(User $owner, int $courseId): Collection
    {
        return $owner->unreadNotifications()->get()
            ->filter(fn (DatabaseNotification $alert): bool => (int) ($alert->data['viewData']['content_course_id'] ?? 0) === $courseId)
            ->values();
    }
}
