@extends('academy.layout')

@section('title', $tutorial->translated('title').' - '.__t('academy.tutorials.title').' - Pilot Academy')

@php
    $metaDescription = \Illuminate\Support\Str::limit($tutorial->translated('summary') ?: __t('academy.tutorials.meta'), 155);
@endphp

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('academy.tutorials') }}" class="text-sm font-semibold text-brand"><span class="inline-block rtl:rotate-180">&larr;</span> {{ __t('academy.tutorials.title') }}</a>

        @if(! $tutorial->isPublished())
            <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <strong>{{ __t('academy.tutorials.draft_notice', ['status' => $tutorial->statusLabel()]) }}</strong>
                - {{ __t('academy.tutorials.draft_hint') }}
            </div>
        @endif

        <h1 class="mt-3 text-2xl font-extrabold text-navy sm:text-3xl">{{ $tutorial->translated('title') }}</h1>

        @if($tutorial->translated('summary'))
            <p class="mt-1 text-slate-500">{{ $tutorial->translated('summary') }}</p>
        @endif

        @if($tutorial->duration_minutes || $tutorial->product)
            <p class="mt-2 text-sm text-slate-500">
                @if($tutorial->duration_minutes)
                    {{ __t('academy.webinars.minutes', ['count' => $tutorial->duration_minutes]) }}
                @endif
                @if($tutorial->product)
                    @if($tutorial->duration_minutes)<span aria-hidden="true">·</span>@endif
                    {{ $tutorial->product->name }}
                @endif
            </p>
        @endif

        @if($tutorial->videoType() === \App\Models\Tutorial::TYPE_UPLOAD)
            <div class="mt-6 aspect-video overflow-hidden rounded-2xl border border-slate-200 bg-black shadow-sm">
                <video class="h-full w-full" controls playsinline preload="metadata">
                    <source src="{{ $tutorial->videoUrl() }}">
                    {{ __t('academy.lesson.no_video_support') }}
                </video>
            </div>
        @elseif($tutorial->youtubeId())
            {{-- youtube-nocookie, as on a lesson: no tracking cookie until play. --}}
            <div class="mt-6 aspect-video overflow-hidden rounded-2xl border border-slate-200 bg-black shadow-sm">
                <iframe class="h-full w-full"
                        src="https://www.youtube-nocookie.com/embed/{{ $tutorial->youtubeId() }}?rel=0"
                        title="{{ $tutorial->translated('title') }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
        @else
            <p class="mt-6 text-slate-500">{{ __t('academy.tutorials.no_video_yet') }}</p>
        @endif

        <p class="mt-6 text-sm text-slate-500">{{ __t('academy.tutorials.reference_only') }}</p>
    </div>
@endsection
