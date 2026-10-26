<!DOCTYPE html>
<html lang="{{ $locale['current'] ?? app()->getLocale() }}" dir="{{ $locale['direction'] ?? 'ltr' }}" class="h-full">
@include('academy.partials.head')
<body class="h-full bg-slate-50 text-slate-800">
    <a href="#main" class="skip-link">{{ __t('nav.skip') }}</a>

    <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
        {{-- Three zones: the logo, the four content areas in the middle, and
             everything about you on the right. The side zones are flex-1 and
             the middle one flex-none, which centres the nav on the header
             itself rather than in whatever space the sides leave over.

             From md: up only. Exact centring needs the two sides to be equal,
             and on a 375px phone the right side needs more room than that
             leaves — Log in ran into the icons. Below md the nav takes the
             space left over instead, which is what fitted before. --}}
        <div class="max-w-6xl mx-auto px-4 sm:px-5 h-16 flex items-center gap-0.5 md:gap-2">
            <a href="{{ route('academy.home') }}" class="flex min-w-0 md:flex-1 items-center">
                {{-- The same PILOT ACADEMY lockup as the admin panel, at the same
                     1.75rem, so both sides of the academy read as one product.
                     It replaced the mark plus HTML text on 2026-09-14 at the
                     owner's request. The stacked "ACADEMY" is small at this
                     height; the alt text and page titles carry the name for
                     anyone who cannot read it. About 89px wide — narrower than
                     the mark and text were, which gives the header room back. --}}
                {{-- object-fit keeps the lockup's proportions when a narrow phone
                     (360px, a Spanish guest header) squeezes its width: it scales
                     down instead of being squashed. Inline, as object-contain is
                     not relied on from the committed CSS bundle. --}}
                <img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy"
                     class="h-7 w-auto max-w-full flex-none" width="89" height="28"
                     style="object-fit: contain; object-position: left center;">
            </a>
            {{-- The four things a partner comes here for. The word joins
                 the glyph from md: up; below that the header has no room
                 for four labels on a 375px phone, so each link is its
                 glyph alone with the name in aria-label. Help moved to the
                 account menu and the footer to make room. --}}
            <nav class="flex flex-1 md:flex-none items-center justify-center sm:gap-1" aria-label="{{ __t('nav.sections') }}">
                @foreach([
                    ['name' => 'courses', 'route' => route('academy.courses'), 'label' => __t('nav.courses')],
                    ['name' => 'lessons', 'route' => route('academy.lessons'), 'label' => __t('nav.lessons')],
                    ['name' => 'case_studies', 'route' => route('academy.case-studies.index'), 'label' => __t('nav.case_studies')],
                    ['name' => 'tutorials', 'route' => route('academy.tutorials'), 'label' => __t('nav.tutorials')],
                    ['name' => 'webinars', 'route' => route('academy.webinars'), 'label' => __t('nav.webinars')],
                ] as $item)
                    <a href="{{ $item['route'] }}"
                       class="flex flex-none items-center gap-2 h-11 px-1 sm:px-2 rounded-lg text-sm text-slate-600 hover:text-brand hover:bg-slate-50 active:bg-slate-100 font-medium"
                       aria-label="{{ $item['label'] }}">
                        @include('academy.partials.nav-icon', ['name' => $item['name']])
                        <span class="hidden md:block">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Everything about you: the account menu, or the way in, and the
                 language button at the very end. --}}
            <div class="flex flex-none md:flex-1 min-w-0 items-center justify-end gap-0.5 sm:gap-3">
                @auth
                    @php
                        $account = auth()->user();
                        $initials = collect(preg_split('/\s+/', trim($account->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    {{-- The account menu: who you are signed in as, your profile, the
                         panel for staff, and the way out. It replaced the name, a
                         decorative initial and a Log out button, which also gives the
                         header room back on a phone. A native <details>, so it works
                         with no JavaScript; the script at the foot of the page only
                         closes it on an outside tap or Escape. --}}
                    <details class="relative flex-none" data-account-menu>
                        <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-full [&::-webkit-details-marker]:hidden"
                                 aria-label="{{ __t('academy.nav.account') }}" title="{{ $account->name }}">
                            <span aria-hidden="true" class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-navy text-sm font-bold text-white">
                                @if($account->avatarUrl())
                                    <img src="{{ $account->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ $initials }}
                                @endif
                            </span>
                        </summary>

                        <div class="absolute end-0 top-full z-30 mt-2 w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                            <div class="border-b border-slate-100 px-3 py-2.5">
                                <p class="truncate text-sm font-bold text-navy">{{ $account->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $account->email }}</p>
                            </div>

                            <a href="{{ route('academy.profile') }}"
                               class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
                                {{ __t('academy.nav.profile') }}
                            </a>

                            <a href="{{ route('certificates.index') }}"
                               class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
                                {{ __t('nav.certificates') }}
                            </a>

                            {{-- Help left the header when the four content areas
                                 arrived. Guests reach it from the footer, which
                                 carries it on every page. --}}
                            <a href="{{ route('academy.help') }}"
                               class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
                                {{ __t('nav.help') }}
                            </a>

                            @if($account->isAdmin())
                                {{-- A full page load: the panel is a different app. --}}
                                <a href="{{ url('/admin') }}"
                                   class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
                                    {{ __t('academy.nav.admin_panel') }}
                                </a>
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                @csrf
                                <button type="submit"
                                        class="flex min-h-11 w-full items-center rounded-lg px-3 text-start text-sm font-medium text-slate-700 hover:bg-slate-50 hover:text-brand">
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
                {{-- The language button: last in the header, so it sits in the top
                     right corner at every width, as it does in the admin panel. A
                     globe and the current code, like the panel's, rather than a
                     select of every name, which only fitted from tablet up. Below
                     sm: just the code, because a guest header in Spanish or Russian
                     has no room for the globe as well. A native details element
                     like the account menu; the script at the foot closes it. No
                     block PHP here: the inline one above would swallow it. --}}
                @if(($locale['available'] ?? collect())->count() > 1)
                    <details class="relative flex-none" data-language-menu>
                        <summary class="flex h-11 cursor-pointer list-none items-center text-sm font-semibold text-slate-600 hover:text-brand [&::-webkit-details-marker]:hidden"
                                 aria-label="{{ __t('locale.choose') }}" title="{{ __t('locale.choose') }}">
                            {{-- An outlined pill inside the 44px tap area, so a lone "ES" on
                                 a phone reads as a button, not stray text beside Register.
                                 The minimum width is inline: no utility for it is in the
                                 committed CSS bundle. --}}
                            <span class="flex h-9 items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-1 sm:px-2 hover:bg-slate-50" style="min-width: 2.25rem">
                                <svg class="hidden sm:block w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                                </svg>
                                <span>{{ strtoupper($locale['current'] ?? app()->getLocale()) }}</span>
                            </span>
                        </summary>

                        <form method="POST" action="{{ route('locale.switch') }}"
                              class="absolute end-0 top-full z-30 mt-2 w-48 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-1 shadow-lg">
                            @csrf
                            @foreach($locale['available'] as $language)
                                {{-- In its own language, so a speaker finds theirs without
                                     knowing the English name. --}}
                                <button type="submit" name="locale" value="{{ $language->code }}" lang="{{ $language->code }}"
                                        @if($language->code === ($locale['current'] ?? app()->getLocale())) aria-current="true" @endif
                                        class="flex min-h-11 w-full items-center justify-between rounded-lg px-3 text-start text-sm {{ $language->code === ($locale['current'] ?? app()->getLocale()) ? 'font-semibold text-navy bg-slate-100' : 'font-medium text-slate-700 hover:bg-slate-50 hover:text-brand' }}">
                                    <span>{{ $language->native_name }}</span>
                                    <span class="text-xs uppercase text-slate-400">{{ $language->code }}</span>
                                </button>
                            @endforeach
                        </form>
                    </details>
                @endif
            </div>
        </div>
    </header>

    <main id="main" class="max-w-6xl mx-auto px-5 py-8">
        @yield('content')
    </main>

    <footer class="max-w-6xl mx-auto px-5 py-10 text-center text-sm text-slate-400">
        {{-- Every language again at the foot of the page, spelled out — a second
             way in for anyone who scrolls rather than looks up. The button in
             the header's top right corner is the main one. --}}
        @if(($locale['available'] ?? collect())->count() > 1)
            <form method="POST" action="{{ route('locale.switch') }}" class="mb-4 flex flex-wrap items-center justify-center gap-1" aria-label="{{ __t('locale.choose') }}">
                @csrf
                @foreach($locale['available'] as $language)
                    <button type="submit" name="locale" value="{{ $language->code }}" lang="{{ $language->code }}"
                            @if($language->code === ($locale['current'] ?? app()->getLocale())) aria-current="true" @endif
                            class="inline-flex items-center min-h-11 px-3 rounded-lg {{ $language->code === ($locale['current'] ?? app()->getLocale()) ? 'font-semibold text-navy bg-slate-100' : 'text-slate-500 hover:text-brand hover:bg-slate-50' }}">
                        {{ $language->native_name }}
                    </button>
                @endforeach
            </form>
        @endif
        <a href="{{ route('academy.help') }}" class="hover:text-brand">{{ __t('nav.help') }}</a> ·
        <a href="{{ route('academy.privacy') }}" class="hover:text-brand">{{ __t('nav.privacy') }}</a>
    </footer>

    {{-- Closes the account and language menus on a tap outside them or on
         Escape. They work without this — they just stay open until toggled
         again. Opening one closes the other: that tap is outside it. --}}
    <script>
        var headerMenus = 'details[data-account-menu][open], details[data-language-menu][open]';

        document.addEventListener('click', function (event) {
            document.querySelectorAll(headerMenus).forEach(function (menu) {
                if (! menu.contains(event.target)) {
                    menu.removeAttribute('open');
                }
            });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll(headerMenus).forEach(function (menu) {
                    menu.removeAttribute('open');
                });
            }
        });
    </script>
</body>
</html>
