@extends('academy.layout')

@section('title', $webinar->translated('title').' - '.__t('academy.webinars.title').' - Pilot Academy')

@php
    $metaDescription = \Illuminate\Support\Str::limit($webinar->translated('summary') ?: __t('academy.webinars.meta'), 155);
@endphp

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('academy.webinars') }}" class="text-sm font-semibold text-brand">&larr; {{ __t('academy.webinars.title') }}</a>

        @if(! $webinar->isPublished())
            <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <strong>{{ __t('academy.webinars.draft_notice', ['status' => $webinar->statusLabel()]) }}</strong>
                - {{ __t('academy.webinars.draft_hint') }}
            </div>
        @endif

        <p class="mt-3 text-sm font-semibold text-brand">{{ $webinar->whenLabel() }}</p>
        <h1 class="mt-1 text-2xl font-extrabold text-navy sm:text-3xl">{{ $webinar->translated('title') }}</h1>

        <p class="mt-2 text-sm text-slate-500">
            @if($webinar->presenter)
                {{ __t('academy.webinars.presented_by', ['presenter' => $webinar->presenter]) }}
            @endif
            @if($webinar->duration_minutes)
                <span aria-hidden="true">·</span> {{ __t('academy.webinars.minutes', ['count' => $webinar->duration_minutes]) }}
            @endif
            @if($webinar->product)
                <span aria-hidden="true">·</span> {{ $webinar->product->name }}
            @endif
        </p>

        @if($webinar->coverMediaItem?->url)
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <img src="{{ $webinar->coverMediaItem->url }}" alt="" class="h-auto w-full">
            </div>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            @if($webinar->isUpcoming() && $webinar->join_url)
                <a href="{{ $webinar->join_url }}" target="_blank" rel="noopener"
                   class="inline-flex min-h-11 items-center rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-blue-700">
                    {{ __t('academy.webinars.join') }}
                </a>
            @endif
            @if($webinar->hasRecording())
                <a href="{{ $webinar->recording_url }}" target="_blank" rel="noopener"
                   class="inline-flex min-h-11 items-center rounded-lg bg-navy px-5 text-sm font-semibold text-white hover:bg-slate-800">
                    {{ __t('academy.webinars.watch') }}
                </a>
            @endif
        </div>

        @if($webinar->translated('description'))
            <div class="prose-lesson mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                {!! $webinar->translated('description') !!}
            </div>
        @elseif($webinar->translated('summary'))
            <p class="mt-6 text-slate-600">{{ $webinar->translated('summary') }}</p>
        @endif
    </div>
@endsection
