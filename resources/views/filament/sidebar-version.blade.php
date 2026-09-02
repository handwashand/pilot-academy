{{-- The release number, pinned to the very bottom of the sidebar, linking to
     What's new so "which version am I on?" and "what changed?" are one click
     apart.

     Styled with inline CSS on purpose: the Filament panel stylesheet carries no
     Tailwind utility layer, so h-*/text-*/dark:* classes would silently do
     nothing here. The gray-* custom properties are injected per page by
     Filament itself and resolve in both themes. --}}
@php
    $version = config('app.version');
@endphp

@if ($version)
    <div
        @if (filament()->isSidebarCollapsibleOnDesktop())
            {{-- Hidden while the sidebar is collapsed, the same way Filament
                 hides its own navigation labels. No x-cloak: the panel
                 stylesheet does not define [x-cloak], so it would be a no-op
                 that only looked like it was doing something. --}}
            x-show="$store.sidebar.isOpen"
        @endif
        style="padding: 0.25rem 0.75rem 0.75rem; text-align: center;"
    >
        <a
            href="{{ \App\Filament\Pages\Changelog::getUrl() }}"
            style="font-size: 0.6875rem; line-height: 1.2; color: var(--gray-400); text-decoration: none; letter-spacing: 0.02em;"
            title="Pilot Academy {{ $version }} — see what's new"
        >
            v{{ $version }}
        </a>
    </div>
@endif
