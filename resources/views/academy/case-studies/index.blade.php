@extends('academy.layout')

@section('title', 'Case Studies - Pilot Academy')

@php
    $metaDescription = 'Real Pilot deployment patterns for partners and integrators.';
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('academy.home') }}" class="text-sm text-brand font-semibold">&larr; All courses</a>
        <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-navy">Case Studies</h1>
        <p class="mt-1 max-w-3xl text-slate-500">Learn from proven Pilot configurations and adapt them for partner deployments.</p>
    </div>

    <form method="GET" action="{{ route('academy.case-studies.index') }}" class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[1.5fr_1fr_1fr_1fr_auto] md:items-end">
            <div>
                <label for="q" class="block text-sm font-semibold text-navy">Search</label>
                <input id="q" name="q" value="{{ $term }}" type="search"
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800 focus:border-brand"
                       placeholder="Geofence, fuel, overspeeding">
            </div>

            <div>
                <label for="industry" class="block text-sm font-semibold text-navy">Industry</label>
                <select id="industry" name="industry" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">All industries</option>
                    @foreach($industries as $option)
                        <option value="{{ $option }}" @selected($industry === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="feature" class="block text-sm font-semibold text-navy">Feature</label>
                <select id="feature" name="feature" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">All features</option>
                    @foreach($features as $option)
                        <option value="{{ $option }}" @selected($feature === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="difficulty" class="block text-sm font-semibold text-navy">Difficulty</label>
                <select id="difficulty" name="difficulty" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                    <option value="">All levels</option>
                    @foreach($difficulties as $value => $label)
                        <option value="{{ $value }}" @selected($difficulty === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button class="min-h-11 rounded-lg bg-brand px-5 font-semibold text-white hover:bg-blue-700">Apply</button>
        </div>

        @if($term || $industry || $feature || $difficulty)
            <div class="mt-3">
                <a href="{{ route('academy.case-studies.index') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand">
                    Clear filters
                </a>
            </div>
        @endif
    </form>

    <p class="mb-4 text-sm text-slate-500" role="status">
        {{ $caseStudies->total() }} {{ \Illuminate\Support\Str::plural('case study', $caseStudies->total()) }}
    </p>

    @forelse($caseStudies as $study)
        <a href="{{ route('academy.case-studies.show', $study) }}"
           class="group mb-4 grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:grid-cols-[180px_1fr]">
            <div class="flex min-h-36 items-center justify-center bg-gradient-to-br from-brand to-navy">
                @if($study->coverMediaItem?->url)
                    <img src="{{ $study->coverMediaItem->url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="px-4 text-center text-3xl font-extrabold text-white/90">CS</span>
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

                <h2 class="text-lg font-extrabold text-navy transition group-hover:text-brand">{{ $study->title }}</h2>
                @if($study->short_problem)
                    <p class="mt-1 text-sm text-slate-500">{{ $study->short_problem }}</p>
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
            <p class="font-semibold text-slate-700">No case studies matched.</p>
            <p class="mt-1 text-sm text-slate-500">Try clearing one filter or searching for a shorter term.</p>
        </div>
    @endforelse

    @if($caseStudies->hasPages())
        <div class="mt-6">
            {{ $caseStudies->links() }}
        </div>
    @endif
@endsection
