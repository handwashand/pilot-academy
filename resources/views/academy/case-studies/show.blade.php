@extends('academy.layout')

@section('title', $caseStudy->translated('title').' - '.__t('academy.case_studies.title').' - Pilot Academy')

@php
    $metaDescription = \Illuminate\Support\Str::limit($caseStudy->translated('short_problem') ?: __t('academy.case_studies.meta'), 155);
@endphp

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_280px] lg:gap-8">
        <div>
            <a href="{{ route('academy.case-studies.index') }}" class="text-sm font-semibold text-brand"><span class="inline-block rtl:rotate-180">&larr;</span> {{ __t('academy.case_studies.title') }}</a>

            @if(! $caseStudy->isPublished())
                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <strong>{{ __t('academy.case_studies.draft_notice', ['status' => $caseStudy->statusLabel()]) }}</strong>
                    - {{ __t('academy.case_studies.draft_hint') }}
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

            <h1 class="mt-2 text-2xl font-extrabold text-navy sm:text-3xl">{{ $caseStudy->translated('title') }}</h1>
            @if($caseStudy->short_problem)
                <p class="mt-1 text-slate-500">{{ $caseStudy->translated('short_problem') }}</p>
            @endif

            @if($caseStudy->coverMediaItem?->url)
                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <img src="{{ $caseStudy->coverMediaItem->url }}" alt="" class="h-auto w-full">
                </div>
            @endif

            @if($caseStudy->diagramMediaItem?->url)
                <figure class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <img src="{{ $caseStudy->diagramMediaItem->url }}"
                         alt="{{ __t('academy.case_studies.diagram_alt', ['title' => $caseStudy->translated('title')]) }}" class="h-auto w-full">
                    <figcaption class="border-t border-slate-100 px-4 py-2 text-sm text-slate-500">{{ __t('academy.case_studies.diagram_caption') }}</figcaption>
                </figure>
            @endif

            @foreach($caseStudy->sections() as $section)
                <section id="{{ $section['anchor'] }}" class="prose-lesson mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm scroll-mt-20">
                    <h2>{{ $section['heading'] }}</h2>
                    {!! $section['body'] !!}

                    {{-- The step's own screenshots, under the words they belong to. --}}
                    @foreach($section['images'] as $image)
                        <img src="{{ $image }}"
                             alt="{{ __t('academy.case_studies.step_image_alt', ['step' => $section['heading']]) }}"
                             class="mt-4 h-auto w-full rounded-xl border border-slate-200">
                    @endforeach
                </section>
            @endforeach

            <section id="related" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm scroll-mt-20">
                <h2 class="text-xl font-extrabold text-navy">{{ __t('academy.case_studies.sections.related') }}</h2>

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
                                    <span aria-hidden="true" class="flex-none text-slate-400"><span class="inline-block rtl:rotate-180">&rarr;</span></span>
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
                    <p class="mt-2 text-sm text-slate-500">{{ __t('academy.case_studies.no_related') }}</p>
                @endif
            </section>
        </div>

        <aside class="lg:sticky lg:top-20 self-start">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __t('academy.case_studies.details') }}</div>
                <dl class="mt-3 space-y-3 text-sm">
                    @if($caseStudy->product)
                        <div>
                            <dt class="font-semibold text-navy">{{ __t('academy.case_studies.product') }}</dt>
                            <dd class="text-slate-500">{{ $caseStudy->product->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="font-semibold text-navy">{{ __t('academy.case_studies.privacy') }}</dt>
                        <dd class="text-slate-500">
                            @if($caseStudy->is_customer_approved)
                                {{ __t('academy.case_studies.approved') }}
                            @elseif($caseStudy->is_anonymized)
                                {{ __t('academy.case_studies.anonymized') }}
                            @else
                                {{ __t('academy.case_studies.internal') }}
                            @endif
                        </dd>
                    </div>
                    @if($isEditor && $caseStudy->source_note)
                        <div>
                            <dt class="font-semibold text-navy">{{ __t('academy.case_studies.source_note') }}</dt>
                            <dd class="text-slate-500">{{ $caseStudy->source_note }}</dd>
                        </div>
                    @endif
                    @if($isEditor && $caseStudy->performance_claim_note)
                        <div>
                            <dt class="font-semibold text-navy">{{ __t('academy.case_studies.performance_claims') }}</dt>
                            <dd class="text-slate-500">{{ $caseStudy->performance_claim_note }}</dd>
                        </div>
                    @endif
                </dl>

                @if($caseStudy->featureList() !== [])
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __t('academy.case_studies.features') }}</div>
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
