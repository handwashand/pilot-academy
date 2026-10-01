@extends('academy.layout')

@section('title', __t('academy.courses.title').' - Pilot Academy')

@php
    $metaDescription = __t('academy.courses.meta');
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.courses.title') }}</h1>
        <p class="mt-1 max-w-3xl text-slate-500">{{ __t('academy.courses.intro') }}</p>
    </div>

    <form method="GET" action="{{ route('academy.courses') }}" role="search"
          class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[1.5fr_1fr_1fr_auto] md:items-end">
            <div>
                <label for="q" class="block text-sm font-semibold text-navy">{{ __t('academy.courses.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $term }}"
                       placeholder="{{ __t('academy.courses.search_hint') }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800 focus:border-brand">
            </div>

            <div>
                <label for="level" class="block text-sm font-semibold text-navy">{{ __t('academy.courses.level') }}</label>
                <select id="level" name="level" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">{{ __t('academy.courses.all_levels') }}</option>
                    @foreach($levels as $value => $label)
                        <option value="{{ $value }}" @selected($level === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="audience" class="block text-sm font-semibold text-navy">{{ __t('academy.courses.audience') }}</label>
                <select id="audience" name="audience" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">{{ __t('academy.courses.all_audiences') }}</option>
                    @foreach($audiences as $value => $label)
                        <option value="{{ $value }}" @selected($audience === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button class="min-h-11 rounded-lg bg-brand px-5 font-semibold text-white hover:bg-blue-700">{{ __t('academy.courses.apply') }}</button>
        </div>

        @if($term || $level || $audience)
            <div class="mt-3">
                <a href="{{ route('academy.courses') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand">
                    {{ __t('academy.courses.clear') }}
                </a>
            </div>
        @endif
    </form>

    <p class="mb-4 text-sm text-slate-500" role="status">
        {{ __tc('academy.courses.count', $courses->count()) }}
    </p>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($courses as $course)
            @php
                $total = $course->published_lessons_count;
                $done = $course->publishedLessons->whereIn('id', $completed)->count();
                $pct = $total > 0 ? round($done / $total * 100) : 0;
            @endphp

            <a href="{{ route('academy.course', $course) }}"
               class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    @if($course->level)
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ __t('academy.common.level.'.$course->level) }}</span>
                    @endif
                    @if($course->audience_label)
                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand">{{ $course->audience_label }}</span>
                    @endif
                </div>

                <h2 class="text-lg font-extrabold text-navy transition group-hover:text-brand">{{ $course->translated('title') }}</h2>
                <p class="mt-1 line-clamp-3 text-sm text-slate-500">{{ $course->translated('description') }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span>{{ __tc('academy.common.lessons', $total) }}</span>
                    @if($course->durationLabel())
                        <span aria-hidden="true">·</span>
                        <span>{{ $course->durationLabel() }}</span>
                    @endif
                </div>

                @if($done > 0)
                    <div class="mt-3">
                        <div class="mb-1 text-xs text-slate-500">{{ __t('academy.common.lessons_done', ['done' => $done, 'total' => $total]) }}</div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100"
                             role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}"
                             aria-label="{{ __t('academy.common.course_progress_aria', ['course' => $course->translated('title'), 'done' => $done, 'total' => $total]) }}">
                            <div class="h-full bg-ok" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endif
            </a>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:col-span-2 lg:col-span-3">
                <p class="font-semibold text-slate-700">{{ __t('academy.courses.none') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __t('academy.courses.none_hint') }}</p>
            </div>
        @endforelse
    </div>
@endsection
