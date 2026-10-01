<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityEvent extends Model
{
    public const TYPE_LOGIN = 'login';

    public const TYPE_COURSE_OPENED = 'course_opened';

    public const TYPE_LESSON_OPENED = 'lesson_opened';

    public const TYPE_LESSON_COMPLETED = 'lesson_completed';

    public const TYPE_COURSE_COMPLETED = 'course_completed';

    /** Something staff did to the student, rather than something they did. */
    public const TYPE_REMINDER_SENT = 'reminder_sent';

    public const TYPE_CASE_STUDY_OPENED = 'case_study_opened';

    public const TYPE_TUTORIAL_OPENED = 'tutorial_opened';

    public const TYPE_WEBINAR_OPENED = 'webinar_opened';

    public const TYPE_WEBINAR_JOINED = 'webinar_joined';

    public const TYPE_WEBINAR_RECORDING_OPENED = 'webinar_recording_opened';

    /** Actions performed by a learner, excluding staff-triggered reminders. */
    public const LEARNER_ACTIVITY_TYPES = [
        self::TYPE_LOGIN,
        self::TYPE_COURSE_OPENED,
        self::TYPE_LESSON_OPENED,
        self::TYPE_LESSON_COMPLETED,
        self::TYPE_COURSE_COMPLETED,
    ];

    public const TYPE_LABELS = [
        self::TYPE_LOGIN => 'Logged in',
        self::TYPE_COURSE_OPENED => 'Opened course',
        self::TYPE_LESSON_OPENED => 'Opened lesson',
        self::TYPE_LESSON_COMPLETED => 'Completed lesson',
        self::TYPE_COURSE_COMPLETED => 'Completed course',
        self::TYPE_REMINDER_SENT => 'Sent a reminder',
        self::TYPE_CASE_STUDY_OPENED => 'Opened case study',
        self::TYPE_TUTORIAL_OPENED => 'Opened tutorial',
        self::TYPE_WEBINAR_OPENED => 'Opened webinar',
        self::TYPE_WEBINAR_JOINED => 'Joined webinar',
        self::TYPE_WEBINAR_RECORDING_OPENED => 'Opened webinar recording',
    ];

    /** @return array<string, string> The types in the reader's language (lang/{code}/labels.php). */
    public static function typeLabels(): array
    {
        return collect(self::TYPE_LABELS)
            ->mapWithKeys(fn (string $english, string $type): array => [$type => __t("labels.activity.{$type}")])
            ->all();
    }

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'url',
        'subject_type',
        'subject_id',
        'course_id',
        'product_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Record an activity event for a user (no-op if no user). */
    public static function record(
        ?User $user,
        string $type,
        ?string $label = null,
        ?string $url = null,
        ?Model $subject = null,
        ?Course $course = null,
    ): void {
        if (! $user) {
            return;
        }

        static::create([
            'user_id' => $user->id,
            'type' => $type,
            'label' => $label,
            'url' => $url,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'course_id' => $course?->getKey() ?? ($subject instanceof Course ? $subject->getKey() : null),
            'product_id' => $course?->product_id ?? $subject?->getAttribute('product_id'),
        ]);
    }
}
