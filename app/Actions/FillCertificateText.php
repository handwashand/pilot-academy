<?php

namespace App\Actions;

use App\Models\Certificate;
use App\Services\Translator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * The words on a certificate, with their placeholders filled in.
 *
 * The five lines are ordinary interface strings (mail.certificate_pdf.*): the
 * shipped text in lang/{code}/mail.php, overridden per language by whatever
 * an admin typed under Settings → General → Certificate (the same rows Settings
 * → Translations edits). Placeholders are filled here, once, for the PDF and
 * the preview alike. Every value is escaped; only the markup this class adds
 * (bold course title, signature image) is HTML.
 *
 * The signature is never printed unless a line uses :signature or
 * :signature_title (owner, 2026-11-25).
 */
class FillCertificateText
{
    /** The editable lines, by their key under mail.certificate_pdf. */
    public const LINES = ['issuer', 'title', 'certifies', 'achievement_course', 'issued_by'];

    /** The signer's name and title are overrides only: nothing is shipped for them. */
    public const SIGNATURE_NAME = 'mail.certificate_pdf.signature_name';

    public const SIGNATURE_TITLE = 'mail.certificate_pdf.signature_title';

    /** Folder on the public disk holding the signature image, as its only file. */
    public const SIGNATURE_DIR = 'certificate-templates/signature';

    /** Every placeholder an admin can use, in the order the form lists them. */
    public const PLACEHOLDERS = ['learner', 'course', 'lessons', 'score', 'date', 'number', 'verify_link', 'partner', 'product', 'signature', 'signature_title'];

    /**
     * @param  string|null  $signatureSrc  How the signature image is referenced: an embedded image for dompdf, a URL for the browser preview.
     * @param  array<string, string|null>  $typed  Unsaved text from the settings form, for its preview: a line's key, signature_name or signature_title. Blank falls back as saved text does.
     * @return array<string, HtmlString> The five lines, keyed as in LINES.
     */
    public function handle(Certificate $certificate, int $lessons, ?string $signatureSrc = null, array $typed = []): array
    {
        $locale = app()->getLocale();
        $values = $this->values($certificate, $lessons, $locale, $signatureSrc, $typed);

        $lines = [];

        foreach (self::LINES as $line) {
            $raw = filled($typed[$line] ?? null) ? (string) $typed[$line] : __t("mail.certificate_pdf.{$line}");

            // ":score%" written before the score carried its own sign.
            $text = str_replace(':score%', ':score', $raw);

            // strtr tries the longest placeholder first, so :signature_title
            // is never read as :signature followed by "_title".
            $lines[$line] = new HtmlString(strtr(e($text), $values));
        }

        return $lines;
    }

    /** The saved signature image's path on the public disk, if there is one. */
    public static function signatureImage(): ?string
    {
        return Storage::disk('public')->files(self::SIGNATURE_DIR)[0] ?? null;
    }

    /**
     * An override from the translations table only — no shipped text, and no
     * readable key name when nothing is saved: in the reader's language, else
     * the default one.
     */
    public static function override(string $key, ?string $locale = null): string
    {
        $translator = app(Translator::class);

        foreach (array_unique([$locale ?? app()->getLocale(), $translator->defaultCode()]) as $code) {
            $value = $translator->bundle($code)[$key] ?? null;

            if (filled($value)) {
                return (string) $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, string|null>  $typed
     * @return array<string, string> ":placeholder" => escaped HTML
     */
    private function values(Certificate $certificate, int $lessons, string $locale, ?string $signatureSrc, array $typed): array
    {
        $signer = filled($typed['signature_name'] ?? null) ? (string) $typed['signature_name'] : self::override(self::SIGNATURE_NAME, $locale);
        $signerTitle = filled($typed['signature_title'] ?? null) ? (string) $typed['signature_title'] : self::override(self::SIGNATURE_TITLE, $locale);
        $image = $signatureSrc ? '<img class="signature-image" src="'.e($signatureSrc).'" alt=""><br>' : '';

        $values = [
            'learner' => e($certificate->name),
            'course' => '<strong>'.e($certificate->course?->translated('title', $locale)).'</strong>',
            'lessons' => e(__tc('academy.common.lessons', $lessons)),
            'score' => e(((int) $certificate->score_percent).'%'),
            'date' => e($certificate->issued_at?->locale($locale)->isoFormat('LL')),
            'number' => e($certificate->number),
            'verify_link' => e($certificate->number ? route('certificates.verify', $certificate->number) : ''),
            'partner' => e($certificate->user?->company?->name),
            'product' => e($certificate->course?->product?->name),
            'signature' => $signer !== '' || $image !== '' ? '<span class="signature">'.$image.e($signer).'</span>' : '',
            'signature_title' => e($signerTitle),
        ];

        $replacements = [];

        foreach ($values as $name => $html) {
            $replacements[':'.$name] = (string) $html;
        }

        return $replacements;
    }
}
