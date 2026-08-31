<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'DejaVu Serif', 'Times New Roman', serif; color: #0a2540; }

        .page { position: relative; width: 297mm; height: 210mm; overflow: hidden; }
        .bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
        .frame { position: absolute; top: 6mm; left: 6mm; width: 283mm; height: 196mm;
                 border: 2mm solid #1463ff; }
        .frame-inner { position: absolute; top: 9mm; left: 9mm; width: 277mm; height: 190mm;
                       border: 0.4mm solid #0a2540; }

        .eyebrow { position: absolute; top: 34mm; left: 30mm; width: 237mm; text-align: center;
                   font-size: 13pt; letter-spacing: 4pt; text-transform: uppercase; color: #1463ff; }
        .lead { position: absolute; top: 60mm; left: 30mm; width: 237mm; text-align: center;
                font-size: 12pt; color: #64748b; }
        .name { position: absolute; top: 68mm; left: 30mm; width: 237mm; text-align: center;
                font-size: 34pt; font-weight: bold; color: #0a2540; }
        .lead2 { position: absolute; top: 96mm; left: 30mm; width: 237mm; text-align: center;
                 font-size: 12pt; color: #64748b; }
        .course { position: absolute; top: 103mm; left: 30mm; width: 237mm; text-align: center;
                  font-size: 22pt; font-weight: bold; color: #1463ff; }

        .mark { position: absolute; top: 20mm; left: 22mm; width: 18mm; height: 18mm; }

        .qr { position: absolute; top: 150mm; left: 243mm; width: 34mm; height: 34mm; }
        .qr-caption { position: absolute; top: 185mm; left: 243mm; width: 34mm; text-align: center;
                      font-size: 7pt; color: #94a3b8; }

        .meta { position: absolute; top: 165mm; left: 22mm; font-size: 10pt; color: #64748b; }
        .meta strong { color: #0a2540; }
        .meta div { margin-top: 2mm; }
    </style>
</head>
<body>
    <div class="page">
        @if($background)
            <img class="bg" src="{{ $background }}">
        @else
            <div class="frame"></div>
            <div class="frame-inner"></div>
        @endif

        {{-- A custom background is expected to carry its own branding, so the
             mark is only drawn on the built-in framed layout. --}}
        @if($mark && ! $background)
            <img class="mark" src="data:image/svg+xml;base64,{{ $mark }}">
        @endif

        <div class="eyebrow">Certificate of Completion</div>
        <div class="lead">This certifies that</div>
        <div class="name">{{ $certificate->name }}</div>
        <div class="lead2">has successfully completed the course</div>
        <div class="course">{{ $certificate->course->title }}</div>

        <img class="qr" src="data:image/svg+xml;base64,{{ $qr }}">
        <div class="qr-caption">Scan to verify</div>

        <div class="meta">
            <div><strong>Certificate No.</strong> {{ $certificate->number }}</div>
            <div><strong>Issued</strong> {{ $certificate->issued_at->format('F j, Y') }}</div>
            <div>{{ route('certificates.verify', $certificate->number) }}</div>
        </div>
    </div>
</body>
</html>
