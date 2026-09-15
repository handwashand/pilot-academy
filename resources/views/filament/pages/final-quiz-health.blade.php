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
        {{ __t('admin_pages.final_quiz_health.intro') }}
    </p>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- First-time pass rate --}}
        <section class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __t('admin_pages.final_quiz_health.pass_rate') }}</h2>
                <x-filament::badge :color="$overall['status']['color']">{{ $overall['status']['label'] }}</x-filament::badge>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-950 dark:text-white">
                {{ $overall['rate'] !== null ? $overall['rate'].'%' : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __t('admin_pages.final_quiz_health.band', ['low' => $low, 'high' => $high]) }}
            </p>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $overall['status']['note'] }}</p>
            <p class="mt-auto border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400">
                {{ __tc('admin_pages.final_quiz_health.first_attempts_count', $overall['sample']) }}
            </p>
        </section>

        {{-- Days to certificate --}}
        <section class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __t('admin_pages.final_quiz_health.days') }}</h2>
                <x-filament::badge color="gray">{{ $days['sample'] > 0 ? __t('admin_pages.final_quiz_health.no_target') : __t('admin_pages.final_quiz_health.status.no_data') }}</x-filament::badge>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-950 dark:text-white">
                {{ $days['median'] !== null ? __tc('admin_pages.final_quiz_health.days_value', $days['median']) : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __t('admin_pages.final_quiz_health.days_formula') }}
            </p>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                @if ($days['sample'] > 0)
                    {{ __t('admin_pages.final_quiz_health.days_range', ['fastest' => $days['fastest'], 'slowest' => $days['slowest']]) }}
                @else
                    {{ __t('admin_pages.final_quiz_health.no_certificates') }}
                @endif
            </p>
            <p class="mt-auto border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400">
                {{ __tc('admin_pages.final_quiz_health.certificates_count', $days['sample']) }}
            </p>
        </section>

        {{-- Question difficulty — said plainly rather than left out. --}}
        <section class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __t('admin_pages.final_quiz_health.difficulty') }}</h2>
                <x-filament::badge color="gray">{{ __t('admin_pages.final_quiz_health.not_measured') }}</x-filament::badge>
            </div>
            <p class="mt-3 text-3xl font-bold text-gray-950 dark:text-white">—</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __t('admin_pages.final_quiz_health.difficulty_formula') }}
            </p>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                {{ __t('admin_pages.final_quiz_health.difficulty_body') }}
            </p>
            <p class="mt-auto border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400">
                {{ __t('admin_pages.final_quiz_health.difficulty_waiting') }}
            </p>
        </section>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <h2 class="border-b border-gray-200 px-5 py-3 text-sm font-semibold text-gray-950 dark:border-white/10 dark:text-white">
            {{ __t('admin_pages.final_quiz_health.by_course') }}
        </h2>

        @if ($byCourse === [])
            <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">{{ __t('admin_pages.final_quiz_health.no_attempts') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-2 font-medium">{{ __t('admin_common.course') }}</th>
                            <th class="px-5 py-2 text-right font-medium">{{ __t('admin_pages.final_quiz_health.first_attempts') }}</th>
                            <th class="px-5 py-2 text-right font-medium">{{ __t('admin_pages.final_quiz_health.passed_first_time') }}</th>
                            <th class="px-5 py-2 font-medium">{{ __t('admin_pages.final_quiz_health.verdict') }}</th>
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
