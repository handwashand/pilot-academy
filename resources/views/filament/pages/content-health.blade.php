{{-- Content health: every problem the signed-in person owns, worst first. --}}
<x-filament-panels::page>
    @php
        $problems = $this->problems()->sortBy(fn (array $problem): int => $problem['severity'] === 'danger' ? 0 : 1)->values();
    @endphp

    @if($problems->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">Nothing is broken for students.</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                Every published course has lessons, every lesson can be finished, and every question has a right answer.
                If that changes, it shows here and as a red count beside Content health in the menu.
            </p>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Students are hitting these right now. Each one links straight to where it is fixed;
            fixed problems drop off this list on their own.
        </p>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach($problems as $problem)
                    <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                        <div class="min-w-0">
                            <x-filament::badge :color="$problem['severity']" class="inline-flex">
                                {{ $problem['what'] }}
                            </x-filament::badge>

                            <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                                {{ $problem['name'] }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $problem['fix'] }}
                            </p>
                        </div>

                        <x-filament::link :href="$problem['url']" class="shrink-0">
                            Fix it
                        </x-filament::link>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-panels::page>
