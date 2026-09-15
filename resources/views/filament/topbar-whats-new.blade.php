{{--
    What's new, as an icon in the top bar beside search.

    Release notes are something people go looking for right after a screen
    changed shape, so the way in sits where they already are rather than only
    at the bottom of the sidebar under Docs. The page keeps its sidebar item.

    An icon, not words: one text button in a row of icons is the thing that
    wraps on a narrow screen. The title and aria-label carry the name.
--}}
@php
    $page = \App\Filament\Pages\Changelog::class;
    $active = request()->routeIs($page::getRouteName());
@endphp

@if ($page::canAccess())
    <a
        href="{{ $page::getUrl() }}"
        data-whats-new-shortcut
        title="{{ __t('admin_nav.whats_new.nav') }}"
        aria-label="{{ __t('admin_nav.whats_new.nav') }}"
        @if ($active) aria-current="page" @endif
        @class([
            'flex shrink-0 items-center justify-center rounded-lg p-2 outline-none transition focus-visible:ring-2 focus-visible:ring-primary-500',
            'bg-gray-100 text-primary-600 dark:bg-white/5 dark:text-primary-400' => $active,
            'text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300' => ! $active,
        ])
    >
        <x-filament::icon icon="heroicon-o-megaphone" class="size-5" />
    </a>
@endif
