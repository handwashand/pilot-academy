{{--
    What's new, printed. Written for paper rather than restyled from the screen:

      · No coloured dots. A dot meaning "Added" is unreadable in greyscale, so
        each section says its category in words instead.
      · dompdf is not a browser — no flexbox, no grid, a subset of CSS2. Keep
        everything here to plain blocks.

    Item HTML is already sanitised by the page's Markdown renderer (raw HTML
    stripped, unsafe links refused).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>What's new — Pilot Academy</title>
    <style>
        @page { margin: 20mm 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.5; color: #1f2937; margin: 0; }

        .masthead { border-bottom: 1.5pt solid #0a2540; padding-bottom: 8pt; margin-bottom: 16pt; }
        .product { font-size: 15pt; font-weight: bold; color: #0a2540; margin: 0; }
        .subtitle { font-size: 9.5pt; color: #6b7280; margin: 3pt 0 0; }

        .release { margin-bottom: 18pt; }
        .release-title { font-size: 13pt; font-weight: bold; color: #111827; margin: 0 0 8pt; padding-bottom: 4pt; border-bottom: 0.75pt solid #d1d5db; }

        .section { margin-bottom: 10pt; }
        .section-title { font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt; color: #374151; margin: 0 0 4pt; page-break-after: avoid; }

        .entry { margin: 0 0 6pt; padding-left: 8pt; border-left: 1.5pt solid #e5e7eb; }
        .entry p { margin: 0 0 4pt; }
        .entry strong { color: #111827; }
        .entry code { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.5pt; }
        .entry a { color: #1f2937; text-decoration: underline; }
        .entry ul, .entry ol { margin: 0 0 4pt; padding-left: 14pt; }
        .entry blockquote { margin: 4pt 0; padding-left: 8pt; color: #4b5563; border-left: 1pt solid #d1d5db; }
        .entry table { width: 100%; border-collapse: collapse; margin: 0 0 4pt; }
        .entry th, .entry td { border: 0.5pt solid #d1d5db; padding: 2pt 4pt; font-size: 8.5pt; text-align: left; vertical-align: top; }

        .colophon { margin-top: 14pt; padding-top: 6pt; border-top: 0.5pt solid #e5e7eb; font-size: 8pt; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="masthead">
        <p class="product">Pilot Academy</p>
        <p class="subtitle">
            What's new — {{ $single ? $releases[0]['title'] : 'every release' }}
        </p>
    </div>

    @foreach ($releases as $release)
        <div class="release">
            <h1 class="release-title">{{ $release['title'] }}</h1>

            @foreach ($release['sections'] as $section)
                <div class="section">
                    <p class="section-title">{{ $section['label'] }}</p>

                    @foreach ($section['items'] as $item)
                        <div class="entry">{!! $item['html'] !!}</div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach

    <p class="colophon">
        {{-- One expression, not an inline @if: a directive glued to the
             text before it is a known Blade trap in this repo. --}}
        Generated {{ now()->format('j F Y') }}{{ config('app.version') ? ' · version '.config('app.version') : '' }}
    </p>
</body>
</html>
