@extends('academy.layout')

@section('title', __t('academy.case_studies.title').' - Pilot Academy')

@php
    $metaDescription = __t('academy.case_studies.meta');
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('academy.home') }}" class="text-sm text-brand font-semibold"><span class="inline-block rtl:rotate-180">&larr;</span> {{ __t('academy.common.all_courses') }}</a>
        <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.case_studies.title') }}</h1>
        <p class="mt-1 max-w-3xl text-slate-500">{{ __t('academy.case_studies.intro') }}</p>
    </div>

    <form method="GET" action="{{ route('academy.case-studies.index') }}" class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[1.5fr_1fr_1fr_1fr_auto] md:items-end">
            <div>
                <label for="q" class="block text-sm font-semibold text-navy">{{ __t('academy.case_studies.search') }}</label>
                <input id="q" name="q" value="{{ $term }}" type="search"
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800 focus:border-brand"
                       placeholder="{{ __t('academy.case_studies.search_hint') }}">
            </div>

            <div>
                <label for="industry" class="block text-sm font-semibold text-navy">{{ __t('academy.case_studies.industry') }}</label>
                <select id="industry" name="industry" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">{{ __t('academy.case_studies.all_industries') }}</option>
                    @foreach($industries as $option)
                        <option value="{{ $option }}" @selected($industry === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="feature" class="block text-sm font-semibold text-navy">{{ __t('academy.case_studies.feature') }}</label>
                <select id="feature" name="feature" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">{{ __t('academy.case_studies.all_features') }}</option>
                    @foreach($features as $option)
                        <option value="{{ $option }}" @selected($feature === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="difficulty" class="block text-sm font-semibold text-navy">{{ __t('academy.case_studies.difficulty') }}</label>
                <select id="difficulty" name="difficulty" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">{{ __t('academy.case_studies.all_levels') }}</option>
                    @foreach($difficulties as $value => $label)
                        <option value="{{ $value }}" @selected($difficulty === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button class="min-h-11 rounded-lg bg-brand px-5 font-semibold text-white hover:bg-blue-700">{{ __t('academy.case_studies.apply') }}</button>
        </div>

        @if($term || $industry || $feature || $difficulty)
            <div class="mt-3">
                <a href="{{ route('academy.case-studies.index') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand">
                    {{ __t('academy.case_studies.clear') }}
                </a>
            </div>
        @endif
    </form>

    <p class="mb-4 text-sm text-slate-500" role="status">
        {{ __tc('academy.case_studies.count', $caseStudies->total()) }}
    </p>

    @forelse($caseStudies as $study)
        <a href="{{ route('academy.case-studies.show', $study) }}"
           class="group mb-4 grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:grid-cols-[180px_1fr]">
            <div class="flex min-h-36 items-center justify-center bg-gradient-to-br from-brand to-navy">
                @if($study->coverMediaItem?->url)
                    <img src="{{ $study->coverMediaItem->url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span aria-hidden="true" class="px-4 text-center text-3xl font-extrabold text-white/90">CS</span>
                @endif
            </div>

            <div class="p-5">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    @if($study->industry)
                        <span class="rounded bg-blue-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand">{{ $study->industry }}</span>
                    @endif
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ $study->difficultyLabel() }}</span>
                    @if($study->implementation_time)
                        <span class="rounded bg-green-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-green-700">{{ $study->implementation_time }}</span>
                    @endif
                </div>

                <h2 class="text-lg font-extrabold text-navy transition group-hover:text-brand">{{ $study->translated('title') }}</h2>
                @if($study->short_problem)
                    <p class="mt-1 text-sm text-slate-500">{{ $study->translated('short_problem') }}</p>
                @endif

                @if($study->featureList() !== [])
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($study->featureList() as $featureName)
                            <span class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600">{{ $featureName }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </a>
    @empty
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="font-semibold text-slate-700">{{ __t('academy.case_studies.none') }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ __t('academy.case_studies.none_hint') }}</p>
        </div>
    @endforelse

    @if($caseStudies->hasPages())
        <div class="mt-6">
            {{ $caseStudies->links() }}
        </div>
    @endif
@endsection
