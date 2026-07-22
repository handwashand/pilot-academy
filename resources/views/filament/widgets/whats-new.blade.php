<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">What's new</x-slot>
        <x-slot name="description">The latest updates to the academy</x-slot>

        @php($entries = $this->getEntries())

        @if (empty($entries))
            <p class="text-sm text-gray-500 dark:text-gray-400">No updates yet.</p>
        @else
            <ul class="space-y-4">
                @foreach ($entries as $entry)
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-2 w-2 flex-none rounded-full bg-primary-500"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $entry['title'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $entry['summary'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5">
                <a href="{{ \App\Filament\Pages\Changelog::getUrl() }}"
                   class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                    See all updates &rarr;
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
