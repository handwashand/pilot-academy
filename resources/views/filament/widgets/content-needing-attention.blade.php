{{-- The root element is unconditional: Livewire refuses to render a component
     that returns no markup, so wrapping the whole view in an @if would break
     the dashboard on a healthy academy. --}}
<x-filament-widgets::widget>
    @php($problems = $this->getProblems())

    @if($problems->isNotEmpty())
        <x-filament::section
            icon="heroicon-o-exclamation-triangle"
            icon-color="danger"
            :heading="'Content needing attention (' . $problems->count() . ')'"
            description="Students are hitting these right now. Nothing else in the panel warns about them."
            collapsible
        >
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach($problems as $problem)
                    <li class="flex flex-col gap-1 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                        <div class="min-w-0">
                            <x-filament::badge :color="$problem['severity']" class="inline-flex">
                                {{ $problem['what'] }}
                            </x-filament::badge>

                            <p class="mt-1 truncate text-sm font-medium text-gray-950 dark:text-white">
                                {{ $problem['name'] }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $problem['fix'] }}
                            </p>
                        </div>

                        @if($problem['url'])
                            <x-filament::link :href="$problem['url']" class="shrink-0">
                                Fix it
                            </x-filament::link>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
