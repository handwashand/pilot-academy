{{--
    Final quiz health: one card per measure — value, what it is judged against,
    what it means, and how many results it rests on. Flat cards, the status
    carried by a badge. Every class here comes from the panel theme.
--}}
@php
    $overall = $this->firstTimePassRate();
    $byCourse = $this->firstTimePassRateByCourse();
    $days = $this->daysToCertificate();
    [$low, $high] = \App\Filament\Pages\FinalQuizHealth::PASS_BAND;
@endphp

<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Is the final quiz measuring what it should? Learners only — staff previews are left out.
    </p>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- First-time pass rate --}}
        <section class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">First-time pass rate</h2>
                <x-filament::badge :color="$overall['status']['color']">{{ $overall['status']['label'] }}</x-filament::badge>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-950 dark:text-white">
                {{ $overall['rate'] !== null ? $overall['rate'].'%' : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Passed on the first try ÷ all first tries · suggested band {{ $low }}–{{ $high }}%
            </p>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $overall['status']['note'] }}</p>
            <p class="mt-auto border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400">
                {{ $overall['sample'] }} first {{ $overall['sample'] === 1 ? 'attempt' : 'attempts' }}
            </p>
        </section>

        {{-- Days to certificate --}}
        <section class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Days to certificate</h2>
                <x-filament::badge color="gray">{{ $days['sample'] > 0 ? 'No target set' : 'No data yet' }}</x-filament::badge>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-950 dark:text-white">
                {{ $days['median'] !== null ? $days['median'].' '.\Illuminate\Support\Str::plural('day', $days['median']) : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Median, from a learner's first finished lesson in a course to their certificate for it
            </p>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                @if ($days['sample'] > 0)
                    Fastest {{ $days['fastest'] }}, slowest {{ $days['slowest'] }}. No target has been agreed, so this is a direction to watch rather than a verdict.
                @else
                    No learner has earned a certificate yet.
                @endif
            </p>
            <p class="mt-auto border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400">
                {{ $days['sample'] }} {{ \Illuminate\Support\Str::plural('certificate', $days['sample']) }}
            </p>
        </section>

        {{-- Question difficulty — said plainly rather than left out. --}}
        <section class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Question difficulty</h2>
                <x-filament::badge color="gray">Not measured</x-filament::badge>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-950 dark:text-white">—</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Share of learners answering each question correctly
            </p>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                The academy records each attempt's total score, not the answer given to each question, so there is nothing to calculate this from yet. It would flag questions almost nobody, or almost everybody, gets right.
            </p>
            <p class="mt-auto border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400">
                Waiting on per-question answers being stored
            </p>
        </section>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <h2 class="border-b border-gray-200 px-5 py-3 text-sm font-semibold text-gray-950 dark:border-white/10 dark:text-white">
            First-time pass rate by course
        </h2>

        @if ($byCourse === [])
            <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">No learner has sat a final quiz yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-2 font-medium">Course</th>
                            <th class="px-5 py-2 text-right font-medium">First attempts</th>
                            <th class="px-5 py-2 text-right font-medium">Passed first time</th>
                            <th class="px-5 py-2 font-medium">Verdict</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($byCourse as $row)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-950 dark:text-white">{{ $row['course'] }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $row['sample'] }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $row['rate'] }}%</td>
                                <td class="px-5 py-3">
                                    <x-filament::badge :color="$row['status']['color']">{{ $row['status']['label'] }}</x-filament::badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-filament-panels::page>
