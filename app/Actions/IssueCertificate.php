<?php

namespace App\Actions;

use App\Mail\CertificateIssued;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Translator;
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

        // The course's own background, else the standard one from Settings →
        // Certificate, else none: the built-in layout is drawn.
        $background = null;
        foreach ([$certificate->course->certificate_template, Certificate::standardTemplate()] as $template) {
            if ($template && Storage::disk('public')->exists($template)) {
                $background = Storage::disk('public')->path($template);
                break;
            }
        }

        // The company's original blue PILOT lockup, restored byte for byte from
        // before "ACADEMY" was added: the certificate is issued by Pilot
        // Telematics, so it carries the company mark (owner's choice, 2026-11-24).
        //
        // Embedded rather than linked, so dompdf resolves it without filesystem
        // access — exactly as the QR does above.
        $logo = $this->embedPng(public_path('img/pilot-telematics-logo-blue.png'));

        // What was achieved, counted when the PDF is drawn. No study time on
        // purpose (owner, 2026-11-24): lessons and score only.
        $lessons = $certificate->course->publishedLessons()->count();

        // Printed in the student's language, whoever pressed Regenerate. The
        // view is rendered here, so the locale has to be set around it.
        $translator = app(Translator::class);

        $output = $translator->inLocale($translator->localeFor($certificate->user), fn (): string => Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'qr' => base64_encode($qrSvg),
            'background' => $background,
            'logo' => $logo,
            'lessons' => $lessons,
        ])->setPaper('a4', 'landscape')->output());

        $path = "certificates/{$certificate->number}.pdf";
        Storage::disk('public')->put($path, $output);

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

    private function embedPng(string $path): ?string
    {
        return is_file($path) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($path)) : null;
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
