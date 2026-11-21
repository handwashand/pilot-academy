<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf: absolute positioning in mm on an A4 landscape page (297x210).
           Brand palette only — navy #0a2540, Pilot blue #1463ff, amber #FFB900
           (the mark's own colour). */
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'DejaVu Serif', 'Times New Roman', serif; color: #0a2540; }

        .page { position: relative; width: 297mm; height: 210mm; overflow: hidden; background: #fbfaf6; }
        .bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }

        /* Frame: a navy band, an amber hairline inside it, and blue corner brackets. */
        .band { position: absolute; top: 7mm; left: 7mm; width: 279mm; height: 192mm;
                border: 2.6mm solid #0a2540; }
        .hair { position: absolute; top: 12.5mm; left: 12.5mm; width: 271mm; height: 184mm;
                border: 0.35mm solid #FFB900; }
        .corner { position: absolute; width: 12mm; height: 12mm; border-color: #1463ff; border-style: solid; }
        .c-tl { top: 16mm; left: 16mm; border-width: 0.9mm 0 0 0.9mm; }
        .c-tr { top: 16mm; left: 268mm; border-width: 0.9mm 0.9mm 0 0; }
        .c-bl { top: 181mm; left: 16mm; border-width: 0 0 0.9mm 0.9mm; }
        .c-br { top: 181mm; left: 268mm; border-width: 0 0.9mm 0.9mm 0; }

        /* 1920x604 lockup, kept to ratio: 54mm wide is 17mm tall. Centred. */
        .logo { position: absolute; top: 22mm; left: 121.5mm; width: 54mm; height: 17mm; }

        .center { position: absolute; left: 30mm; width: 237mm; text-align: center; }

        /* The title sits under the logo, not beside it: a long translation —
           Russian "СЕРТИФИКАТ О ПРОХОЖДЕНИИ КУРСА" — must have the full width. */
        .eyebrow { top: 47mm; font-family: 'DejaVu Sans', sans-serif; font-size: 15pt; font-weight: bold;
                   letter-spacing: 4pt; text-transform: uppercase; color: #0a2540; }
        .rule { position: absolute; height: 0.5mm; background: #FFB900; }
        .rule-l { top: 59mm; left: 108.5mm; width: 30mm; }
        .rule-r { top: 59mm; left: 158.5mm; width: 30mm; }
        .dot { position: absolute; top: 57.9mm; left: 146.9mm; width: 2.6mm; height: 2.6mm;
               background: #1463ff; border-radius: 1.3mm; }

        .lead { font-size: 12pt; font-style: italic; color: #64748b; }
        .lead-1 { top: 67mm; }
        .name { top: 75mm; font-size: 36pt; font-weight: bold; color: #0a2540; }
        .name-rule { position: absolute; top: 95mm; left: 78.5mm; width: 140mm; height: 0.3mm; background: #0a2540; }
        .lead-2 { top: 100mm; }
        .course { top: 108mm; font-size: 21pt; font-weight: bold; color: #1463ff; }

        /* Bottom row: details left, seal centre, QR right. */
        .meta { position: absolute; top: 158mm; left: 26mm; width: 85mm;
                font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #64748b; }
        .meta .label { font-size: 7pt; letter-spacing: 1.5pt; text-transform: uppercase; color: #94a3b8; }
        .meta .value { font-size: 11pt; font-weight: bold; color: #0a2540; margin-bottom: 2.5mm; }
        .meta .url { font-size: 7pt; color: #94a3b8; }

        .seal-o { position: absolute; top: 146mm; left: 129.5mm; width: 38mm; height: 38mm;
                  border-radius: 19mm; background: #0a2540; }
        .seal-i { position: absolute; top: 148.5mm; left: 132mm; width: 31.6mm; height: 31.6mm;
                  border-radius: 16mm; border: 0.4mm solid #FFB900; }
        .seal-t { position: absolute; left: 129.5mm; width: 38mm; text-align: center;
                  font-family: 'DejaVu Sans', sans-serif; color: #ffffff; }
        .seal-1 { top: 155mm; font-size: 6.5pt; letter-spacing: 1.5pt; }
        .seal-2 { top: 160mm; font-size: 14pt; font-weight: bold; color: #FFB900; }
        .seal-3 { top: 169mm; font-size: 6.5pt; letter-spacing: 1.5pt; }

        .qr { position: absolute; top: 152mm; left: 237mm; width: 30mm; height: 30mm; }
        .qr-caption { position: absolute; top: 183mm; left: 230mm; width: 44mm; text-align: center;
                      font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="page">
        @if($background)
            <img class="bg" src="{{ $background }}">
        @else
            <div class="band"></div>
            <div class="hair"></div>
            <div class="corner c-tl"></div>
            <div class="corner c-tr"></div>
            <div class="corner c-bl"></div>
            <div class="corner c-br"></div>
        @endif

        {{-- A custom background is expected to carry its own branding, so the
             logo is only drawn on the built-in layout. --}}
        @if($logo && ! $background)
            <img class="logo" src="{{ $logo }}">
        @endif

        {{-- Rendered inside the student's locale (IssueCertificate::renderPdf):
             the wording, the course title where translated, and the date. --}}
        <div class="center eyebrow">{{ __t('mail.certificate_pdf.title') }}</div>
        <div class="rule rule-l"></div>
        <div class="dot"></div>
        <div class="rule rule-r"></div>

        <div class="center lead lead-1">{{ __t('mail.certificate_pdf.certifies') }}</div>
        <div class="center name">{{ $certificate->name }}</div>
        <div class="name-rule"></div>
        <div class="center lead lead-2">{{ __t('mail.certificate_pdf.completed') }}</div>
        <div class="center course">{{ $certificate->course->translated('title', app()->getLocale()) }}</div>

        <div class="meta">
            <div class="label">{{ __t('mail.certificate_pdf.number') }}</div>
            <div class="value">{{ $certificate->number }}</div>
            <div class="label">{{ __t('mail.certificate_pdf.issued') }}</div>
            <div class="value">{{ $certificate->issued_at->locale(app()->getLocale())->isoFormat('LL') }}</div>
            <div class="url">{{ route('certificates.verify', $certificate->number) }}</div>
        </div>

        <div class="seal-o"></div>
        <div class="seal-i"></div>
        <div class="seal-t seal-1">PILOT ACADEMY</div>
        @if($certificate->score_percent !== null)
            <div class="seal-t seal-2">{{ $certificate->score_percent }}%</div>
        @else
            <div class="seal-t seal-2">&#10003;</div>
        @endif
        <div class="seal-t seal-3">{{ $certificate->issued_at->format('Y') }}</div>

        <img class="qr" src="data:image/svg+xml;base64,{{ $qr }}">
        <div class="qr-caption">{{ __t('mail.certificate_pdf.scan') }}</div>
    </div>
</body>
</html>
