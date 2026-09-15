<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityEvent extends Model
{
    public const TYPE_LOGIN = 'login';

    public const TYPE_COURSE_OPENED = 'course_opened';

    public const TYPE_LESSON_OPENED = 'lesson_opened';

    public const TYPE_LESSON_COMPLETED = 'lesson_completed';

    public const TYPE_COURSE_COMPLETED = 'course_completed';

    /** Something staff did to the student, rather than something they did. */
    public const TYPE_REMINDER_SENT = 'reminder_sent';

    public const TYPE_LABELS = [
        self::TYPE_LOGIN => 'Logged in',
        self::TYPE_COURSE_OPENED => 'Opened course',
        self::TYPE_LESSON_OPENED => 'Opened lesson',
        self::TYPE_LESSON_COMPLETED => 'Completed lesson',
        self::TYPE_COURSE_COMPLETED => 'Completed course',
        self::TYPE_REMINDER_SENT => 'Sent a reminder',
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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Record an activity event for a user (no-op if no user). */
    public static function record(?User $user, string $type, ?string $label = null, ?string $url = null): void
    {
        if (! $user) {
            return;
        }

        static::create([
            'user_id' => $user->id,
            'type' => $type,
            'label' => $label,
            'url' => $url,
        ]);
    }
}
