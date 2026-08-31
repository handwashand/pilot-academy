<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pilot Academy')</title>
    @isset($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endisset

    {{-- public/favicon.ico is the empty stock file, so it is deliberately not
         linked — an SVG icon covers every current browser. Drop a real .ico in
         and add a fallback link here if very old browsers ever matter. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/pilot-mark.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/pilot-mark.svg') }}">
    <meta name="theme-color" content="#0284c7">

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
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
            <a href="{{ route('academy.home') }}" class="flex items-center gap-2.5">
                {{-- The Pilot mark. Kept next to the wordmark rather than using
                     the full lockup, which already reads "PILOT" and would say
                     it twice beside "Pilot Academy". --}}
                <img src="{{ asset('img/pilot-mark.svg') }}" alt="" class="w-9 h-9" width="36" height="36">
                <span class="font-extrabold text-navy text-lg">Pilot <span class="text-brand">Academy</span></span>
            </a>
            <div class="flex items-center gap-3">
                @auth
                    @php($name = auth()->user()->name)
                    <a href="{{ route('certificates.index') }}" class="hidden sm:block text-sm text-slate-600 hover:text-brand font-medium">Certificates</a>
                    <span class="hidden sm:block text-sm text-slate-500">{{ $name }}</span>
                    <span class="w-9 h-9 rounded-full bg-navy text-white flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(mb_substr($name, 0, 1)) }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm text-slate-500 hover:text-brand font-medium">Log out</button>
                    </form>
                @else
                    @php($name = session('student_name'))
                    @if($name)
                        <span class="hidden sm:block text-sm text-slate-500">{{ $name }}</span>
                    @endif
                    <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-brand font-medium">Log in</a>
                    <a href="{{ route('register') }}" class="text-sm font-semibold rounded-lg bg-brand text-white px-3.5 py-1.5 hover:bg-blue-700">Register</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-5 py-8">
        @yield('content')
    </main>

    <footer class="max-w-6xl mx-auto px-5 py-10 text-center text-sm text-slate-400">
        Pilot Academy · internal training
    </footer>
</body>
</html>
