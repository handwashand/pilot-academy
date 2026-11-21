<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Certificate extends Model
{
    /** Folder on the public disk holding the standard background (Settings → Certificate). */
    public const STANDARD_DIR = 'certificate-templates/standard';

    /** The standard background's path on the public disk, or null to use the built-in layout. */
    public static function standardTemplate(): ?string
    {
        return collect(Storage::disk('public')->files(self::STANDARD_DIR))
            ->first(fn (string $file): bool => (bool) preg_match('/\.(png|jpe?g)$/i', $file));
    }

    protected $fillable = [
        'user_id',
        'course_id',
        'quiz_attempt_id',
        'number',
        'name',
        'score_percent',
        'issued_at',
        'revoked_at',
        'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A certificate is a claim already made in public. Deleting the course
     * hides the course, not the fact that someone passed it, so the
     * certificate still names it.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }

    public function statusLabel(): string
    {
        return $this->isValid() ? __t('labels.certificate.valid') : __t('labels.certificate.revoked');
    }

    /** Public verification URL for this certificate. */
    public function verifyUrl(): string
    {
        return route('certificates.verify', $this->number);
    }
}
