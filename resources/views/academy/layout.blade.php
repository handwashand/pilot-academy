<!DOCTYPE html>
<html lang="{{ $locale['current'] ?? app()->getLocale() }}" dir="{{ $locale['direction'] ?? 'ltr' }}" class="h-full">
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
    {{-- NOTE: iOS ignores an SVG apple-touch-icon, so "Add to Home Screen"
         currently falls back to a screenshot. Fixing it needs a square PNG
         mark (180x180) in public/img/ — there isn't one yet. --}}
    <link rel="apple-touch-icon" href="{{ asset('img/pilot-mark.svg') }}">
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
<body class="h-full bg-slate-50 text-slate-800">
    <a href="#main" class="skip-link">{{ __t('nav.skip') }}</a>

    <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-5 h-16 flex items-center justify-between gap-2">
            <a href="{{ route('academy.home') }}" class="flex min-w-0 sm:shrink-0 items-center">
                {{-- The same PILOT ACADEMY lockup as the admin panel, at the same
                     1.75rem, so both sides of the academy read as one product.
                     It replaced the mark plus HTML text on 2026-09-14 at the
                     owner's request. The stacked "ACADEMY" is small at this
                     height; the alt text and page titles carry the name for
                     anyone who cannot read it. About 89px wide — narrower than
                     the mark and text were, which gives the header room back. --}}
                <img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy"
                     class="h-7 w-auto max-w-full flex-none" width="89" height="28">
            </a>
            <div class="flex flex-none sm:flex-initial sm:min-w-0 items-center gap-0.5 sm:gap-3">
                {{-- Help, for everyone: anonymous visitors take lessons too.
                     Icon-only below sm: like Certificates — the header has no
                     room for another word on a 375px phone. --}}
                <a href="{{ route('academy.help') }}"
                   class="flex flex-none items-center gap-2 h-11 px-1 sm:px-2 rounded-lg text-sm text-slate-600 hover:text-brand hover:bg-slate-50 active:bg-slate-100 font-medium"
                   aria-label="{{ __t('nav.help') }}">
                    <span aria-hidden="true" class="w-6 h-6 rounded-full border border-slate-300 text-xs font-bold flex items-center justify-center">?</span>
                    <span class="hidden sm:block">{{ __t('nav.help') }}</span>
                </a>
                @if(($locale['available'] ?? collect())->count() > 1)
                    <form method="POST" action="{{ route('locale.switch') }}" class="hidden sm:block">
                        @csrf
                        <label class="vh" for="locale-switcher">{{ __t('locale.choose') }}</label>
                        <select id="locale-switcher" name="locale" onchange="this.form.submit()" class="h-10 rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-600">
                            @foreach($locale['available'] as $language)
                                <option value="{{ $language->code }}" @selected($language->code === ($locale['current'] ?? app()->getLocale()))>{{ $language->native_name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                @auth
                    @php
                        $account = auth()->user();
                        $initials = collect(preg_split('/\s+/', trim($account->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    {{-- Certificates has to stay reachable on a phone: the word
                         alone overflows the header below sm:, so the 🎓 used for
                         certificates elsewhere in the academy carries it there and
                         the label joins it from sm: up. One link, not two, so
                         there is nothing to keep in sync.

                         `sm:hidden` is NOT in the committed CSS bundle — hiding
                         the icon on desktop would silently do nothing. Every class
                         here was checked against public/build/assets/app-*.css. --}}
                    <a href="{{ route('certificates.index') }}"
                       class="flex flex-none items-center gap-2 h-11 px-2 rounded-lg text-sm text-slate-600 hover:text-brand hover:bg-slate-50 active:bg-slate-100 font-medium"
                       aria-label="{{ __t('nav.certificates') }}">
                        <span aria-hidden="true">🎓</span>
                        <span class="hidden sm:block">{{ __t('nav.certificates') }}</span>
                    </a>
                    {{-- The account menu: who you are signed in as, your profile, the
                         panel for staff, and the way out. It replaced the name, a
                         decorative initial and a Log out button, which also gives the
                         header room back on a phone. A native <details>, so it works
                         with no JavaScript; the script at the foot of the page only
                         closes it on an outside tap or Escape. --}}
                    <details class="relative flex-none" data-account-menu>
                        <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-full [&::-webkit-details-marker]:hidden"
                                 aria-label="{{ __t('nav.account') }}" title="{{ $account->name }}">
                            <span aria-hidden="true" class="flex h-9 w-9 items-center justify-center rounded-full bg-navy text-sm font-bold text-white">{{ $initials }}</span>
                        </summary>

                        <div class="absolute right-0 top-full z-30 mt-2 w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                            <div class="border-b border-slate-100 px-3 py-2.5">
                                <p class="truncate text-sm font-bold text-navy">{{ $account->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $account->email }}</p>
                            </div>

                            <a href="{{ route('academy.profile') }}"
                               class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
                                {{ __t('nav.profile') }}
                            </a>

                            @if($account->isAdmin() || $account->isCreator())
                                {{-- A full page load: the panel is a different app. --}}
                                <a href="{{ url('/admin') }}"
                                   class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
                                    {{ __t('nav.admin_panel') }}
                                </a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                @csrf
                                <button type="submit"
                                        class="flex min-h-11 w-full items-center rounded-lg px-3 text-left text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
                                    {{ __t('nav.logout') }}
                                </button>
                            </form>
                        </div>
                    </details>
                @else
                    @php($name = session('student_name'))
                    @if($name)
                        <span class="hidden sm:block min-w-0 truncate text-sm text-slate-500">{{ $name }}</span>
                    @endif
                    <a href="{{ route('login') }}" class="flex flex-none items-center h-11 px-1 sm:px-1.5 whitespace-nowrap text-sm text-slate-600 hover:text-brand font-medium">{{ __t('auth.login') }}</a>
                    <a href="{{ route('register') }}" class="flex flex-none items-center h-10 whitespace-nowrap text-sm font-semibold rounded-lg bg-brand text-white px-3 sm:px-3.5 hover:bg-blue-700">{{ __t('auth.register') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="main" class="max-w-6xl mx-auto px-5 py-8">
        @yield('content')
    </main>

    <footer class="max-w-6xl mx-auto px-5 py-10 text-center text-sm text-slate-400">
        Pilot Academy · {{ __t('footer.internal_training') }} ·
        <a href="{{ route('academy.help') }}" class="hover:text-brand">{{ __t('nav.help') }}</a>
    </footer>

    {{-- Closes the account menu on a tap outside it or on Escape. The menu
         works without this — it just stays open until toggled again. --}}
    <script>
        document.addEventListener('click', function (event) {
            document.querySelectorAll('details[data-account-menu][open]').forEach(function (menu) {
                if (! menu.contains(event.target)) {
                    menu.removeAttribute('open');
                }
            });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('details[data-account-menu][open]').forEach(function (menu) {
                    menu.removeAttribute('open');
                });
            }
        });
    </script>
</body>
</html>
