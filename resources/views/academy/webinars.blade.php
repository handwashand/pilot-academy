@extends('academy.layout')

@section('title', __t('academy.webinars.title').' - Pilot Academy')

@php
    $metaDescription = __t('academy.webinars.meta');
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.webinars.title') }}</h1>
        <p class="mt-1 max-w-3xl text-slate-500">{{ __t('academy.webinars.intro') }}</p>
    </div>

    <section class="mb-10">
        <h2 class="mb-4 text-xl font-extrabold text-navy">{{ __t('academy.webinars.upcoming') }}</h2>

        @forelse($upcoming as $webinar)
            <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-brand">{{ $webinar->whenLabel() }}</p>
                        <h3 class="mt-1 text-lg font-extrabold text-navy">
                            <a href="{{ route('academy.webinar', $webinar) }}" class="hover:text-brand">{{ $webinar->translated('title') }}</a>
                        </h3>
                        @if($webinar->translated('summary'))
                            <p class="mt-1 text-sm text-slate-500">{{ $webinar->translated('summary') }}</p>
                        @endif
                        <p class="mt-2 text-sm text-slate-500">
                            @if($webinar->presenter)
                                {{ __t('academy.webinars.presented_by', ['presenter' => $webinar->presenter]) }}
                            @endif
                            @if($webinar->duration_minutes)
                                <span aria-hidden="true">·</span> {{ __t('academy.webinars.minutes', ['count' => $webinar->duration_minutes]) }}
                            @endif
                        </p>
                    </div>

                    @if($webinar->join_url)
                        <a href="{{ $webinar->join_url }}" target="_blank" rel="noopener"
                           class="inline-flex min-h-11 flex-none items-center justify-center rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ __t('academy.webinars.join') }}
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-semibold text-slate-700">{{ __t('academy.webinars.none_upcoming') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __t('academy.webinars.none_upcoming_hint') }}</p>
            </div>
        @endforelse
    </section>

    <section>
        <h2 class="mb-4 text-xl font-extrabold text-navy">{{ __t('academy.webinars.past') }}</h2>

        @forelse($past as $webinar)
            <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">{{ $webinar->whenLabel() }}</p>
                        <h3 class="mt-1 text-lg font-extrabold text-navy">
                            <a href="{{ route('academy.webinar', $webinar) }}" class="hover:text-brand">{{ $webinar->translated('title') }}</a>
                        </h3>
                        @if($webinar->translated('summary'))
                            <p class="mt-1 text-sm text-slate-500">{{ $webinar->translated('summary') }}</p>
                        @endif
                    </div>

                    @if($webinar->hasRecording())
                        <a href="{{ $webinar->recording_url }}" target="_blank" rel="noopener"
                           class="inline-flex min-h-11 flex-none items-center justify-center rounded-lg bg-navy px-5 text-sm font-semibold text-white hover:bg-slate-800">
                            {{ __t('academy.webinars.watch') }}
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-semibold text-slate-700">{{ __t('academy.webinars.none_past') }}</p>
            </div>
        @endforelse
    </section>
@endsection
