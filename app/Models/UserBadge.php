<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A badge one student has earned. Awarded only by App\Actions\AwardBadges,
 * which reads what the academy already records; nothing here is typed in.
 */
class UserBadge extends Model
{
    public const FIRST_LESSON = 'first_lesson';

    public const FIRST_CERTIFICATE = 'first_certificate';

    public const PRODUCT_COMPLETE = 'product_complete';

    public const PERFECT_SCORE = 'perfect_score';

    /** In the order they are shown. */
    public const BADGES = [
        self::FIRST_LESSON,
        self::FIRST_CERTIFICATE,
        self::PRODUCT_COMPLETE,
        self::PERFECT_SCORE,
    ];

    protected $fillable = [
        'user_id',
        'badge',
        'subject',
        'awarded_at',
    ];

    protected $casts = [
        'awarded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The product or course a badge is for, if any; null once it has been deleted for good. */
    public function subjectModel(): ?Model
    {
        [$type, $id] = array_pad(explode(':', $this->subject, 2), 2, null);

        return match ($type) {
            'product' => Product::withTrashed()->find($id),
            'course' => Course::withTrashed()->find($id),
            default => null,
        };
    }

    /** "All courses done: GARM", in the reader's language. */
    public function label(): string
    {
        $subject = $this->subjectModel();
        $name = $subject instanceof Course ? $subject->translated('title') : $subject?->getAttribute('name');

        return __t("academy.badges.{$this->badge}.name", ['name' => (string) $name]);
    }

    public function description(): string
    {
        return __t("academy.badges.{$this->badge}.description");
    }
}
