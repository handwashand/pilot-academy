@extends('academy.layout')

@section('title', $caseStudy->title.' - Case Study - Pilot Academy')

@php
    $metaDescription = \Illuminate\Support\Str::limit($caseStudy->short_problem ?: 'Pilot Academy case study.', 155);

    $sections = [
        'Customer scenario and problem' => $caseStudy->scenario_problem,
        'Desired outcome' => $caseStudy->desired_outcome,
        'Required devices, data, and prerequisites' => $caseStudy->prerequisites,
        'Pilot features used' => $caseStudy->pilot_features,
        'Step-by-step configuration' => $caseStudy->configuration_steps,
        'How to test and verify the setup' => $caseStudy->testing_verification,
        'Expected results and limitations' => $caseStudy->expected_results,
        'Troubleshooting and common mistakes' => $caseStudy->troubleshooting,
        'Ways partners can adapt the solution' => $caseStudy->adaptation,
    ];
@endphp

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_280px] lg:gap-8">
        <div>
            <a href="{{ route('academy.case-studies.index') }}" class="text-sm font-semibold text-brand">&larr; Case Studies</a>

            @if(! $caseStudy->isPublished())
                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <strong>Case study: {{ $caseStudy->statusLabel() }}</strong> - learners cannot see this page. You are previewing it as an editor.
                </div>
            @endif

            <div class="mt-3 flex flex-wrap items-center gap-2">
                @if($caseStudy->industry)
                    <span class="rounded bg-blue-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand">{{ $caseStudy->industry }}</span>
                @endif
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">{{ $caseStudy->difficultyLabel() }}</span>
                @if($caseStudy->implementation_time)
                    <span class="rounded bg-green-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-green-700">{{ $caseStudy->implementation_time }}</span>
                @endif
            </div>

            <h1 class="mt-2 text-2xl font-extrabold text-navy sm:text-3xl">{{ $caseStudy->title }}</h1>
            @if($caseStudy->short_problem)
                <p class="mt-1 text-slate-500">{{ $caseStudy->short_problem }}</p>
            @endif

            @if($caseStudy->coverMediaItem?->url)
                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <img src="{{ $caseStudy->coverMediaItem->url }}" alt="" class="h-auto w-full">
                </div>
            @endif

            @if($caseStudy->diagramMediaItem?->url)
                <figure class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <img src="{{ $caseStudy->diagramMediaItem->url }}" alt="Diagram for {{ $caseStudy->title }}" class="h-auto w-full">
                    <figcaption class="border-t border-slate-100 px-4 py-2 text-sm text-slate-500">Reference diagram or sanitized screenshot.</figcaption>
                </figure>
            @endif

            @foreach($sections as $heading => $body)
                @if(filled($body))
                    <section id="{{ \Illuminate\Support\Str::slug($heading) }}" class="prose-lesson mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm scroll-mt-20">
                        <h2>{{ $heading }}</h2>
                        {!! $body !!}
                    </section>
                @endif
            @endforeach

            <section id="related" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm scroll-mt-20">
                <h2 class="text-xl font-extrabold text-navy">Related Academy lessons and documentation</h2>

                @if($relatedLessons->isNotEmpty())
                    <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                        @foreach($relatedLessons as $lesson)
                            @php($course = $lesson->courses->first())
                            @if($course)
                                <a href="{{ route('academy.lesson', [$course, $lesson]) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 active:bg-slate-100">
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-navy">{{ $lesson->translated('title') }}</span>
                                        <span class="block text-sm text-slate-500">{{ $course->translated('title') }}</span>
                                    </span>
                                    <span aria-hidden="true" class="flex-none text-slate-400">&rarr;</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if(! empty($caseStudy->related_links))
                    <ul class="mt-4 space-y-2">
                        @foreach($caseStudy->related_links as $link)
                            @if(! empty($link['title']) && ! empty($link['url']))
                                <li>
                                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                                       class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 font-medium text-slate-700 hover:border-brand/40 hover:bg-slate-50 active:bg-slate-100">
                                        <span aria-hidden="true" class="flex-none text-slate-400">&nearr;</span>
                                        <span class="min-w-0 break-words">{{ $link['title'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif

                @if($relatedLessons->isEmpty() && empty($caseStudy->related_links))
                    <p class="mt-2 text-sm text-slate-500">No related lessons or documentation have been linked yet.</p>
                @endif
            </section>
        </div>

        <aside class="lg:sticky lg:top-20 self-start">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Study details</div>
                <dl class="mt-3 space-y-3 text-sm">
                    @if($caseStudy->product)
                        <div>
                            <dt class="font-semibold text-navy">Product</dt>
                            <dd class="text-slate-500">{{ $caseStudy->product->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="font-semibold text-navy">Privacy</dt>
                        <dd class="text-slate-500">{{ $caseStudy->is_customer_approved ? 'Customer approved' : ($caseStudy->is_anonymized ? 'Anonymized' : 'Internal draft') }}</dd>
                    </div>
                    @if($caseStudy->source_note)
                        <div>
                            <dt class="font-semibold text-navy">Source note</dt>
                            <dd class="text-slate-500">{{ $caseStudy->source_note }}</dd>
                        </div>
                    @endif
                    @if($caseStudy->performance_claim_note)
                        <div>
                            <dt class="font-semibold text-navy">Performance claims</dt>
                            <dd class="text-slate-500">{{ $caseStudy->performance_claim_note }}</dd>
                        </div>
                    @endif
                </dl>

                @if($caseStudy->featureList() !== [])
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Features</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($caseStudy->featureList() as $featureName)
                                <span class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-600">{{ $featureName }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </div>
@endsection
