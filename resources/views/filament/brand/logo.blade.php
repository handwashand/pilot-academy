{{-- Panel brand: the Pilot mark plus the product name, matching the public
     site header. If the full PILOT lockup is dropped in as
     public/img/pilot-logo.svg it is used instead, and the white version is
     swapped in for dark mode. --}}
@php
    $lockup = collect(['svg', 'png'])
        ->map(fn (string $ext): string => 'img/pilot-logo.'.$ext)
        ->first(fn (string $path): bool => file_exists(public_path($path)));

    $lockupDark = collect(['svg', 'png'])
        ->map(fn (string $ext): string => 'img/pilot-logo-white.'.$ext)
        ->first(fn (string $path): bool => file_exists(public_path($path)));
@endphp

@if($lockup)
    <img src="{{ asset($lockup) }}" alt="Pilot Academy" class="h-7 w-auto @if($lockupDark) dark:hidden @endif">
    @if($lockupDark)
        <img src="{{ asset($lockupDark) }}" alt="Pilot Academy" class="hidden h-7 w-auto dark:block">
    @endif
@else
    <span class="flex items-center gap-2.5">
        <img src="{{ asset('img/pilot-mark.svg') }}" alt="" class="h-8 w-8" width="32" height="32">
        <span class="text-lg font-extrabold text-gray-950 dark:text-white">
            Pilot <span class="text-primary-600 dark:text-primary-400">Academy</span>
        </span>
    </span>
@endif
