{{--
    The language button in the admin panel's top bar: a globe and the current
    code ("EN"), opening a short menu. Deliberately not a <select>: a dropdown
    of native names is wide, and the top bar has no room for one.

    It posts to the same `locale.switch` route as the student site, so the
    choice is saved to the session and the user's account in one place. The
    full page reload that follows is wanted: Filament renders its own chrome
    from app()->getLocale(), so the whole panel must come back in the new
    language.

    Alpine rather than <details>: the panel already loads Alpine, and
    click.outside closes it the way every other Filament menu closes.
--}}
@php
    $languages = app(\App\Services\Translator::class)->activeLanguages();
    $current = app()->getLocale();
@endphp

{{-- One language is not a choice. --}}
@if ($languages->count() > 1)
    <div
        x-data="{ open: false }"
        x-on:click.outside="open = false"
        x-on:keydown.escape.window="open = false"
        class="relative shrink-0"
        data-language-switcher
    >
        <button
            type="button"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open"
            aria-haspopup="menu"
            aria-label="{{ __t('locale.choose') }}"
            title="{{ __t('locale.choose') }}"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-semibold text-gray-500 outline-none transition hover:bg-gray-50 hover:text-gray-700 focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200"
        >
            <x-filament::icon icon="heroicon-o-language" class="size-5" />
            <span>{{ strtoupper($current) }}</span>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            role="menu"
            class="absolute end-0 z-50 mt-1 w-48 rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        >
            @foreach ($languages as $language)
                {{-- A form per language, not a link: this changes what the
                     server remembers, and a GET that does that is what link
                     prefetchers trip over. --}}
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $language->code }}">

                    <button
                        type="submit"
                        role="menuitem"
                        @class([
                            'flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-start text-sm transition',
                            'bg-primary-50 font-semibold text-primary-600 dark:bg-primary-400/10 dark:text-primary-400' => $language->code === $current,
                            'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5' => $language->code !== $current,
                        ])
                    >
                        {{-- In its own language, so a speaker finds theirs
                             without knowing the English name. --}}
                        <span>{{ $language->native_name }}</span>
                        <span class="text-xs uppercase text-gray-400">{{ $language->code }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </div>
@endif
