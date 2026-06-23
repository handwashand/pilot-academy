<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityEvent extends Model
{
    public const TYPE_LOGIN = 'login';

    public const TYPE_COURSE_OPENED = 'course_opened';

    public const TYPE_LESSON_OPENED = 'lesson_opened';

    public const TYPE_LABELS = [
        self::TYPE_LOGIN => 'Logged in',
        self::TYPE_COURSE_OPENED => 'Opened course',
        self::TYPE_LESSON_OPENED => 'Opened lesson',
    ];

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
