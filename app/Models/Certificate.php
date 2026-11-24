<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /** The signatory's name and title, on the private local disk (Settings → Certificate). */
    public const SIGNATORY_FILE = 'certificate-templates/signatory.json';

    /** Folder on the public disk holding the signature image, the only file there. */
    public const SIGNATURE_DIR = 'certificate-templates/signature';

    /**
     * Who signs every certificate, or null when nobody is set: then no
     * signature block is printed, rather than a placeholder.
     *
     * @return array{name: string, title: string, signature: ?string}|null
     */
    public static function signatory(): ?array
    {
        $saved = json_decode((string) (Storage::disk('local')->get(self::SIGNATORY_FILE) ?? ''), true);
        $name = trim((string) ($saved['name'] ?? ''));

        if ($name === '') {
            return null;
        }

        return [
            'name' => $name,
            'title' => trim((string) ($saved['title'] ?? '')),
            'signature' => collect(Storage::disk('public')->files(self::SIGNATURE_DIR))
                ->first(fn (string $file): bool => (bool) preg_match('/\.png$/i', $file)),
        ];
    }

    /** A certificate for one course, earned by passing its final quiz. */
    public const KIND_COURSE = 'course';

    /** The all-courses certificate: every published course finished. No course, no score. */
    public const KIND_ACADEMY = 'academy';

    protected $fillable = [
        'user_id',
        'course_id',
        'kind',
        'covered_course_ids',
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
        'covered_course_ids' => 'array',
    ];

    /**
     * Course certificates only. Anything that reads a certificate as "this
     * student passed this course" (dashboards, quiz health, completion) goes
     * through this, so an all-courses certificate is never counted as one.
     */
    public function scopeForCourses(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_COURSE);
    }

    public function scopeForAcademy(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_ACADEMY);
    }

    public function isAcademy(): bool
    {
        return $this->kind === self::KIND_ACADEMY;
    }

    /** What it certifies, in the given (or current) language: the course, or the whole academy. */
    public function title(?string $locale = null): string
    {
        return $this->isAcademy()
            ? __t('mail.certificate_pdf.academy_title')
            : (string) $this->course?->translated('title', $locale ?? app()->getLocale());
    }

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
