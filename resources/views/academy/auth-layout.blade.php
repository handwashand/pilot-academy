<!DOCTYPE html>
<html lang="{{ $locale['current'] ?? app()->getLocale() }}" dir="{{ $locale['direction'] ?? 'ltr' }}" class="h-full">
@include('academy.partials.head')
<body class="flex h-full min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-10 text-slate-800">
    {{-- A minimal front door, deliberately without the site header: nobody
         signing in needs Courses/Case Studies/Tutorials/Webinars, or a
         second "Log in"/"Register" pair competing with the form right below
         it. The logo is the only thing above the card, so it leads. --}}
    <a href="{{ route('academy.home') }}" class="mb-8 block flex-none">
        <img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy" class="h-12 w-auto">
    </a>

    <main id="main" class="w-full flex-none">
        @yield('content')
    </main>

    <footer class="mt-10 flex-none text-center text-sm text-slate-400">
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

    {{-- Toggles a password field's visibility. Delegated so it works for
         every academy.partials.password-input on the page (register and
         reset-password each include it twice) without per-field wiring. --}}
    <script>
        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-password-toggle]');
            if (! btn) return;

            var input = btn.previousElementSibling;
            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';

            btn.querySelector('[data-icon-show]').classList.toggle('hidden', ! showing);
            btn.querySelector('[data-icon-hide]').classList.toggle('hidden', showing);
            btn.setAttribute('aria-label', showing ? btn.dataset.showLabel : btn.dataset.hideLabel);
        });
    </script>
</body>
</html>
