<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pilot Academy')</title>
    @isset($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endisset

    {{-- Versioned so browsers replace an old cached favicon after deploy. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/pilot-mark.svg') }}?v={{ config('app.version') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/pilot-mark.svg') }}?v={{ config('app.version') }}">
    {{-- Navy, the brand's dark ink. This was #0284c7 — the blue the mark used
         before it turned amber — which is no longer in the palette at all. --}}
    <meta name="theme-color" content="#0a2540">

    {{-- Link previews, e.g. when a course is shared in a chat. --}}
    <meta property="og:site_name" content="Pilot Academy">
    <meta property="og:title" content="@yield('title', 'Pilot Academy')">
    <meta property="og:type" content="website">
    {{-- PNG, not the SVG mark: Slack, WhatsApp and Twitter all refuse to render
         an SVG preview image. --}}
    <meta property="og:image" content="{{ asset('img/pilot-logo.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    @isset($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endisset
    {{-- Compiled Tailwind via Vite. In production public/build/manifest.json is
         committed (built in CI), so this serves one static, minified CSS file.
         The CDN below is ONLY a dev fallback for a checkout without a build
         (e.g. no local Node) — it never loads in production. --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/app.css')
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            brand: '#1463ff',
                            navy: '#0a2540',
                            ok: '#19a86b',
                        },
                        fontFamily: {
                            sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        },
                    },
                },
            };
        </script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* The academy reads better a little smaller on a desktop — about what
           the browser's nine-tenths zoom gives, but only for this site, and
           without anyone having to set it. Tailwind sizes are in rem, so one
           number scales the type and the spacing together, and the max-width
           column narrows with them.

           Phones are deliberately left at the browser's own 16px. Shrinking the
           root there would shrink every tap target with it: h-11 is 2.75rem,
           which is 44px at 16px and under 40 once scaled — below the ~44px rule
           the student site is held to. 768px is the md: breakpoint, so the
           change starts where the layout stops being a single phone column.

           Written as 0.9em rather than a percentage: on the root element em
           resolves against the browser's own default, so a reader who has set a
           larger font still gets one. It also keeps a figure-with-a-percent-sign
           out of the page source — FinalQuizCertificateTest asserts the public
           verification page contains no such figure, so that a certificate
           score cannot leak onto it, and a stylesheet carrying one tripped it. */
        @media (min-width: 768px) {
            html { font-size: 0.9em; }
        }

        body { font-family: 'Inter', system-ui, sans-serif; }
        .prose-lesson h2 { font-size: 1.35rem; font-weight: 700; margin: 1.2rem 0 .5rem; color: #0a2540; }
        .prose-lesson h3 { font-size: 1.1rem; font-weight: 700; margin: 1rem 0 .4rem; color: #0a2540; }
        .prose-lesson p { margin: .6rem 0; line-height: 1.7; color: #334155; }
        .prose-lesson ul { list-style: disc; margin: .6rem 0 .6rem 1.4rem; color: #334155; line-height: 1.7; }
        .prose-lesson strong { color: #0a2540; }
        /* Keep embedded lesson media from overflowing on small screens */
        .prose-lesson img, .prose-lesson video { max-width: 100%; height: auto; border-radius: 8px; }
        .prose-lesson iframe { max-width: 100%; }
        .prose-lesson table { display: block; max-width: 100%; overflow-x: auto; }

        /* Transcripts are plain text with real line breaks. Tailwind's
           `whitespace-pre-line` is not in the committed bundle. */
        .transcript { white-space: pre-line; }

        /* Visually hidden, still announced. Tailwind's `sr-only` is not in the
           committed CSS bundle, so the academy defines its own. */
        .vh {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0;
        }

        /* Lets a keyboard or screen-reader user jump the header on every page. */
        .skip-link {
            position: absolute; left: 12px; top: -64px; z-index: 50;
            background: #fff; color: #0a2540; font-weight: 600;
            padding: 10px 16px; border-radius: 8px;
            box-shadow: 0 4px 14px rgba(10, 37, 64, .18);
        }
        .skip-link:focus { top: 12px; }

        /* The default focus ring is invisible against the brand blue links. */
        :focus-visible { outline: 2px solid #1463ff; outline-offset: 2px; border-radius: 4px; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important; animation-iteration-count: 1 !important;
                transition-duration: .01ms !important; scroll-behavior: auto !important;
            }
        }
    </style>
</head>
