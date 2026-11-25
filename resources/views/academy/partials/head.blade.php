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
    {{-- iOS ignores an SVG here: a 180px PNG of the same mark, on white. --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/apple-touch-icon.png') }}?v={{ config('app.version') }}">
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
        /* A long URL or code word must wrap, not widen the page on a phone. */
        .prose-lesson { overflow-wrap: anywhere; }
        .prose-lesson pre { max-width: 100%; overflow-x: auto; }

        /* Covers (partials/lesson-cover, the home page cards). Colours and the
           picture come from the course's cover theme through --cover-*
           variables. The picture is one 400x160 drawing weighted to the right,
           sized to cover; the box is a little larger than the cover so each
           --shift-N can move it without showing an edge. Light falls from the
           top left. */
        .lesson-cover {
            position: relative; height: 7rem; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: radial-gradient(circle at 10% 0%, rgba(255, 255, 255, .18), transparent 55%),
                        linear-gradient(135deg, var(--cover-from, #1463ff), var(--cover-to, #0a2540));
        }
        .lesson-cover__image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .lesson-cover__image--banner { opacity: .35; }
        .lesson-cover__pattern {
            position: absolute; inset: -14%;
            background: var(--cover-pattern) right center / cover no-repeat;
        }
        .lesson-cover--shift-2 .lesson-cover__pattern { transform: translate(-6%, 5%); }
        .lesson-cover--shift-0 .lesson-cover__pattern { transform: translate(-11%, -5%); }
        [dir="rtl"] .lesson-cover__pattern { transform: scaleX(-1); }
        [dir="rtl"] .lesson-cover--shift-2 .lesson-cover__pattern { transform: scaleX(-1) translate(-6%, 5%); }
        [dir="rtl"] .lesson-cover--shift-0 .lesson-cover__pattern { transform: scaleX(-1) translate(-11%, -5%); }
        .lesson-cover__number {
            position: absolute; inset-inline-start: 1.25rem; top: 50%; transform: translateY(-50%);
            display: flex; flex-direction: column; gap: .125rem; color: #fff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, .25);
        }
        .lesson-cover__label { font-size: .6875rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; opacity: .85; }
        .lesson-cover__digits { font-size: 2.75rem; font-weight: 800; line-height: 1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }

        /* Home page (academy/home). Inline for the same reason as .lesson-cover.
           .course-tile: a course in a grid, cover on top; on a phone a short
           row with the cover at the side. .course-feature: the one wide card
           ("Start here", "Continue where you left off"). */
        .home-section { margin-bottom: 2.5rem; }
        .home-heading { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .5rem 1rem; margin-bottom: 1rem; }
        .home-heading h2 { font-size: 1.375rem; font-weight: 800; color: #0a2540; }
        .home-eyebrow { display: block; margin-bottom: .75rem; font-size: .75rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #1463ff; }
        .home-grid { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); }
        .course-tile {
            position: relative; display: flex; overflow: hidden; min-height: 5.5rem;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04); transition: box-shadow .15s, transform .15s;
        }
        .course-tile:hover { box-shadow: 0 8px 24px rgba(15, 23, 42, .08); transform: translateY(-2px); }
        .course-tile .lesson-cover { width: 6rem; height: auto; flex-shrink: 0; }
        .course-tile__count {
            position: absolute; bottom: .75rem; inset-inline-start: .75rem; display: none;
            padding: .2rem .6rem; border-radius: 999px; background: rgba(10, 37, 64, .55); color: #fff;
            font-size: .75rem; font-weight: 600;
        }
        .course-tile__body { display: flex; flex: 1; min-width: 0; flex-direction: column; justify-content: center; gap: .25rem; padding: .75rem 1rem; }
        .course-tile__title { font-size: .9375rem; font-weight: 700; line-height: 1.3; color: #0a2540; }
        .course-tile__title a::after { content: ''; position: absolute; inset: 0; }
        .course-tile__desc, .course-tile__cta, .course-tile__tag { display: none; }
        .course-tile__tag { font-size: .6875rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #1463ff; }
        .course-tile__meta { font-size: .8125rem; color: #64748b; }
        .course-tile__cta { margin-top: auto; padding-top: .5rem; font-size: .875rem; font-weight: 700; color: #1463ff; }
        .course-feature {
            display: flex; flex-direction: column; overflow: hidden;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 1.125rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }
        .course-feature--next { border-color: #bfdbfe; box-shadow: 0 8px 24px rgba(20, 99, 255, .08); }
        .course-feature .lesson-cover { height: 8.5rem; }
        .course-feature__body { display: flex; flex-direction: column; justify-content: center; gap: .625rem; padding: 1.25rem 1.25rem 1.5rem; min-width: 0; }
        .course-feature__title { font-size: 1.375rem; font-weight: 800; line-height: 1.25; color: #0a2540; }
        .course-feature__cta {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 2.875rem; padding: 0 1.375rem;
            border-radius: .625rem; background: #1463ff; color: #fff; font-weight: 700; align-self: flex-start;
            transition: background .15s;
        }
        .course-feature__cta:hover { background: #0a2540; }
        /* A started course: its next three lessons. They scroll sideways on a
           phone, so each course takes one card's height; a grid from 640px. */
        .lesson-row { display: flex; gap: .75rem; overflow-x: auto; scroll-snap-type: x mandatory; margin: 0 -1rem; padding: 0 1rem .25rem; }
        .lesson-row > * { flex: 0 0 15rem; scroll-snap-align: start; }
        .course-progress { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; margin-bottom: .875rem; }
        .course-progress .lesson-cover { width: 4.5rem; height: 3rem; border-radius: .625rem; flex-shrink: 0; }
        .course-done { display: flex; align-items: center; gap: .875rem; padding: .625rem .875rem .625rem .625rem; background: #fff; border: 1px solid #e2e8f0; border-radius: .875rem; }
        .course-done .lesson-cover { width: 4.5rem; height: 3rem; border-radius: .5rem; flex-shrink: 0; }
        .more-tile { display: flex; gap: .875rem; align-items: flex-start; padding: 1.25rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; transition: border-color .15s; }
        .more-tile:hover { border-color: #93c5fd; }
        .more-tile__icon { display: flex; flex-shrink: 0; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; border-radius: .75rem; background: #eff6ff; color: #1463ff; }
        .hero-covers { display: none; }
        @media (min-width: 640px) {
            .home-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
            .course-tile { flex-direction: column; }
            .course-tile .lesson-cover { width: auto; height: 8.75rem; }
            .course-tile__count { display: inline-block; }
            .course-tile__meta--count { display: none; }
            .course-tile__body { justify-content: flex-start; gap: .5rem; padding: 1.125rem 1.25rem 1.25rem; }
            .course-tile__title { font-size: 1.0625rem; }
            .course-tile__desc { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .875rem; line-height: 1.5; color: #475569; }
            .course-tile__cta, .course-tile__tag { display: block; }
            .lesson-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; overflow: visible; margin: 0; padding: 0; }
            .course-feature { flex-direction: row; }
            .course-feature .lesson-cover { width: 40%; height: auto; min-height: 12rem; flex-shrink: 0; }
            .course-feature__body { padding: 1.75rem 2rem; }
            .course-feature__title { font-size: 1.625rem; }
        }
        @media (min-width: 1024px) {
            .home-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .lesson-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .hero-covers { display: flex; flex-direction: column; gap: .75rem; width: 20rem; flex-shrink: 0; }
            .hero-covers .lesson-cover { height: 5.25rem; border-radius: .875rem; box-shadow: 0 12px 30px rgba(0, 0, 0, .25); }
            .hero-covers .lesson-cover:nth-child(odd) { margin-inline-start: 1.5rem; }
        }
        /* Visitor hero: what is on offer, and a way to look before signing in. */
        .hero-stats { display: flex; flex-wrap: wrap; gap: .25rem .6rem; font-size: .875rem; font-weight: 600; color: #fff; opacity: .95; }
        .hero-browse {
            display: inline-flex; align-items: center; justify-content: center; min-height: 2.75rem; padding: 0 1.25rem;
            border-radius: .5rem; border: 1.5px solid rgba(255, 255, 255, .85); color: #fff; font-weight: 600; white-space: nowrap;
            transition: background .15s;
        }
        .hero-browse:hover, .hero-browse:focus-visible { background: rgba(255, 255, 255, .14); }

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
