<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        /* dompdf: absolute positioning in mm on an A4 landscape page (297x210).
           Brand palette only — navy #0a2540, Pilot blue #1463ff, amber #FFB900.
           A course certificate has a navy frame with an amber hairline; the
           all-courses certificate swaps them (amber frame, navy hairline).
           Layout chosen on the design canvas, 2026-11-24: issuer and original
           PILOT logo, recipient, precise achievement, signatory, seal, ID + QR.
           No study time (hours) is printed: the owner asked for it to be left out. */
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'DejaVu Serif', 'Times New Roman', serif; color: #0a2540; }

        .page { position: relative; width: 297mm; height: 210mm; overflow: hidden; background: #fbfaf6; }
        .bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }

        .band { position: absolute; top: 7mm; left: 7mm; width: 279mm; height: 192mm; border: 2.4mm solid #0a2540; }
        .hair { position: absolute; top: 12.2mm; left: 12.2mm; width: 271.4mm; height: 184.4mm; border: 0.35mm solid #FFB900; }
        .academy .band { border: 2.8mm solid #FFB900; }
        .academy .hair { border: 0.4mm solid #0a2540; }

        /* 1920x604 lockup, kept to ratio: 46mm wide is 14.5mm tall. Centred. */
        .logo { position: absolute; top: 19mm; left: 125.5mm; width: 46mm; height: 14.5mm; }

        .center { position: absolute; left: 30mm; width: 237mm; text-align: center; }
        .sans { font-family: 'DejaVu Sans', sans-serif; }

        .issuer { top: 36.5mm; font-size: 7.5pt; letter-spacing: 2.5pt; text-transform: uppercase; color: #475569; }
        /* The title has the full width: a long translation must fit on one line. */
        .title { top: 44mm; font-size: 25pt; font-weight: bold; color: #0a2540; }

        .pill { position: absolute; top: 57mm; left: 108.5mm; width: 80mm; padding: 1.3mm 0; text-align: center;
                background: #0a2540; color: #FFB900; border-radius: 4mm;
                font-size: 7.5pt; font-weight: bold; letter-spacing: 2pt; text-transform: uppercase; }

        .lead { font-size: 12pt; font-style: italic; color: #475569; }
        .lead-1 { top: 60mm; }
        .academy .lead-1 { top: 66mm; }
        .name { top: 67mm; font-size: 32pt; font-weight: bold; color: #0a2540; }
        .academy .name { top: 73mm; }
        .name-rule { position: absolute; top: 86mm; left: 83.5mm; width: 130mm; height: 0.4mm; background: #FFB900; }
        .academy .name-rule { top: 92mm; }
        .achievement { position: absolute; top: 91mm; left: 53.5mm; width: 190mm; text-align: center;
                       font-size: 12.5pt; line-height: 1.5; color: #334155; }
        .academy .achievement { top: 97mm; }
        .achievement strong { color: #1463ff; }

        /* Bottom row: signatory left, seal centre, ID and QR right. */
        .sign { position: absolute; top: 146mm; left: 26mm; width: 75mm; }
        .sign-image { height: 13mm; }
        .sign-space { height: 13mm; }
        .sign-line { width: 62mm; height: 0.3mm; background: #0a2540; margin-top: 1mm; }
        .sign-name { margin-top: 2mm; font-size: 10.5pt; font-weight: bold; color: #0a2540; }
        .sign-title { font-size: 8.5pt; color: #475569; }

        .seal-o { position: absolute; top: 143mm; left: 130.5mm; width: 36mm; height: 36mm; border-radius: 18mm; background: #0a2540; }
        .seal-i { position: absolute; top: 145.4mm; left: 132.9mm; width: 30.4mm; height: 30.4mm; border-radius: 15.2mm; border: 0.4mm solid #FFB900; }
        .seal-t { position: absolute; left: 130.5mm; width: 36mm; text-align: center; color: #ffffff; }
        .seal-1 { top: 152mm; font-size: 6pt; letter-spacing: 1.5pt; }
        .seal-2 { top: 156.5mm; font-size: 14pt; font-weight: bold; color: #FFB900; }
        .seal-3 { top: 166mm; font-size: 6pt; letter-spacing: 1.5pt; }

        .meta { position: absolute; top: 149mm; left: 175mm; width: 58mm; text-align: right; font-size: 8pt; color: #64748b; }
        .meta .label { font-size: 6.5pt; letter-spacing: 1.2pt; text-transform: uppercase; color: #64748b; }
        .meta .value { font-size: 10pt; font-weight: bold; color: #0a2540; margin-bottom: 2mm; }
        .meta .url { font-size: 6.5pt; color: #64748b; }
        .qr { position: absolute; top: 148mm; left: 238mm; width: 28mm; height: 28mm; }
        .qr-caption { position: absolute; top: 177mm; left: 232mm; width: 40mm; text-align: center; font-size: 6.5pt; color: #64748b; }

        .issued-by { top: 186.5mm; font-size: 7pt; color: #64748b; }
    </style>
</head>
<body>
    @php
        $isAcademy = $certificate->isAcademy();
        $lessons = __tc('academy.common.lessons', $counts['lessons']);
    @endphp
    <div class="page {{ $isAcademy ? 'academy' : '' }}">
        @if($background)
            <img class="bg" src="{{ $background }}">
        @else
            <div class="band"></div>
            <div class="hair"></div>
        @endif

        {{-- A custom background is expected to carry its own branding, so the
             logo is only drawn on the built-in layout. --}}
        @if($logo && ! $background)
            <img class="logo" src="{{ $logo }}">
        @endif

        {{-- Rendered inside the student's locale (IssueCertificate::renderPdf):
             the wording, the course title where translated, and the date. --}}
        <div class="center sans issuer">{{ __t('mail.certificate_pdf.issuer') }}</div>
        <div class="center title">{{ __t($isAcademy ? 'mail.certificate_pdf.title_academy' : 'mail.certificate_pdf.title') }}</div>

        @if($isAcademy)
            <div class="pill sans">{{ __t('mail.certificate_pdf.academy_badge') }}</div>
        @endif

        <div class="center lead lead-1">{{ __t('mail.certificate_pdf.certifies') }}</div>
        <div class="center name">{{ $certificate->name }}</div>
        <div class="name-rule"></div>

        {{-- Built from escaped pieces: only the <strong> around the title is markup. --}}
        <div class="achievement">
            @if($isAcademy)
                {!! __t('mail.certificate_pdf.achievement_academy', [
                    'academy' => '<strong>'.e(__t('mail.certificate_pdf.academy_name')).'</strong>',
                    'courses' => e(__tc('academy.home.hero_courses', $counts['courses'])),
                    'lessons' => e($lessons),
                ]) !!}
            @else
                {{-- No study time on purpose (owner, 2026-11-24): lessons and score only. --}}
                {!! __t('mail.certificate_pdf.achievement_course', [
                    'course' => '<strong>'.e($certificate->title()).'</strong>',
                    'lessons' => e($lessons),
                    'score' => (int) $certificate->score_percent,
                ]) !!}
            @endif
        </div>

        {{-- Set under Settings → Certificate; with nobody set, nothing is printed here. --}}
        @if($signatory)
            <div class="sign sans">
                @if($signatory['signature'])
                    <img class="sign-image" src="{{ $signatory['signature'] }}">
                @else
                    <div class="sign-space"></div>
                @endif
                <div class="sign-line"></div>
                <div class="sign-name">{{ $signatory['name'] }}</div>
                @if($signatory['title'] !== '')
                    <div class="sign-title">{{ $signatory['title'] }}</div>
                @endif
            </div>
        @endif

        <div class="seal-o"></div>
        <div class="seal-i"></div>
        <div class="seal-t sans seal-1">PILOT ACADEMY</div>
        @if($isAcademy)
            <div class="seal-t sans seal-2">{{ $counts['courses'] }}/{{ $counts['courses'] }}</div>
            <div class="seal-t sans seal-3">{{ __t('mail.certificate_pdf.all_courses') }}</div>
        @else
            <div class="seal-t sans seal-2">{{ $certificate->score_percent !== null ? $certificate->score_percent.'%' : '✓' }}</div>
            <div class="seal-t sans seal-3">{{ $certificate->issued_at->format('Y') }}</div>
        @endif

        <div class="meta sans">
            <div class="label">{{ __t('mail.certificate_pdf.number') }}</div>
            <div class="value">{{ $certificate->number }}</div>
            <div class="label">{{ __t('mail.certificate_pdf.issued') }}</div>
            <div class="value">{{ $certificate->issued_at->locale(app()->getLocale())->isoFormat('LL') }}</div>
            <div class="url">{{ route('certificates.verify', $certificate->number) }}</div>
        </div>

        <img class="qr" src="data:image/svg+xml;base64,{{ $qr }}">
        <div class="qr-caption sans">{{ __t('mail.certificate_pdf.scan') }}</div>

        <div class="center sans issued-by">{{ __t('mail.certificate_pdf.issued_by') }}</div>
    </div>
</body>
</html>
