<?php

namespace App\Actions;

use App\Mail\CertificateIssued;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class IssueCertificate
{
    /**
     * Issue (or return the existing valid) certificate for a passed final quiz,
     * render its PDF, and email it to the student.
     */
    public function handle(User $user, Course $course, QuizAttempt $attempt): Certificate
    {
        // One valid certificate per student + course. Certificates are permanent.
        $existing = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNull('revoked_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'quiz_attempt_id' => $attempt->id,
            'number' => $this->uniqueNumber($course),
            'name' => $user->certificate_name ?: $user->name,
            'score_percent' => $attempt->scorePercent() ?? 0,
            'issued_at' => now(),
        ]);

        // A rendering/mail hiccup must never lose a certificate the student earned.
        try {
            $this->renderPdf($certificate);
        } catch (\Throwable $e) {
            Log::warning('Certificate PDF render failed', [
                'certificate' => $certificate->number,
                'error' => $e->getMessage(),
            ]);
        }

        $this->email($certificate);

        return $certificate;
    }

    /** Regenerate and store the certificate PDF; returns the storage path. */
    public function renderPdf(Certificate $certificate): string
    {
        $certificate->loadMissing('user', 'course');

        $qrSvg = QrCode::format('svg')->size(220)->margin(0)->errorCorrection('M')
            ->generate($certificate->verifyUrl());

        $background = null;
        if ($certificate->course->certificate_template
            && Storage::disk('public')->exists($certificate->course->certificate_template)
        ) {
            $background = Storage::disk('public')->path($certificate->course->certificate_template);
        }

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'qr' => base64_encode($qrSvg),
            'background' => $background,
        ])->setPaper('a4', 'landscape');

        $path = "certificates/{$certificate->number}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $certificate->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    /** Email the certificate to the student. Failures never block issuance. */
    public function email(Certificate $certificate): void
    {
        $certificate->loadMissing('user', 'course');

        try {
            Mail::to($certificate->user->email)->send(new CertificateIssued($certificate));
        } catch (\Throwable $e) {
            Log::warning('Certificate email failed', [
                'certificate' => $certificate->number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Generate a unique, human-readable certificate number. */
    private function uniqueNumber(Course $course): string
    {
        do {
            $number = sprintf('PA-%d-%s-%s', $course->id, now()->year, strtoupper(Str::random(6)));
        } while (Certificate::where('number', $number)->exists());

        return $number;
    }
}
